<?php

namespace App\Http\Controllers\School\Setup;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClassController extends Controller
{
    public function index(): View
    {
        return view('school.setup.classes', [
            'classes' => SchoolClass::query()->ordered()->with('sections')->withCount('subjects')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['sort_order'] ??= (int) SchoolClass::query()->max('sort_order') + 1;

        $class = SchoolClass::create($data);
        $class->sections()->create(['name' => 'A']);

        return back()->with('success', "{$class->name} added with section A.");
    }

    public function update(Request $request, SchoolClass $class): RedirectResponse
    {
        $class->update($this->validated($request, $class));

        return back()->with('success', "{$class->name} updated.");
    }

    public function destroy(SchoolClass $class): RedirectResponse
    {
        $class->delete();

        return back()->with('success', "{$class->name} and its sections were deleted.");
    }

    /** One click: add Playgroup to Class 10 (skips any that already exist), each with section A. */
    public function addStandard(): RedirectResponse
    {
        $existing = SchoolClass::query()->pluck('name')->map(fn ($n) => mb_strtolower($n))->all();
        $added = 0;

        DB::transaction(function () use ($existing, &$added) {
            foreach (SchoolClass::STANDARD as $order => $name) {
                if (in_array(mb_strtolower($name), $existing, true)) {
                    continue;
                }

                SchoolClass::create(['name' => $name, 'sort_order' => $order + 1])
                    ->sections()->create(['name' => 'A']);
                $added++;
            }
        });

        return back()->with('success', $added ? "{$added} classes added." : 'All standard classes already exist.');
    }

    public function storeSection(Request $request, SchoolClass $class): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:30', Rule::unique('sections')->where('class_id', $class->id)],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $class->sections()->create($data);

        return back()->with('success', "Section {$data['name']} added to {$class->name}.");
    }

    public function destroySection(Section $section): RedirectResponse
    {
        if ($section->schoolClass->sections()->count() === 1) {
            return back()->with('error', 'A class needs at least one section.');
        }

        $section->delete();

        return back()->with('success', "Section {$section->name} deleted.");
    }

    protected function validated(Request $request, ?SchoolClass $class = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('classes')->where('school_id', current_school()->id)->ignore($class?->id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);
    }
}
