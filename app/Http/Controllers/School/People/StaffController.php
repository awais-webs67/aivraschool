<?php

namespace App\Http\Controllers\School\People;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Staff;
use App\Models\User;
use App\Support\Activity;
use App\Support\Uploads;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Staff records and their portal logins (School Admin and Principal).
 */
class StaffController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', Rule::in(['active', 'left', 'all'])],
        ]);

        return view('school.staff.index', [
            'staff' => $this->filtered($filters)->with('user')->orderBy('name')->paginate(25)->withQueryString(),
            'filters' => $filters,
            'departments' => Staff::query()->whereNotNull('department')->distinct()->orderBy('department')->pluck('department'),
            'counts' => [
                'active' => Staff::query()->active()->count(),
                'teaching' => Staff::query()->active()->where('department', 'Teaching')->count(),
                'logins' => Staff::query()->active()->whereHas('user', fn ($q) => $q->where('is_active', true))->count(),
                'left' => Staff::query()->where('status', 'left')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('school.staff.form', ['member' => new Staff(['status' => 'active', 'employee_no' => Staff::nextEmployeeNo()]), 'roles' => $this->assignableRoles()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $member = DB::transaction(function () use ($request, $data) {
            $member = Staff::create($data['staff'] + ['photo_path' => Uploads::store($request->file('photo'), 'staff')]);
            $this->syncLogin($member, $data['login']);

            return $member;
        });

        Activity::log('staff.created', "Added staff member {$member->name} ({$member->employee_no})", $member);

        return redirect()->route('school.staff.show', $member)->with('success', "{$member->name} added.".($member->user ? ' They can sign in with '.$member->user->email.'.' : ''));
    }

    public function show(Staff $member): View
    {
        return view('school.staff.show', ['member' => $member->load('user', 'classSections.schoolClass')]);
    }

    public function edit(Staff $member): View
    {
        return view('school.staff.form', ['member' => $member->load('user'), 'roles' => $this->assignableRoles()]);
    }

    public function update(Request $request, Staff $member): RedirectResponse
    {
        $data = $this->validated($request, $member);

        DB::transaction(function () use ($request, $member, $data) {
            $member->update($data['staff'] + ['photo_path' => Uploads::replace($request->file('photo'), $member->photo_path, 'staff')]);
            $this->syncLogin($member, $data['login']);
        });

        Activity::log('staff.updated', "Updated staff member {$member->name}", $member);

        return redirect()->route('school.staff.show', $member)->with('success', 'Saved.');
    }

    public function destroy(Request $request, Staff $member): RedirectResponse
    {
        abort_if($member->user_id && $member->user_id === $request->user('school')->id, 422, 'You cannot delete your own record.');

        DB::transaction(function () use ($member) {
            $member->user?->delete();
            if ($member->photo_path) {
                Storage::delete($member->photo_path);
            }
            $member->delete();
        });

        Activity::log('staff.deleted', "Deleted staff member {$member->name} ({$member->employee_no})");

        return redirect()->route('school.staff.index')->with('success', "{$member->name} deleted.");
    }

    public function photo(Staff $member): Response
    {
        abort_unless($member->photo_path && Storage::exists($member->photo_path), 404);

        return Storage::response($member->photo_path, null, ['Cache-Control' => 'private, max-age=86400']);
    }

    public function export(Request $request): StreamedResponse
    {
        $rows = $this->filtered($request->only(['q', 'department', 'status']))->with('user')->orderBy('name');

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Employee no', 'Name', 'Designation', 'Department', 'Phone', 'Email', 'Joining date', 'Salary', 'Status', 'Login role']);
            $rows->each(fn (Staff $s) => fputcsv($out, [
                $s->employee_no, $s->name, $s->designation, $s->department, $s->phone, $s->email,
                $s->joining_date?->toDateString(), $s->salary, $s->status, $s->user?->is_active ? $s->user->roleLabel() : '',
            ]));
            fclose($out);
        }, 'staff-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    /** Pick the class teacher of every section on one screen. */
    public function classTeachers(): View
    {
        return view('school.staff.class-teachers', [
            'sections' => Section::query()->with('schoolClass')->get()->sortBy(fn ($s) => [$s->schoolClass?->sort_order, $s->name])->values(),
            'teachers' => Staff::query()->active()->orderBy('name')->get()->mapWithKeys(fn ($s) => [$s->id => $s->name.($s->designation ? ' · '.$s->designation : '')]),
        ]);
    }

    public function saveClassTeachers(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'teacher' => ['array'],
            'teacher.*' => ['nullable', Rule::exists('staff', 'id')->where('school_id', current_school()->id)],
        ]);

        foreach (Section::query()->get() as $section) {
            $section->update(['class_teacher_id' => $data['teacher'][$section->id] ?? null]);
        }
        Activity::log('staff.class_teachers', 'Updated class teachers');

        return back()->with('success', 'Class teachers saved.');
    }

    protected function filtered(array $filters): Builder
    {
        $status = $filters['status'] ?? 'active';

        return Staff::query()
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($filters['department'] ?? null, fn ($q, $d) => $q->where('department', $d))
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$term}%")->orWhere('employee_no', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")
                ->orWhere('designation', 'like', "%{$term}%")));
    }

    /** Only a School Admin can make another School Admin. */
    protected function assignableRoles(): array
    {
        return request()->user('school')->role === 'admin' ? User::ROLES : array_diff_key(User::ROLES, ['admin' => true]);
    }

    /** @return array{staff: array<string, mixed>, login: array<string, mixed>} */
    protected function validated(Request $request, ?Staff $member = null): array
    {
        $schoolId = current_school()->id;
        $login = $request->boolean('login');
        $userId = $member?->user_id;
        $isSelf = $userId && $userId === $request->user('school')->id;

        $data = $request->validate([
            'employee_no' => [$member ? 'required' : 'nullable', 'string', 'max:30', Rule::unique('staff')->where('school_id', $schoolId)->ignore($member)],
            'name' => ['required', 'string', 'max:120'],
            'father_name' => ['nullable', 'string', 'max:120'],
            'gender' => ['nullable', Rule::in(array_keys(Staff::GENDERS))],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'national_id' => ['nullable', 'string', 'max:40'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => [$login ? 'required' : 'nullable', 'email', 'max:150',
                Rule::unique('users', 'email')->where('school_id', $schoolId)->ignore($userId)],
            'address' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:80'],
            'department' => ['nullable', 'string', 'max:80'],
            'qualification' => ['nullable', 'string', 'max:150'],
            'joining_date' => ['nullable', 'date'],
            'salary' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'status' => ['required', Rule::in(['active', 'left'])],
            'left_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'photo' => Uploads::PHOTO_RULES,
            'role' => [$login ? 'required' : 'nullable', Rule::in(array_keys($this->assignableRoles()))],
            // A new login needs a password; an existing one keeps its password when left blank.
            'password' => [$login && ! $member?->user ? 'required' : 'nullable', Password::min(8)->letters()->numbers()],
        ], ['email.unique' => 'Another portal login already uses this email.']);

        // Nobody can lock themselves out or change their own role.
        if ($isSelf && (! $login || $data['status'] === 'left' || ($data['role'] ?? null) !== $member->user->role)) {
            throw ValidationException::withMessages(['role' => 'You cannot remove or change your own login. Ask another admin.']);
        }

        $staff = collect($data)->except(['photo', 'role', 'password'])->all();
        $staff['email'] = $staff['email'] ? Str::lower($staff['email']) : null;
        $staff['left_on'] = $data['status'] === 'left' ? ($data['left_on'] ?? now()->toDateString()) : null;

        return [
            'staff' => $staff,
            // Someone who left can't sign in.
            'login' => ['enabled' => $login && $data['status'] === 'active', 'role' => $data['role'] ?? null, 'password' => $data['password'] ?? null],
        ];
    }

    /** Creates, updates or switches off the portal login linked to a staff member. */
    protected function syncLogin(Staff $member, array $login): void
    {
        $user = $member->user;

        if (! $login['enabled']) {
            $user?->update(['is_active' => false]);

            return;
        }

        $fields = ['name' => $member->name, 'email' => $member->email, 'phone' => $member->phone, 'role' => $login['role'], 'is_active' => true];
        if ($login['password']) {
            $fields['password'] = $login['password'];
        }

        if ($user) {
            $user->update($fields);
        } else {
            $member->update(['user_id' => User::create($fields)->id]);
        }
    }
}
