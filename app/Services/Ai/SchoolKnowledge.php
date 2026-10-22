<?php

namespace App\Services\Ai;

use App\Models\AcademicSession;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;

/**
 * What the AI assistant knows about a school: a short, plain-text summary built from the
 * school's own records (nothing from other schools). Kept compact to use few tokens.
 *
 * "Teaching" the assistant a new module = add one method here that summarises that module
 * (e.g. students(), attendance(), fees()) and list it in sections(). See docs/AI.md.
 */
class SchoolKnowledge
{
    /** Runs inside the school (current school set), so every query is scoped to it. */
    public function build(School $school): string
    {
        return $school->run(fn () => collect($this->sections($school))
            ->map(fn (string $text, string $title) => "## {$title}\n{$text}")
            ->join("\n\n"));
    }

    /** @return array<string, string> section title => text */
    protected function sections(School $school): array
    {
        return array_filter([
            'School' => $this->overview($school),
            'Academic sessions' => $this->sessions(),
            'Classes and sections' => $this->classes(),
            'Subjects' => $this->subjects(),
            'Staff accounts' => $this->users(),
        ]);
    }

    protected function overview(School $school): string
    {
        return collect([
            "Name: {$school->name}",
            'Location: '.(collect([$school->city, $school->country])->filter()->join(', ') ?: 'not set'),
            'Principal: '.($school->principal_name ?: 'not set'),
            'Students (approx.): '.($school->student_count ?: 'not recorded yet'),
            'Plan: '.($school->plan?->name ?? 'none').' · status '.$school->status->label(),
            'Fee currency: '.$school->currencyCode(),
            'Modules on: '.collect(config('skooline.modules'))->filter(fn ($m, $k) => $school->hasModule($k))->pluck('label')->join(', '),
        ])->join("\n");
    }

    protected function sessions(): string
    {
        return AcademicSession::query()->orderByDesc('starts_on')->limit(5)->get()
            ->map(fn ($s) => "- {$s->name} ({$s->starts_on->format('d M Y')} to {$s->ends_on->format('d M Y')})".($s->is_current ? ' CURRENT' : ''))
            ->join("\n") ?: 'None yet.';
    }

    protected function classes(): string
    {
        $classes = SchoolClass::query()->ordered()->with('sections:id,class_id,name,capacity')->withCount('subjects')->limit(60)->get();

        return $classes->map(fn ($c) => "- {$c->name}: sections ".($c->sections->pluck('name')->join(', ') ?: 'none').", {$c->subjects_count} subjects")
            ->join("\n") ?: 'None yet.';
    }

    protected function subjects(): string
    {
        return Subject::query()->orderBy('name')->with('classes:id,name')->limit(60)->get()
            ->map(fn ($s) => "- {$s->name}".($s->code ? " ({$s->code})" : '').': '.($s->classes->pluck('name')->join(', ') ?: 'no classes'))
            ->join("\n") ?: 'None yet.';
    }

    protected function users(): string
    {
        return User::query()->selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role')
            ->map(fn ($n, $role) => (User::ROLES[$role] ?? $role).": {$n}")->join(', ');
    }
}
