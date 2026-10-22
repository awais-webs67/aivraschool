<?php

namespace App\Http\Controllers\School\Setup;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(): View
    {
        return view('school.setup.subjects', [
            'subjects' => Subject::query()->orderBy('name')->with('classes')->get(),
            'classes' => SchoolClass::query()->ordered()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $subject = Subject::create($data);
        $subject->classes()->sync($data['class_ids'] ?? []);

        return back()->with('success', "{$subject->name} added.");
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $data = $this->validated($request, $subject);

        $subject->update($data);
        $subject->classes()->sync($data['class_ids'] ?? []);

        return back()->with('success', "{$subject->name} updated.");
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $subject->delete();

        return back()->with('success', "{$subject->name} deleted.");
    }

    /** One click: add common subjects (skips any that already exist). */
    public function addStandard(): RedirectResponse
    {
        $existing = Subject::query()->pluck('name')->map(fn ($n) => mb_strtolower($n))->all();
        $added = 0;

        DB::transaction(function () use ($existing, &$added) {
            foreach (Subject::STANDARD as $name) {
                if (! in_array(mb_strtolower($name), $existing, true)) {
                    Subject::create(['name' => $name, 'type' => 'theory']);
                    $added++;
                }
            }
        });

        return back()->with('success', $added ? "{$added} subjects added." : 'All common subjects already exist.');
    }

    protected function validated(Request $request, ?Subject $subject = null): array
    {
        $schoolId = current_school()->id;

        return $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('subjects')->where('school_id', $schoolId)->ignore($subject?->id)],
            'code' => ['nullable', 'string', 'max:20'],
            'type' => ['required', Rule::in(array_keys(Subject::TYPES))],
            'class_ids' => ['array'],
            'class_ids.*' => ['integer', Rule::exists('classes', 'id')->where('school_id', $schoolId)],
        ]);
    }
}
