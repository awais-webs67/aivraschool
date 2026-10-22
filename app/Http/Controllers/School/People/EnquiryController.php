<?php

namespace App\Http\Controllers\School\People;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\SchoolClass;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admission enquiries: quick entry at the front desk, follow-ups, and the public online form.
 */
class EnquiryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in([...array_keys(Enquiry::STATUSES), 'open', 'all'])],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $status = $filters['status'] ?? 'open';

        return view('school.enquiries.index', [
            'enquiries' => Enquiry::query()->with('schoolClass')
                ->when($status === 'open', fn ($q) => $q->whereNotIn('status', ['admitted', 'lost']))
                ->when(! in_array($status, ['open', 'all'], true), fn ($q) => $q->where('status', $status))
                ->when($filters['q'] ?? null, fn ($q, $t) => $q->where(fn ($q) => $q->where('child_name', 'like', "%{$t}%")->orWhere('parent_name', 'like', "%{$t}%")->orWhere('phone', 'like', "%{$t}%")))
                ->orderByRaw('follow_up_on IS NULL, follow_up_on')->latest('id')
                ->paginate(25)->withQueryString(),
            'status' => $status,
            'filters' => $filters,
            'counts' => Enquiry::query()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status'),
            'dueToday' => Enquiry::query()->whereNotIn('status', ['admitted', 'lost'])->whereDate('follow_up_on', '<=', today())->count(),
            'classes' => SchoolClass::query()->ordered()->pluck('name', 'id'),
            'applyUrl' => route('school.apply'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $enquiry = Enquiry::create($this->validated($request));
        Activity::log('enquiry.created', "New admission enquiry for {$enquiry->child_name}", $enquiry);

        return back()->with('success', "Enquiry for {$enquiry->child_name} saved.");
    }

    public function update(Request $request, Enquiry $enquiry): RedirectResponse
    {
        $enquiry->update($this->validated($request, $enquiry));

        return back()->with('success', 'Enquiry updated.');
    }

    public function destroy(Enquiry $enquiry): RedirectResponse
    {
        $enquiry->delete();

        return back()->with('success', 'Enquiry deleted.');
    }

    /** Public "Apply for admission" page at /{school}/apply. */
    public function apply(): View
    {
        return view('school.enquiries.apply', ['school' => current_school(), 'classes' => SchoolClass::query()->ordered()->pluck('name', 'id')]);
    }

    public function submit(Request $request): RedirectResponse
    {
        // Bots fill the hidden "website" field; people never see it.
        if (filled($request->input('website'))) {
            return back()->with('success', 'Thank you! The school will contact you soon.');
        }

        $key = 'apply:'.current_school()->id.'|'.$request->ip();
        abort_if(RateLimiter::tooManyAttempts($key, 5), 429, 'Too many applications from this network. Please try again later.');
        RateLimiter::hit($key, 3600);

        $data = $this->validated($request, public: true);
        Enquiry::create($data + ['source' => 'website', 'status' => 'new']);

        return back()->with('success', 'Thank you! Your application was sent. The school will contact you on '.$data['phone'].'.');
    }

    protected function validated(Request $request, ?Enquiry $enquiry = null, bool $public = false): array
    {
        $data = $request->validate([
            'child_name' => ['required', 'string', 'max:120'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'class_id' => ['nullable', Rule::exists('classes', 'id')->where('school_id', current_school()->id)],
            'parent_name' => [$public ? 'required' : 'nullable', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'previous_school' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'source' => ['nullable', Rule::in(array_keys(Enquiry::SOURCES))],
            'status' => ['nullable', Rule::in(array_keys(Enquiry::STATUSES))],
            'follow_up_on' => ['nullable', 'date'],
        ]);

        if ($public) {
            return collect($data)->except(['source', 'status', 'follow_up_on'])->all();
        }

        $data['source'] ??= $enquiry?->source ?? 'walk_in';
        $data['status'] ??= $enquiry?->status ?? 'new';

        return $data;
    }
}
