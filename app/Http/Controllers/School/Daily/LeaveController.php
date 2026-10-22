<?php

namespace App\Http\Controllers\School\Daily;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\StaffAttendance;
use App\Support\Activity;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Staff apply for leave; the principal (or school admin) approves or rejects it.
 * Approved days are written into staff attendance as "leave".
 */
class LeaveController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user('school');
        $canApprove = $user->allows('approvals');
        $tab = $canApprove ? $request->query('tab', 'pending') : 'mine';

        $query = LeaveRequest::query()->with('staff', 'reviewer')->latest('from_date')->latest('id');
        $query = match ($tab) {
            'pending' => $query->where('status', 'pending'),
            'all' => $query,
            default => $query->where('staff_id', $user->staff?->id ?? 0),
        };

        return view('school.leave.index', [
            'requests' => $query->paginate(20)->withQueryString(),
            'tab' => $tab,
            'canApprove' => $canApprove,
            'staff' => $user->staff,
            'pending' => $canApprove ? LeaveRequest::query()->where('status', 'pending')->count() : 0,
            'onLeaveToday' => $canApprove ? LeaveRequest::query()->with('staff')->where('status', 'approved')->whereDate('from_date', '<=', today())->whereDate('to_date', '>=', today())->get() : collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $staff = $request->user('school')->staff;
        abort_unless($staff, 403, 'Your login is not linked to a staff record. Ask the school admin.');

        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(LeaveRequest::TYPES))],
            'from_date' => ['required', 'date', 'after_or_equal:'.today()->subDays(30)->toDateString()],
            'to_date' => ['required', 'date', 'after_or_equal:from_date', 'before_or_equal:'.today()->addYear()->toDateString()],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $from = Carbon::parse($data['from_date']);
        $to = Carbon::parse($data['to_date']);
        $overlap = LeaveRequest::query()->where('staff_id', $staff->id)->where('status', '!=', 'rejected')
            ->whereDate('from_date', '<=', $to)->whereDate('to_date', '>=', $from)->exists();
        if ($overlap) {
            return back()->withInput()->withErrors(['from_date' => 'You already applied for leave on some of these days.']);
        }

        $leave = LeaveRequest::create($data + ['staff_id' => $staff->id, 'days' => $from->diffInDays($to) + 1, 'status' => 'pending']);
        Activity::log('leave.requested', "{$staff->name} applied for {$leave->days} day(s) of leave", $leave);

        return back()->with('success', 'Leave request sent to the principal.');
    }

    public function review(Request $request, LeaveRequest $leave): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'review_note' => ['nullable', 'string', 'max:255'],
        ]);
        abort_if($leave->status !== 'pending', 422, 'This request was already answered.');
        abort_if($leave->staff_id === $request->user('school')->staff?->id, 403, 'Someone else must approve your own leave.');

        DB::transaction(function () use ($leave, $data, $request) {
            $leave->update($data + ['status' => $data['decision'], 'reviewed_by' => $request->user('school')->id, 'reviewed_at' => now()]);

            if ($data['decision'] === 'approved') {
                foreach (CarbonPeriod::create($leave->from_date, $leave->to_date) as $day) {
                    StaffAttendance::updateOrCreate(['staff_id' => $leave->staff_id, 'date' => $day->toDateString()], ['status' => 'leave', 'marked_by' => $request->user('school')->id]);
                }
            }
        });

        Activity::log('leave.'.$data['decision'], ucfirst($data['decision'])." leave of {$leave->staff->name} ({$leave->period()})", $leave);

        return back()->with('success', 'Leave '.$data['decision'].'.');
    }

    /** Staff can withdraw their own request while it is still pending. */
    public function destroy(Request $request, LeaveRequest $leave): RedirectResponse
    {
        abort_unless($leave->staff_id === $request->user('school')->staff?->id && $leave->status === 'pending', 403);
        $leave->delete();

        return back()->with('success', 'Leave request withdrawn.');
    }
}
