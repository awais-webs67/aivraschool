<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\School;
use App\Models\SchoolRegistration;
use App\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Website sign-ups waiting for the Super Admin: review, edit, approve or reject.
 */
class RegistrationController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(SchoolRegistration::STATUSES))],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return view('admin.registrations.index', [
            'registrations' => SchoolRegistration::query()->with('plan')
                ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
                ->when($filters['q'] ?? null, fn ($q, $t) => $q->where(fn ($q) => $q->where('school_name', 'like', "%{$t}%")
                    ->orWhere('principal_name', 'like', "%{$t}%")->orWhere('principal_email', 'like', "%{$t}%")->orWhere('city', 'like', "%{$t}%")))
                ->latest()->paginate(20)->withQueryString(),
            'counts' => SchoolRegistration::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'filters' => $filters,
        ]);
    }

    public function show(SchoolRegistration $registration): View
    {
        return view('admin.registrations.show', [
            'r' => $registration->load(['plan', 'school', 'reviewer']),
            'plans' => Plan::query()->active()->ordered()->pluck('name', 'id'),
            'addressFree' => $registration->isPending() ? ! School::where('slug', $registration->slug)->exists() : true,
        ]);
    }

    /** Fix details before approving (address taken, wrong plan…). */
    public function update(Request $request, SchoolRegistration $registration): RedirectResponse
    {
        abort_unless($registration->isPending(), 403);

        $request->merge(['slug' => strtolower(trim((string) $request->input('slug')))]);
        $data = $request->validate([
            'slug' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', Rule::notIn(config('skooline.reserved_slugs')), Rule::unique('schools', 'slug')],
            'plan_id' => ['required', 'exists:plans,id'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $registration->update($data);

        return back()->with('success', 'Registration updated.');
    }

    public function approve(Request $request, SchoolRegistration $registration, RegistrationService $service): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(['trial', 'paid', 'unpaid'])],
            'trial_days' => ['nullable', 'required_if:mode,trial', 'integer', 'min:1', 'max:365'],
        ]);

        $school = $service->approve($registration, $data['mode'], $data['trial_days'] ?? null, $request->user()->id);

        return redirect()->route('admin.schools.show', $school)->with('success', "{$school->name} is live. The principal was emailed their login link.");
    }

    public function reject(Request $request, SchoolRegistration $registration, RegistrationService $service): RedirectResponse
    {
        abort_unless($registration->isPending(), 403);

        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);
        $service->reject($registration, $data['reason'] ?? null, $request->user()->id);

        return redirect()->route('admin.registrations.index')->with('success', "Registration of {$registration->school_name} rejected.");
    }

    /** School or principal photo (stored privately). */
    public function photo(SchoolRegistration $registration, string $kind): Response
    {
        $path = match ($kind) {
            'school' => $registration->school_photo_path,
            'principal' => $registration->principal_photo_path,
            default => null,
        };

        abort_unless($path && Storage::exists($path), 404);

        return Storage::response($path);
    }
}
