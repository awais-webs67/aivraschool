<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemoRequest;
use App\Support\Activity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Leads from the website demo form: a small CRM pipeline.
 */
class DemoRequestController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(DemoRequest::STATUSES)],
            'q' => ['nullable', 'string', 'max:100'],
            'follow_up' => ['nullable', Rule::in(['due'])],
        ]);

        return view('admin.demo-requests.index', [
            'requests' => self::filtered($filters)->latest()->paginate(20)->withQueryString(),
            'counts' => DemoRequest::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'dueCount' => self::filtered(['follow_up' => 'due'])->count(),
            'filters' => $filters,
        ]);
    }

    public static function filtered(array $filters): Builder
    {
        return DemoRequest::query()
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when(($filters['follow_up'] ?? null) === 'due', fn ($q) => $q->whereNotIn('status', ['converted', 'closed'])->whereDate('follow_up_on', '<=', today()))
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$term}%")->orWhere('school_name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")->orWhere('city', 'like', "%{$term}%")));
    }

    public function show(DemoRequest $demoRequest): View
    {
        return view('admin.demo-requests.show', ['lead' => $demoRequest->load('school')]);
    }

    public function update(Request $request, DemoRequest $demoRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(DemoRequest::STATUSES)],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'follow_up_on' => ['sometimes', 'nullable', 'date'],
        ]);

        $old = $demoRequest->status;
        $demoRequest->update($data);

        if ($old !== $demoRequest->status) {
            Activity::log('lead.status', "Lead {$demoRequest->school_name} moved to ".DemoRequest::STATUS_LABELS[$demoRequest->status], $demoRequest);
        }

        return back()->with('success', 'Lead updated.');
    }

    public function destroy(DemoRequest $demoRequest): RedirectResponse
    {
        $demoRequest->delete();
        Activity::log('lead.deleted', "Deleted lead {$demoRequest->school_name}");

        return redirect()->route('admin.demo-requests.index')->with('success', 'Lead deleted.');
    }
}
