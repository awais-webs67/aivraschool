<?php

namespace App\Http\Controllers\School\Office;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\FeeInvoice;
use App\Models\Section;
use App\Models\Student;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Leaving / character / bonafide certificates and student ID cards. */
class CertificateController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        return view('school.certificates.index', [
            'q' => $q,
            'found' => $q === '' ? collect() : Student::query()->with('schoolClass', 'section')
                ->where(fn ($s) => $s->where('name', 'like', "%{$q}%")->orWhere('admission_no', $q))->orderBy('name')->limit(20)->get(),
            'issued' => Certificate::query()->with('student')->latest('id')->limit(20)->get(),
            'groups' => Section::grouped(),
        ]);
    }

    public function issue(Request $request, Student $student): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Certificate::TYPES))],
            'conduct' => ['nullable', 'string', 'max:40'],
            'reason' => ['nullable', 'string', 'max:150'],
            'last_class' => ['nullable', 'string', 'max:60'],
            'remarks' => ['nullable', 'string', 'max:255'],
            'mark_left' => ['nullable', 'boolean'],
        ]);

        // A leaving certificate normally needs all fees cleared first.
        if ($data['type'] === 'leaving' && ! $request->boolean('ignore_dues')) {
            $owed = (float) FeeInvoice::query()->open()->where('student_id', $student->id)->get()->sum(fn ($i) => $i->balance());
            if ($owed > 0) {
                return back()->withInput()->withErrors(['type' => $student->name.' still owes '.money($owed).'. Clear the fees or tick "Issue anyway".']);
            }
        }

        $prefix = ['leaving' => 'SLC-', 'character' => 'CC-', 'bonafide' => 'BC-'][$data['type']];
        $certificate = Certificate::create([
            'student_id' => $student->id,
            'type' => $data['type'],
            'serial_no' => FeeInvoice::nextNumber($prefix, 'serial_no', Certificate::query()),
            'issued_on' => today(),
            'details' => array_filter([
                'conduct' => $data['conduct'] ?? 'Good',
                'reason' => $data['reason'] ?? null,
                'last_class' => $data['last_class'] ?? $student->classLabel(),
                'remarks' => $data['remarks'] ?? null,
            ]),
            'issued_by' => $request->user('school')->id,
        ]);

        if ($data['type'] === 'leaving' && $request->boolean('mark_left') && $student->status === 'active') {
            $student->update(['status' => 'left', 'left_on' => today(), 'left_reason' => $data['reason'] ?? 'Leaving certificate issued']);
        }

        Activity::log('certificate.issued', Certificate::TYPES[$certificate->type]." {$certificate->serial_no} for {$student->name}", $certificate);

        return redirect()->route('school.certificates.print', $certificate);
    }

    public function print(Certificate $certificate): View
    {
        return view('school.certificates.print', ['c' => $certificate->load('student.schoolClass', 'student.section'), 'school' => current_school()]);
    }

    /** ID cards for a section (8 per A4 page). */
    public function idCards(Request $request): View
    {
        $section = Section::with('schoolClass')->findOrFail($request->integer('section'));

        return view('school.certificates.id-cards', [
            'section' => $section,
            'students' => $section->students()->active()->orderByRaw('CAST(roll_no AS UNSIGNED), roll_no')->orderBy('name')->get(),
            'school' => current_school(),
            'validTill' => $request->query('valid') ?: today()->addYear()->format('M Y'),
        ]);
    }
}
