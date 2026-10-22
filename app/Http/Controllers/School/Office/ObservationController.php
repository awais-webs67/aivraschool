<?php

namespace App\Http\Controllers\School\Office;

use App\Http\Controllers\Controller;
use App\Models\Observation;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Subject;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Classroom observations of teachers by the principal / coordinator. */
class ObservationController extends Controller
{
    public function index(Request $request): View
    {
        $staffId = $request->integer('staff') ?: null;

        return view('school.observations.index', [
            'observations' => Observation::query()->with('staff', 'section.schoolClass', 'subject', 'observer')
                ->when($staffId, fn ($q) => $q->where('staff_id', $staffId))->latest('date')->latest('id')->paginate(20)->withQueryString(),
            'teachers' => Staff::query()->active()->orderBy('name')->get(),
            'averages' => Observation::query()->selectRaw('staff_id, avg(overall) as avg, count(*) as n, max(date) as last')->groupBy('staff_id')->get()->keyBy('staff_id'),
            'staffId' => $staffId,
            'groups' => Section::grouped(),
            'subjects' => Subject::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = current_school()->id;
        $data = $request->validate([
            'staff_id' => ['required', Rule::exists('staff', 'id')->where('school_id', $schoolId)],
            'section_id' => ['nullable', Rule::exists('sections', 'id')->where('school_id', $schoolId)],
            'subject_id' => ['nullable', Rule::exists('subjects', 'id')->where('school_id', $schoolId)],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'ratings' => ['required', 'array'],
            'ratings.*' => ['required', 'integer', 'between:1,5'],
            'strengths' => ['nullable', 'string', 'max:2000'],
            'improvements' => ['nullable', 'string', 'max:2000'],
        ]);
        $ratings = array_intersect_key($data['ratings'], Observation::AREAS);
        abort_if(count($ratings) !== count(Observation::AREAS), 422, 'Rate every area.');

        $obs = Observation::create($data + ['ratings' => $ratings, 'overall' => round(array_sum($ratings) / count($ratings), 1), 'observer_id' => $request->user('school')->id]);
        Activity::log('observation.created', "Observed a lesson of {$obs->staff->name} ({$obs->overall}/5)", $obs);

        return back()->with('success', 'Observation saved.');
    }

    public function destroy(Observation $observation): RedirectResponse
    {
        $observation->delete();

        return back()->with('success', 'Observation deleted.');
    }
}
