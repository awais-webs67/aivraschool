<?php

namespace App\Http\Controllers\School\Office;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Visitor;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Reception: visitor log and the complaints / suggestions register. */
class FrontOfficeController extends Controller
{
    public function visitors(Request $request): View
    {
        $date = rescue(fn () => Carbon::parse((string) $request->query('date', today()->toDateString())), today(), false);

        return view('school.office.visitors', [
            'date' => $date,
            'visitors' => Visitor::query()->whereDate('in_at', $date)->latest('in_at')->get(),
            'inside' => Visitor::query()->whereNull('out_at')->whereDate('in_at', today())->count(),
        ]);
    }

    public function storeVisitor(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'national_id' => ['nullable', 'string', 'max:40'],
            'purpose' => ['required', 'string', 'max:150'],
            'to_meet' => ['nullable', 'string', 'max:120'],
            'people' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        Visitor::create($data + ['people' => $data['people'] ?? 1, 'in_at' => now(), 'recorded_by' => $request->user('school')->id]);

        return back()->with('success', "{$data['name']} signed in.");
    }

    public function checkout(Visitor $visitor): RedirectResponse
    {
        $visitor->update(['out_at' => $visitor->out_at ?? now()]);

        return back()->with('success', "{$visitor->name} signed out.");
    }

    public function complaints(Request $request): View
    {
        $status = $request->query('status', 'open');

        return view('school.office.complaints', [
            'complaints' => Complaint::query()->with('student')
                ->when($status === 'open', fn ($q) => $q->where('status', '!=', 'resolved'))
                ->when(in_array($status, ['resolved'], true), fn ($q) => $q->where('status', 'resolved'))
                ->latest('id')->paginate(20)->withQueryString(),
            'status' => $status,
            'openCount' => Complaint::query()->where('status', '!=', 'resolved')->count(),
        ]);
    }

    public function storeComplaint(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(['complaint', 'suggestion'])],
            'from_name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'category' => ['nullable', 'string', 'max:40'],
            'details' => ['required', 'string', 'max:3000'],
        ]);
        $complaint = Complaint::create($data + ['status' => 'open', 'created_by' => $request->user('school')->id]);
        Activity::log('complaint.created', ucfirst($complaint->kind)." from {$complaint->from_name}", $complaint);

        return back()->with('success', ucfirst($complaint->kind).' recorded.');
    }

    public function updateComplaint(Request $request, Complaint $complaint): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Complaint::STATUSES))],
            'response' => ['nullable', 'string', 'max:3000'],
        ]);
        $complaint->update($data + ['resolved_at' => $data['status'] === 'resolved' ? ($complaint->resolved_at ?? now()) : null]);

        return back()->with('success', 'Updated.');
    }
}
