<?php

namespace App\Services\Ai;

use App\Models\AcademicSession;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Changes the AI may PROPOSE for a school admin. Nothing runs until the admin presses Confirm,
 * and every proposal is validated here again (the AI's output is never trusted as-is).
 * Add an action per new module: describe it in CATALOG and handle it in execute().
 */
class SchoolActions
{
    /** type => description shown to the AI (with the JSON params it must send). */
    public const CATALOG = [
        'create_class' => 'Add a class. params: {"name": "Class 6", "sections": ["A","B"]}',
        'add_section' => 'Add a section to an existing class. params: {"class": "Class 6", "section": "C", "capacity": 30}',
        'create_subject' => 'Add a subject, optionally taught in classes. params: {"name": "Biology", "code": "BIO", "classes": ["Class 9","Class 10"]}',
        'create_session' => 'Add an academic year. params: {"name": "2027-28", "starts_on": "2027-04-01", "ends_on": "2028-03-31", "make_current": false}',
        'set_current_session' => 'Make an existing session the current one. params: {"name": "2027-28"}',
    ];

    /**
     * Check a proposal and return clean params plus a one-line summary for the Confirm card.
     *
     * @return array{type: string, params: array, summary: string}
     */
    public function prepare(string $type, array $params): array
    {
        $rules = match ($type) {
            'create_class' => ['name' => ['required', 'string', 'max:50'], 'sections' => ['array', 'max:10'], 'sections.*' => ['string', 'max:30']],
            'add_section' => ['class' => ['required', 'string'], 'section' => ['required', 'string', 'max:30'], 'capacity' => ['nullable', 'integer', 'min:1', 'max:500']],
            'create_subject' => ['name' => ['required', 'string', 'max:80'], 'code' => ['nullable', 'string', 'max:20'], 'classes' => ['array', 'max:30'], 'classes.*' => ['string']],
            'create_session' => ['name' => ['required', 'string', 'max:30'], 'starts_on' => ['required', 'date'], 'ends_on' => ['required', 'date', 'after:starts_on'], 'make_current' => ['boolean']],
            'set_current_session' => ['name' => ['required', 'string']],
            default => throw ValidationException::withMessages(['action' => 'The assistant suggested something it is not allowed to do.']),
        };

        $clean = Validator::make($params, $rules)->validate();

        $summary = match ($type) {
            'create_class' => "Add class \"{$clean['name']}\" with sections ".(implode(', ', $clean['sections'] ?? []) ?: 'A'),
            'add_section' => "Add section \"{$clean['section']}\" to {$clean['class']}",
            'create_subject' => "Add subject \"{$clean['name']}\"".(! empty($clean['classes']) ? ' for '.implode(', ', $clean['classes']) : ''),
            'create_session' => "Add session {$clean['name']} (".Carbon::parse($clean['starts_on'])->format('d M Y').' – '.Carbon::parse($clean['ends_on'])->format('d M Y').')'.(! empty($clean['make_current']) ? ' and make it current' : ''),
            'set_current_session' => "Make {$clean['name']} the current session",
        };

        return ['type' => $type, 'params' => $clean, 'summary' => $summary];
    }

    /** Runs a confirmed action inside the current school. Returns a short result message. */
    public function execute(string $type, array $p): string
    {
        return DB::transaction(fn () => match ($type) {
            'create_class' => $this->createClass($p),
            'add_section' => $this->addSection($p),
            'create_subject' => $this->createSubject($p),
            'create_session' => $this->createSession($p),
            'set_current_session' => $this->setCurrent($p),
        });
    }

    protected function createClass(array $p): string
    {
        $this->fail(SchoolClass::where('name', $p['name'])->exists(), "{$p['name']} already exists.");

        $class = SchoolClass::create(['name' => $p['name'], 'sort_order' => (int) SchoolClass::max('sort_order') + 1]);
        foreach (array_unique(($p['sections'] ?? []) ?: ['A']) as $section) {
            $class->sections()->create(['name' => $section]);
        }

        return "Added {$class->name} with ".$class->sections()->count().' section(s).';
    }

    protected function addSection(array $p): string
    {
        $class = SchoolClass::where('name', $p['class'])->first();
        $this->fail(! $class, "There is no class called {$p['class']}.");
        $this->fail($class->sections()->where('name', $p['section'])->exists(), "{$class->name} already has section {$p['section']}.");

        Section::create(['class_id' => $class->id, 'name' => $p['section'], 'capacity' => $p['capacity'] ?? null]);

        return "Added section {$p['section']} to {$class->name}.";
    }

    protected function createSubject(array $p): string
    {
        $this->fail(Subject::where('name', $p['name'])->exists(), "{$p['name']} already exists.");

        $subject = Subject::create(['name' => $p['name'], 'code' => $p['code'] ?? null, 'type' => 'theory']);
        $classIds = SchoolClass::whereIn('name', $p['classes'] ?? [])->pluck('id');
        $subject->classes()->sync($classIds);

        return "Added {$subject->name}".($classIds->isNotEmpty() ? ' for '.$classIds->count().' class(es).' : '.');
    }

    protected function createSession(array $p): string
    {
        $this->fail(AcademicSession::where('name', $p['name'])->exists(), "Session {$p['name']} already exists.");

        $session = AcademicSession::create(['name' => $p['name'], 'starts_on' => $p['starts_on'], 'ends_on' => $p['ends_on']]);
        if (! empty($p['make_current'])) {
            $session->makeCurrent();
        }

        return "Added session {$session->name}.";
    }

    protected function setCurrent(array $p): string
    {
        $session = AcademicSession::where('name', $p['name'])->first();
        $this->fail(! $session, "There is no session called {$p['name']}.");
        $session->makeCurrent();

        return "{$session->name} is now the current session.";
    }

    protected function fail(bool $condition, string $message): void
    {
        if ($condition) {
            throw ValidationException::withMessages(['action' => $message]);
        }
    }
}
