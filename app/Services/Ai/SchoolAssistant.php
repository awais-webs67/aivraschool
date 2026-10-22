<?php

namespace App\Services\Ai;

use App\Models\AiUsage;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The AI assistant inside a school portal.
 * - Principal: analysis only (answers questions about the school).
 * - School admin: analysis, and can propose one change that runs only after they confirm.
 */
class SchoolAssistant
{
    public function __construct(
        protected AiManager $ai,
        protected SchoolKnowledge $knowledge,
        protected SchoolActions $actions,
    ) {}

    public static function canAct(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * @param  list<array{role: string, content: string}>  $history  earlier turns (already trimmed)
     * @return array{answer: string, action: ?array{type: string, params: array, summary: string}, provider: string}
     */
    public function ask(School $school, User $user, string $question, array $history): array
    {
        $limit = $school->aiMonthlyLimit();
        if ($limit <= 0) {
            throw ValidationException::withMessages(['question' => 'AI is not included in your plan. Contact AivraSol to add it.']);
        }
        if (AiUsage::requestsThisMonth($school) >= $limit) {
            throw ValidationException::withMessages(['question' => "You have used all {$limit} AI questions for this month. The limit resets on the 1st."]);
        }

        $messages = array_merge($history, [['role' => 'user', 'content' => $question]]);
        $result = $this->ai->chat($this->systemPrompt($school, $user), $messages);

        AiUsage::record($school, $result['input_tokens'], $result['output_tokens']);

        [$answer, $proposal] = $this->parse($result['text']);

        $action = null;
        if ($proposal && self::canAct($user)) {
            try {
                $action = $school->run(fn () => $this->actions->prepare((string) ($proposal['type'] ?? ''), (array) ($proposal['params'] ?? [])));
            } catch (ValidationException $e) {
                $answer .= "\n\n_(The suggested change could not be prepared: ".collect($e->errors())->flatten()->first().')_';
            }
        }

        return ['answer' => $answer, 'action' => $action, 'provider' => $result['provider']];
    }

    /** Short instructions + the school's data. Kept small on purpose (tokens = cost). */
    protected function systemPrompt(School $school, User $user): string
    {
        $role = self::canAct($user)
            ? "The user is the SCHOOL ADMIN. You may propose ONE change from the action list when they ask for it. Never say a change is done: they must press Confirm.\nActions:\n"
                .collect(SchoolActions::CATALOG)->map(fn ($d, $t) => "- {$t}: {$d}")->join("\n")
            : 'The user is the PRINCIPAL: analysis only. If asked to add, edit or delete anything, politely explain that only the school admin can make changes, and set "action" to null.';

        $today = now()->format('d M Y');

        return <<<PROMPT
        You are Skooline Assistant for {$school->name}. Today is {$today}.
        Answer only from the SCHOOL DATA below. If something is not in the data, say it is not recorded yet and where in Skooline it can be added. Never invent numbers or names.
        Write clear, structured answers in Markdown: a one-line summary, then bullet points or a table. Keep it under 180 words unless asked for more.
        {$role}
        Reply with JSON only: {"answer": "<markdown>", "action": null or {"type": "<action>", "params": {...}}}

        SCHOOL DATA
        {$this->knowledge->build($school)}
        PROMPT;
    }

    /**
     * Pull the answer and optional action out of the model's reply. Models sometimes wrap the JSON
     * in ```code fences``` or add text around it; plain text is used as the answer when no JSON is found.
     *
     * @return array{0: string, 1: ?array}
     */
    protected function parse(string $text): array
    {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start !== false && $end > $start) {
            $json = json_decode(substr($text, $start, $end - $start + 1), true);

            if (is_array($json) && isset($json['answer'])) {
                return [trim((string) $json['answer']), is_array($json['action'] ?? null) ? $json['action'] : null];
            }
        }

        return [trim(preg_replace('/^```\w*|```$/m', '', $text)), null];
    }

    /** Safe HTML for an answer: Markdown with any raw HTML stripped and unsafe links blocked. */
    public static function render(string $markdown): string
    {
        return Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
    }
}
