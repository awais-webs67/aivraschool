<?php

namespace App\Http\Controllers\School\People;

use App\Http\Controllers\Controller;
use App\Http\Controllers\School\Daily\AttendanceController;
use App\Models\AcademicSession;
use App\Models\Enquiry;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Support\Activity;
use App\Support\Uploads;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Student records: list, profile, add / edit, leave, export and photo.
 */
class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        return view('school.students.index', [
            'students' => $this->filtered($filters)->with('schoolClass', 'section')
                ->orderBy(SchoolClass::select('sort_order')->whereColumn('classes.id', 'students.class_id'))
                ->orderByRaw('CAST(roll_no AS UNSIGNED), roll_no')->orderBy('name')
                ->paginate(30)->withQueryString(),
            'filters' => $filters,
            'groups' => Section::grouped(),
            'counts' => [
                'active' => Student::query()->active()->count(),
                'boys' => Student::query()->active()->where('gender', 'male')->count(),
                'girls' => Student::query()->active()->where('gender', 'female')->count(),
                'new' => Student::query()->active()->where('admission_date', '>=', now()->startOfMonth())->count(),
            ],
            'limit' => self::limit(),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        if (self::limitReached()) {
            return redirect()->route('school.students.index')->with('error', 'Your plan allows '.self::limit().' students. Ask AivraSol to upgrade to add more.');
        }

        $student = new Student(['admission_date' => now(), 'status' => 'active', 'admission_no' => Student::nextAdmissionNo()]);

        // Coming from an enquiry or "add sibling": fill what we already know.
        if ($from = Student::find($request->integer('sibling_of'))) {
            $student->fill($from->only(['father_name', 'father_phone', 'father_national_id', 'father_occupation', 'mother_name', 'mother_phone', 'guardian_name', 'guardian_relation', 'guardian_phone', 'address', 'city', 'email']));
        }

        if ($enquiry = Enquiry::find($request->integer('enquiry'))) {
            $student->fill([
                'name' => $enquiry->child_name, 'gender' => $enquiry->gender, 'date_of_birth' => $enquiry->date_of_birth,
                'father_name' => $enquiry->parent_name, 'father_phone' => $enquiry->phone, 'email' => $enquiry->email,
                'address' => $enquiry->address, 'previous_school' => $enquiry->previous_school,
            ]);
        }

        return view('school.students.form', ['student' => $student, 'groups' => Section::grouped(), 'enquiry' => $enquiry]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (self::limitReached()) {
            return back()->withInput()->with('error', 'Your plan allows '.self::limit().' students. Ask AivraSol to upgrade to add more.');
        }

        $data = $this->validated($request);
        $student = DB::transaction(function () use ($request, $data) {
            $student = Student::create($data + ['photo_path' => Uploads::store($request->file('photo'), 'students')]);

            // Admitted from an enquiry: close it.
            if ($enquiry = Enquiry::find($request->integer('enquiry_id'))) {
                $enquiry->update(['status' => 'admitted', 'student_id' => $student->id]);
            }

            return $student;
        });

        Activity::log('student.admitted', "Admitted {$student->name} ({$student->admission_no}) to {$student->classLabel()}", $student);

        return redirect()->route('school.students.show', $student)->with('success', "{$student->name} admitted. Admission no. {$student->admission_no}.");
    }

    public function show(Student $student): View
    {
        $session = AcademicSession::current();
        $since = $session?->starts_on ?? today()->subMonths(3);
        $attendance = StudentAttendance::query()->where('student_id', $student->id)->whereDate('date', '>=', $since)->get();

        return view('school.students.show', [
            'student' => $student->load('schoolClass', 'section.classTeacher'),
            'siblings' => $student->siblings(),
            'attendance' => AttendanceController::summarise($attendance) + [
                'since' => $since,
                'recentAbsences' => $attendance->where('status', 'absent')->sortByDesc('date')->take(5),
            ],
        ]);
    }

    public function edit(Student $student): View
    {
        return view('school.students.form', ['student' => $student, 'groups' => Section::grouped(), 'enquiry' => null]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $data = $this->validated($request, $student);
        $student->update($data + ['photo_path' => Uploads::replace($request->file('photo'), $student->photo_path, 'students')]);
        Activity::log('student.updated', "Updated {$student->name} ({$student->admission_no})", $student);

        return redirect()->route('school.students.show', $student)->with('success', 'Saved.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        if ($student->photo_path) {
            Storage::delete($student->photo_path);
        }
        $student->delete();
        Activity::log('student.deleted', "Deleted student {$student->name} ({$student->admission_no})");

        return redirect()->route('school.students.index')->with('success', "{$student->name} deleted.");
    }

    public function photo(Student $student): Response
    {
        abort_unless($student->photo_path && Storage::exists($student->photo_path), 404);

        return Storage::response($student->photo_path, null, ['Cache-Control' => 'private, max-age=86400']);
    }

    public function export(Request $request): StreamedResponse
    {
        $rows = $this->filtered($this->filters($request))->with('schoolClass', 'section')->orderBy('class_id')->orderBy('name');

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [...Student::IMPORT_COLUMNS, 'status']);
            $rows->each(fn (Student $s) => fputcsv($out, [
                $s->admission_no, $s->name, $s->gender, $s->date_of_birth?->toDateString(), $s->schoolClass?->name, $s->section?->name, $s->roll_no,
                $s->father_name, $s->father_phone, $s->father_national_id, $s->mother_name, $s->mother_phone, $s->address, $s->city,
                $s->national_id, $s->admission_date?->toDateString(), $s->status,
            ]));
            fclose($out);
        }, 'students-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    /** Most students the plan allows (null = no limit). */
    public static function limit(): ?int
    {
        $max = (int) current_school()->plan?->max_students;

        return $max > 0 ? $max : null;
    }

    public static function limitReached(int $adding = 1): bool
    {
        $limit = self::limit();

        return $limit !== null && Student::query()->active()->count() + $adding > $limit;
    }

    protected function filters(Request $request): array
    {
        return $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'class_id' => ['nullable', 'integer'],
            'section_id' => ['nullable', 'integer'],
            'gender' => ['nullable', Rule::in(array_keys(Student::GENDERS))],
            'status' => ['nullable', Rule::in([...array_keys(Student::STATUSES), 'all'])],
        ]);
    }

    protected function filtered(array $filters): Builder
    {
        $status = $filters['status'] ?? 'active';

        return Student::query()
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($filters['section_id'] ?? null, fn ($q, $id) => $q->where('section_id', $id))
            ->when($filters['class_id'] ?? null, fn ($q, $id) => $q->where('class_id', $id))
            ->when($filters['gender'] ?? null, fn ($q, $g) => $q->where('gender', $g))
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$term}%")->orWhere('admission_no', 'like', "%{$term}%")
                ->orWhere('father_name', 'like', "%{$term}%")->orWhere('father_phone', 'like', "%{$term}%")
                ->orWhere('roll_no', $term)));
    }

    protected function validated(Request $request, ?Student $student = null): array
    {
        $schoolId = current_school()->id;

        $data = $request->validate([
            'admission_no' => [$student ? 'required' : 'nullable', 'string', 'max:30', Rule::unique('students')->where('school_id', $schoolId)->ignore($student)],
            'name' => ['required', 'string', 'max:120'],
            'gender' => ['nullable', Rule::in(array_keys(Student::GENDERS))],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'national_id' => ['nullable', 'string', 'max:40'],
            'religion' => ['nullable', 'string', 'max:40'],
            'blood_group' => ['nullable', Rule::in(Student::BLOOD_GROUPS)],
            'section_id' => ['required', Rule::exists('sections', 'id')->where('school_id', $schoolId)],
            'roll_no' => ['nullable', 'string', 'max:20'],
            'admission_date' => ['nullable', 'date'],
            'category' => ['nullable', 'string', 'max:30'],
            'fee_discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'father_name' => ['nullable', 'string', 'max:120'],
            'father_phone' => ['nullable', 'string', 'max:30'],
            'father_national_id' => ['nullable', 'string', 'max:40'],
            'father_occupation' => ['nullable', 'string', 'max:80'],
            'mother_name' => ['nullable', 'string', 'max:120'],
            'mother_phone' => ['nullable', 'string', 'max:30'],
            'guardian_name' => ['nullable', 'string', 'max:120'],
            'guardian_relation' => ['nullable', 'string', 'max:40'],
            'guardian_phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:80'],
            'previous_school' => ['nullable', 'string', 'max:150'],
            'medical_notes' => ['nullable', 'string', 'max:1000'],
            'status' => [$student ? 'required' : 'nullable', Rule::in(array_keys(Student::STATUSES))],
            'left_on' => ['nullable', 'date'],
            'left_reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'photo' => Uploads::PHOTO_RULES,
        ]);

        if (blank($data['father_phone'] ?? null) && blank($data['guardian_phone'] ?? null) && blank($data['mother_phone'] ?? null)) {
            throw ValidationException::withMessages(['father_phone' => 'Add at least one parent or guardian phone number.']);
        }

        $data['class_id'] = Section::find($data['section_id'])->class_id;
        $data['status'] ??= 'active';
        $data['fee_discount'] = (float) ($data['fee_discount'] ?? 0);
        $data['left_on'] = $data['status'] === 'active' ? null : ($data['left_on'] ?? now()->toDateString());
        $data['left_reason'] = $data['status'] === 'active' ? null : ($data['left_reason'] ?? null);

        return collect($data)->except('photo')->all();
    }
}
