<?php

namespace App\Http\Controllers\School\Academics;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\Substitution;
use App\Models\TimetableEntry;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Covering absent teachers: lists their lessons for a day and the teachers who are free then.
 */
class SubstitutionController extends Controller
{
    public function index(Request $request): View
    {
        $date = rescue(fn () => Carbon::parse((string) $request->query('date', today()->toDateString()))->startOfDay(), today(), false);
        $absent = $this->absentStaffIds($date);

        $lessons = TimetableEntry::query()->with('period', 'section.schoolClass', 'subject', 'staff')
            ->where('day', $date->dayOfWeekIso)->whereIn('staff_id', $absent)->get()
            ->sortBy(fn ($e) => [(string) $e->period?->starts_at, $e->section?->label()])->values();

        $existing = Substitution::query()->whereDate('date', $date)->get()->keyBy('timetable_entry_id');

        return view('school.timetable.substitutions', [
            'date' => $date,
            'lessons' => $lessons,
            'existing' => $existing,
            'free' => $lessons->mapWithKeys(fn ($e) => [$e->id => $this->freeTeachers($e, $date, $absent, $existing)]),
            'absentStaff' => Staff::query()->whereIn('id', $absent)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'sub' => ['array'],
            'sub.*' => ['nullable', Rule::exists('staff', 'id')->where('school_id', current_school()->id)],
        ]);
        $date = Carbon::parse($data['date'])->toDateString();
        $entries = TimetableEntry::query()->whereIn('id', array_keys($data['sub'] ?? []))->get()->keyBy('id');

        DB::transaction(function () use ($data, $date, $entries, $request) {
            foreach ($data['sub'] ?? [] as $entryId => $staffId) {
                $entry = $entries[$entryId] ?? null;
                if (! $entry) {
                    continue;
                }
                $staffId
                    ? Substitution::updateOrCreate(['timetable_entry_id' => $entry->id, 'date' => $date], ['absent_staff_id' => $entry->staff_id, 'substitute_staff_id' => $staffId, 'created_by' => $request->user('school')->id])
                    : Substitution::query()->where('timetable_entry_id', $entry->id)->whereDate('date', $date)->delete();
            }
        });

        Activity::log('timetable.substitution', 'Arranged substitute teachers for '.Carbon::parse($date)->format('d M Y'));

        return back()->with('success', 'Substitutions saved.');
    }

    /** Staff marked absent or on leave that day, or with approved leave. */
    protected function absentStaffIds(Carbon $date): array
    {
        return StaffAttendance::query()->whereDate('date', $date)->whereIn('status', ['absent', 'leave'])->pluck('staff_id')
            ->merge(LeaveRequest::query()->where('status', 'approved')->whereDate('from_date', '<=', $date)->whereDate('to_date', '>=', $date)->pluck('staff_id'))
            ->unique()->values()->all();
    }

    /** Teachers present that day with no lesson and no other cover in that period. */
    protected function freeTeachers(TimetableEntry $entry, Carbon $date, array $absent, Collection $existing): Collection
    {
        $busy = TimetableEntry::query()->where('day', $entry->day)->where('period_id', $entry->period_id)->whereNotNull('staff_id')->pluck('staff_id')
            ->merge(Substitution::query()->whereDate('date', $date)->where('timetable_entry_id', '!=', $entry->id)
                ->whereHas('entry', fn ($q) => $q->where('period_id', $entry->period_id))->pluck('substitute_staff_id'))
            ->merge($absent)->unique()->all();

        return Staff::query()->active()->whereNotIn('id', $busy)->orderBy('name')->pluck('name', 'id');
    }
}
