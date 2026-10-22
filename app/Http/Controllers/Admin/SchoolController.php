<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SchoolStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSchoolRequest;
use App\Http\Requests\Admin\UpdateSchoolRequest;
use App\Models\Plan;
use App\Models\School;
use App\Services\SchoolProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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

    public function create(): View
    {
        return view('admin.schools.create', [
            'plans' => Plan::query()->active()->ordered()->get(),
        ]);
    }

    public function store(StoreSchoolRequest $request, SchoolProvisioner $provisioner): RedirectResponse
    {
        $data = $request->validated();
        $plan = Plan::findOrFail($data['plan_id']);
        $password = $data['admin_password'] ?: Str::password(12, symbols: false);

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

        return redirect()->route('admin.schools.show', $school)
            ->with('success', "{$school->name} is ready.")
            ->with('credentials', [
                'url' => $school->url('login'),
                'email' => $data['admin_email'],
                'password' => $password,
            ]);
    }

    public function show(School $school): View
    {
        $school->load(['plan', 'subscriptions.creator']);

        $admins = $school->users()->where('role', 'admin')->orderBy('id')->get();

        return view('admin.schools.show', [
            'school' => $school,
            'admins' => $admins,
            'plans' => Plan::query()->active()->ordered()->get(),
        ]);
    }

    public function edit(School $school): View
    {
        return view('admin.schools.edit', ['school' => $school]);
    }

    public function update(UpdateSchoolRequest $request, School $school): RedirectResponse
    {
        $school->update($request->validated());

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

        return back()->with('success', "Trial extended by {$data['days']} days.");
    }

    public function resetAdminPassword(Request $request, School $school): RedirectResponse
    {
        $data = $request->validate(['user_id' => ['required', 'integer']]);
        $password = Str::password(12, symbols: false);

        $user = $school->users()->findOrFail($data['user_id']);
        $user->update(['password' => $password]);
        $email = $user->email;

        return back()->with('credentials', [
            'url' => $school->url('login'),
            'email' => $email,
            'password' => $password,
        ])->with('success', 'A new password was generated.');
    }
}
