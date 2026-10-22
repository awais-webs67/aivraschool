<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SchoolStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSchoolRequest;
use App\Http\Requests\Admin\UpdateSchoolRequest;
use App\Models\ActivityLog;
use App\Models\DemoRequest;
use App\Models\Plan;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\SchoolProvisioner;
use App\Support\Activity;
use App\Support\Uploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SchoolController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(SchoolStatus::class)],
            'plan' => ['nullable', 'integer'],
        ]);

        $schools = School::query()
            ->with('plan')
            ->when($filters['q'] ?? null, function ($query, $q) {
                $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%"));
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['plan'] ?? null, fn ($query, $plan) => $query->where('plan_id', $plan))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.schools.index', [
            'schools' => $schools,
            'plans' => Plan::query()->ordered()->get(),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): View
    {
        // "Convert to school" from a lead: start the form with the lead's details.
        $lead = $request->integer('lead') ? DemoRequest::find($request->integer('lead')) : null;

        if ($lead && ! $request->session()->hasOldInput()) {
            $request->session()->now('_old_input', [
                'name' => $lead->school_name,
                'slug' => Str::slug(Str::limit($lead->school_name, 40, '')),
                'principal_name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'city' => $lead->city,
                'admin_name' => $lead->name,
                'admin_email' => $lead->email,
                'student_count' => $lead->students,
            ]);
        }

        return view('admin.schools.create', [
            'plans' => Plan::query()->active()->ordered()->get(),
            'lead' => $lead,
        ]);
    }

    public function store(StoreSchoolRequest $request, SchoolProvisioner $provisioner): RedirectResponse
    {
        $data = $request->validated();
        $data['photo_path'] = Uploads::store($request->file('school_photo'), 'schools');
        $data['principal_photo_path'] = Uploads::store($request->file('principal_photo'), 'schools');
        $plan = Plan::findOrFail($data['plan_id']);
        $password = ($data['admin_password'] ?? null) ?: Str::password(12, symbols: false);

        $school = $provisioner->create(
            school: $data,
            plan: $plan,
            admin: ['name' => $data['admin_name'], 'email' => $data['admin_email'], 'password' => $password],
            billing: [
                'mode' => $data['billing_mode'],
                'trial_days' => $data['trial_days'] ?? null,
                'starts_at' => $data['starts_at'] ?? null,
                'student_count' => $data['student_count'] ?? null,
                'amount' => $data['amount'] ?? null,
                'payment_status' => $data['payment_status'] ?? 'unpaid',
            ],
            createdBy: $request->user()->id,
        );

        if (! empty($data['lead_id'])) {
            DemoRequest::whereKey($data['lead_id'])->update(['status' => 'converted', 'school_id' => $school->id]);
        }

        Activity::log('school.created', "Added school {$school->name} on {$plan->name}", $school);

        return redirect()->route('admin.schools.show', $school)
            ->with('success', "{$school->name} is ready.")
            ->with('credentials', [
                'title' => 'School login credentials',
                'url' => $school->url('login'),
                'email' => $data['admin_email'],
                'password' => $password,
            ]);
    }

    public function show(School $school): View
    {
        $school->load(['plan', 'subscriptions.creator']);

        $admins = $school->users()->where('role', 'admin')->orderBy('id')->get();

        $usage = $school->run(fn () => [
            'Users' => User::query()->count(),
            'Classes' => SchoolClass::query()->count(),
            'Sections' => Section::query()->count(),
            'Subjects' => Subject::query()->count(),
        ]);

        return view('admin.schools.show', [
            'school' => $school,
            'admins' => $admins,
            'plans' => Plan::query()->active()->ordered()->get(),
            'usage' => $usage,
            'lastLogin' => $school->users()->max('last_login_at'),
            'tickets' => SupportTicket::query()->withoutGlobalScopes()->where('school_id', $school->id)->latest('id')->limit(5)->get(),
            'activity' => ActivityLog::query()->where('school_id', $school->id)->latest('id')->limit(8)->get(),
        ]);
    }

    public function edit(School $school): View
    {
        return view('admin.schools.edit', ['school' => $school]);
    }

    public function update(UpdateSchoolRequest $request, School $school): RedirectResponse
    {
        $data = $request->validated();
        $data['photo_path'] = Uploads::replace($request->file('school_photo'), $school->photo_path, 'schools');
        $data['principal_photo_path'] = Uploads::replace($request->file('principal_photo'), $school->principal_photo_path, 'schools');
        unset($data['school_photo'], $data['principal_photo']);

        $school->update($data);
        Activity::log('school.updated', "Updated details of {$school->name}", $school);

        return redirect()->route('admin.schools.show', $school)->with('success', 'School details updated.');
    }

    public function destroy(Request $request, School $school): RedirectResponse
    {
        $request->validate([
            'confirm_slug' => ['required', Rule::in([$school->slug])],
        ], [
            'confirm_slug.in' => 'Type the exact school address to confirm deletion.',
        ]);

        $name = $school->name;
        $school->delete(); // cascades to the school's users and subscriptions
        Activity::log('school.deleted', "Deleted school {$name} and all of its data");

        return redirect()->route('admin.schools.index')->with('success', "{$name} and all of its data were deleted.");
    }

    public function suspend(Request $request, School $school): RedirectResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        $school->update([
            'status' => SchoolStatus::Suspended,
            'suspended_at' => now(),
            'suspension_reason' => $data['reason'] ?? null,
        ]);
        Activity::log('school.suspended', "Suspended {$school->name}".(! empty($data['reason']) ? ": {$data['reason']}" : ''), $school);

        return back()->with('success', "{$school->name} has been suspended.");
    }

    public function activate(School $school): RedirectResponse
    {
        $status = $school->subscriptions()->exists() ? SchoolStatus::Active : SchoolStatus::Trial;

        $school->update([
            'status' => $status,
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        Activity::log('school.activated', "Reactivated {$school->name}", $school);

        $message = $school->canAccess()
            ? "{$school->name} has been reactivated."
            : "{$school->name} was reactivated but its access period has ended. Add a subscription to restore access.";

        return back()->with('success', $message);
    }

    public function extendTrial(Request $request, School $school): RedirectResponse
    {
        $data = $request->validate(['days' => ['required', 'integer', 'min:1', 'max:365']]);

        $from = $school->trial_ends_at && $school->trial_ends_at->isFuture() ? $school->trial_ends_at : now();

        $school->update([
            'status' => SchoolStatus::Trial,
            'trial_ends_at' => $from->copy()->addDays($data['days'])->endOfDay(),
        ]);

        Activity::log('school.trial', "Extended {$school->name}'s trial by {$data['days']} days", $school);

        return back()->with('success', "Trial extended by {$data['days']} days.");
    }

    /** School or principal photo (stored privately). */
    public function photo(School $school, string $kind): Response
    {
        $path = $kind === 'principal' ? $school->principal_photo_path : $school->photo_path;

        abort_unless($path && Storage::exists($path), 404);

        return Storage::response($path);
    }

    public function resetAdminPassword(Request $request, School $school): RedirectResponse
    {
        $data = $request->validate(['user_id' => ['required', 'integer']]);
        $password = Str::password(12, symbols: false);

        $user = $school->users()->findOrFail($data['user_id']);
        $user->update(['password' => $password]);
        $email = $user->email;
        Activity::log('school.password', "Reset password for {$email} at {$school->name}", $school);

        return back()->with('credentials', [
            'title' => 'School login credentials',
            'url' => $school->url('login'),
            'email' => $email,
            'password' => $password,
        ])->with('success', 'A new password was generated.');
    }
}
