<?php

namespace App\Http\Controllers\School\Office;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\Student;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Behaviour record: concerns and merits. Teachers add; the principal sees everything. */
class DisciplineController extends Controller
{
    public function index(Request $request): View
    {
        $kind = in_array($request->query('kind'), ['concern', 'merit'], true) ? $request->query('kind') : null;

        return view('school.discipline.index', [
            'incidents' => Incident::query()->with('student.schoolClass', 'student.section', 'reporter')
                ->when($kind, fn ($q) => $q->where('kind', $kind))
                ->when($request->integer('student'), fn ($q, $id) => $q->where('student_id', $id))
                ->latest('date')->latest('id')->paginate(25)->withQueryString(),
            'kind' => $kind,
            'student' => Student::find($request->integer('student')),
            'thisMonth' => Incident::query()->where('date', '>=', today()->startOfMonth())->selectRaw('kind, count(*) as n')->groupBy('kind')->pluck('n', 'kind'),
            'repeat' => Incident::query()->where('kind', 'concern')->where('date', '>=', today()->subDays(30))
                ->selectRaw('student_id, count(*) as n')->groupBy('student_id')->having('n', '>=', 3)->with('student')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'admission_no' => ['required', 'string', 'max:30'],
            'kind' => ['required', Rule::in(['concern', 'merit'])],
            'category' => ['required', 'string', 'max:40'],
            'severity' => ['nullable', Rule::in(array_keys(Incident::SEVERITIES))],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['required', 'string', 'max:2000'],
            'action_taken' => ['nullable', 'string', 'max:40'],
        ], ['admission_no.required' => 'Enter the student\'s admission no.']);

        $student = Student::query()->where('admission_no', trim($data['admission_no']))->first();
        if (! $student) {
            return back()->withInput()->withErrors(['admission_no' => 'No student with this admission no.']);
        }

        $incident = Incident::create(collect($data)->except('admission_no')->all() + [
            'student_id' => $student->id,
            'severity' => $data['kind'] === 'merit' ? 'low' : ($data['severity'] ?? 'low'),
            'parent_informed' => $request->boolean('parent_informed'),
            'reported_by' => $request->user('school')->id,
        ]);
        Activity::log('discipline.'.$incident->kind, ucfirst($incident->kind)." for {$student->name}: {$incident->category}", $incident);

        return back()->with('success', 'Saved to '.$student->name.'\'s record.');
    }

    public function destroy(Request $request, Incident $incident): RedirectResponse
    {
        $user = $request->user('school');
        abort_unless($incident->reported_by === $user->id || $user->allows('approvals'), 403);
        $incident->delete();

        return back()->with('success', 'Record deleted.');
    }
}
