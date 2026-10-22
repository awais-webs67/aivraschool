<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\FeePayment;
use App\Models\Section;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\StudentAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * School reports: strength, attendance, fee collection and staff attendance, each viewable and as CSV.
 */
class ReportController extends Controller
{
    public const REPORTS = [
        'strength' => ['Student strength', 'Students per class, boys and girls.', 'users'],
        'attendance' => ['Attendance by class', 'Average attendance and students below 75% for a month.', 'check-circle'],
        'fees' => ['Fee collection', 'Money received each day of a month, by payment method.', 'banknotes'],
        'staff' => ['Staff attendance', 'Present, absent, late and leave days per staff member.', 'briefcase'],
    ];

    public function index(): View
    {
        return view('school.reports.index', ['reports' => self::REPORTS]);
    }

    public function show(Request $request, string $report): View|StreamedResponse
    {
        abort_unless(isset(self::REPORTS[$report]), 404);
        $month = rescue(fn () => Carbon::createFromFormat('!Y-m', (string) $request->query('month', today()->format('Y-m'))), today()->startOfMonth(), false);
        [$columns, $rows] = $this->{$report}($month);

        if ($request->query('export')) {
            return response()->streamDownload(function () use ($columns, $rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, $columns);
                foreach ($rows as $row) {
                    fputcsv($out, $row);
                }
                fclose($out);
            }, "{$report}-report-".$month->format('Y-m').'.csv', ['Content-Type' => 'text/csv']);
        }

        return view('school.reports.show', ['key' => $report, 'report' => self::REPORTS[$report], 'month' => $month, 'columns' => $columns, 'rows' => $rows]);
    }

    protected function strength(Carbon $month): array
    {
        $rows = Section::query()->with('schoolClass')->withCount([
            'students as boys' => fn ($q) => $q->where('status', 'active')->where('gender', 'male'),
            'students as girls' => fn ($q) => $q->where('status', 'active')->where('gender', 'female'),
            'students as total' => fn ($q) => $q->where('status', 'active'),
        ])->get()->sortBy(fn ($s) => [$s->schoolClass?->sort_order, $s->name])
            ->map(fn ($s) => [$s->label(), $s->boys, $s->girls, $s->total])->values();

        return [['Class', 'Boys', 'Girls', 'Total'], $rows->push(['Total', $rows->sum(1), $rows->sum(2), $rows->sum(3)])];
    }

    protected function attendance(Carbon $month): array
    {
        $records = StudentAttendance::query()->whereBetween('date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])->get();
        $rows = Section::query()->with('schoolClass')->get()->sortBy(fn ($s) => [$s->schoolClass?->sort_order, $s->name])
            ->map(function ($s) use ($records) {
                $mine = $records->where('section_id', $s->id);
                $byStudent = $mine->groupBy('student_id')->map(fn ($r) => $r->whereIn('status', StudentAttendance::ATTENDED)->count() / max(1, $r->count()) * 100);

                return [$s->label(), $mine->pluck('date')->unique()->count(), $mine->count() ? round($mine->whereIn('status', StudentAttendance::ATTENDED)->count() / $mine->count() * 100, 1) : '—', $byStudent->filter(fn ($p) => $p < 75)->count()];
            })->values();

        return [['Class', 'Days marked', 'Average %', 'Students below 75%'], $rows];
    }

    protected function fees(Carbon $month): array
    {
        $payments = FeePayment::query()->whereBetween('paid_on', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])->get();
        $methods = array_keys(FeePayment::METHODS);
        $rows = $payments->groupBy(fn ($p) => $p->paid_on->toDateString())->sortKeys()
            ->map(fn (Collection $day, $date) => [Carbon::parse($date)->format('d M Y'), ...array_map(fn ($m) => round($day->where('method', $m)->sum('amount'), 2), $methods), round($day->sum('amount'), 2)])
            ->values();
        $rows->push(['Total', ...array_map(fn ($m) => round($payments->where('method', $m)->sum('amount'), 2), $methods), round($payments->sum('amount'), 2)]);

        return [['Date', ...array_values(FeePayment::METHODS), 'Total'], $rows];
    }

    protected function staff(Carbon $month): array
    {
        $records = StaffAttendance::query()->whereBetween('date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])->get()->groupBy('staff_id');
        $rows = Staff::query()->active()->orderBy('name')->get()->map(fn ($s) => [
            $s->name, ...array_map(fn ($status) => ($records[$s->id] ?? collect())->where('status', $status)->count(), ['present', 'late', 'half_day', 'absent', 'leave']),
        ]);

        return [['Staff', 'Present', 'Late', 'Half day', 'Absent', 'Leave'], $rows];
    }
}
