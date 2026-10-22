<?php

namespace App\Http\Controllers\School\Daily;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Daily staff attendance (principal / admin). Approved leave fills itself in. */
class StaffAttendanceController extends Controller
{
    public const STATUSES = ['present' => 'Present', 'absent' => 'Absent', 'late' => 'Late', 'leave' => 'Leave', 'half_day' => 'Half day'];

    public function index(Request $request): View
    {
        $date = rescue(fn () => Carbon::parse((string) $request->query('date', today()->toDateString()))->startOfDay(), today(), false);
        $date = $date->isFuture() ? today() : $date;

        $staff = Staff::query()->active()->orderBy('name')->get();
        $onLeave = LeaveRequest::query()->where('status', 'approved')
            ->whereDate('from_date', '<=', $date)->whereDate('to_date', '>=', $date)->pluck('staff_id')->flip();

        return view('school.attendance.staff', [
            'date' => $date,
            'staff' => $staff,
            'records' => StaffAttendance::query()->whereDate('date', $date)->get()->keyBy('staff_id'),
            'onLeave' => $onLeave,
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'status' => ['required', 'array'],
            'status.*' => ['required', Rule::in(array_keys(self::STATUSES))],
            'check_in' => ['array'],
            'check_in.*' => ['nullable', 'date_format:H:i'],
            'check_out' => ['array'],
            'check_out.*' => ['nullable', 'date_format:H:i'],
        ]);
        $date = Carbon::parse($data['date'])->toDateString();
        $ids = Staff::query()->active()->pluck('id')->all();
        $userId = $request->user('school')->id;

        DB::transaction(function () use ($data, $ids, $date, $userId) {
            foreach ($data['status'] as $staffId => $status) {
                if (! in_array((int) $staffId, $ids, true)) {
                    continue;
                }
                StaffAttendance::updateOrCreate(['staff_id' => (int) $staffId, 'date' => $date], [
                    'status' => $status,
                    'check_in' => $data['check_in'][$staffId] ?? null,
                    'check_out' => $data['check_out'][$staffId] ?? null,
                    'marked_by' => $userId,
                ]);
            }
        });

        Activity::log('attendance.staff', 'Staff attendance saved for '.Carbon::parse($date)->format('d M Y'));

        return redirect()->route('school.attendance.staff', ['date' => $date])->with('success', 'Staff attendance saved.');
    }
}
