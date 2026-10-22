<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AiUsage;
use App\Services\Ai\AiException;
use App\Services\Ai\AiManager;
use App\Services\Ai\SchoolActions;
use App\Services\Ai\SchoolAssistant;
use App\Support\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * AI assistant page in the school portal (admins and principals).
 * Proposed changes are kept in the session and only run when the admin confirms them.
 */
class AiAssistantController extends Controller
{
    public function index(Request $request): View
    {
        $school = current_school();

        return view('school.ai.index', [
            'limit' => $school->aiMonthlyLimit(),
            'used' => AiUsage::requestsThisMonth($school),
            'available' => AiManager::available(),
            'canAct' => SchoolAssistant::canAct($request->user('school')),
        ]);
    }

    public function ask(Request $request, SchoolAssistant $assistant): JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:1000'],
            'history' => ['array', 'max:20'],
            'history.*.role' => ['required', 'in:user,assistant'],
            'history.*.content' => ['required', 'string', 'max:3000'],
        ]);

        $school = current_school();
        $user = $request->user('school');
        // Only the last few turns are sent: enough context, fewer tokens.
        $history = array_slice($data['history'] ?? [], -(int) config('ai.history', 6));

        try {
            $result = $assistant->ask($school, $user, $data['question'], $history);
        } catch (ValidationException $e) {
            return response()->json(['error' => collect($e->errors())->flatten()->first()], 422);
        } catch (AiException $e) {
            return response()->json(['error' => 'The AI service is not available right now. Please try again in a few minutes.', 'type' => $e->type], 503);
        }

        $action = null;
        if ($result['action']) {
            $id = Str::random(20);
            $request->session()->put("ai_actions.{$id}", $result['action'] + ['school_id' => $school->id, 'user_id' => $user->id]);
            $action = ['id' => $id, 'summary' => $result['action']['summary'], 'url' => route('school.ai.confirm', ['action' => $id])];
        }

        return response()->json([
            'answer' => $result['answer'],
            'html' => SchoolAssistant::render($result['answer']),
            'action' => $action,
            'used' => AiUsage::requestsThisMonth($school),
            'limit' => $school->aiMonthlyLimit(),
        ]);
    }

    public function confirm(Request $request, string $action, SchoolActions $actions): JsonResponse
    {
        $user = $request->user('school');
        // pull() removes it, so a proposal can only run once.
        $pending = $request->session()->pull("ai_actions.{$action}");

        abort_unless($pending && $pending['school_id'] === current_school()->id && $pending['user_id'] === $user->id && SchoolAssistant::canAct($user), 404);

        try {
            $message = $actions->execute($pending['type'], $pending['params']);
        } catch (ValidationException $e) {
            return response()->json(['error' => collect($e->errors())->flatten()->first()], 422);
        }

        Activity::log('ai.action', "AI assistant: {$pending['summary']}");

        return response()->json(['message' => $message]);
    }
}
