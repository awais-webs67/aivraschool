<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SchoolStatus;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\School;
use App\Models\Subscription;
use App\Support\MonthlySeries;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $months = (int) ($request->validate(['months' => ['nullable', 'in:6,12,24']])['months'] ?? 12);

        $collected = MonthlySeries::sum(Subscription::query()->where('payment_status', 'paid'), 'paid_at', 'amount', $months);
        $invoiced = MonthlySeries::sum(Subscription::query(), 'created_at', 'amount', $months);
        $newSchools = MonthlySeries::count(School::query(), 'created_at', $months);

        $rows = [];
        foreach ($collected as $i => $point) {
            $rows[] = ['label' => Carbon::createFromFormat('Y-m', $point['key'])->format('M Y'), 'collected' => $point['value'], 'invoiced' => $invoiced[$i]['value'], 'schools' => (int) $newSchools[$i]['value']];
        }

        $byPlan = Plan::query()->ordered()->get()->map(function (Plan $plan) {
            $subs = Subscription::query()->where('plan_id', $plan->id);

            return [
                'plan' => $plan,
                'schools' => School::query()->where('plan_id', $plan->id)->whereIn('status', [SchoolStatus::Active->value, SchoolStatus::Trial->value])->count(),
                'revenue' => (float) (clone $subs)->where('payment_status', 'paid')->sum('amount'),
                'outstanding' => (float) (clone $subs)->where('payment_status', 'unpaid')->sum('amount'),
            ];
        });

        return view('admin.reports', [
            'months' => $months,
            'collected' => $collected,
            'newSchools' => $newSchools,
            'rows' => array_reverse($rows),
            'byPlan' => $byPlan,
            'outstanding' => Subscription::query()->with('school')->where('payment_status', 'unpaid')->orderBy('starts_at')->limit(10)->get(),
            'totals' => [
                'collected' => array_sum(array_column($collected, 'value')),
                'invoiced' => array_sum(array_column($invoiced, 'value')),
                'schools' => (int) array_sum(array_column($newSchools, 'value')),
                'lifetime' => (float) Subscription::query()->where('payment_status', 'paid')->sum('amount'),
            ],
        ]);
    }
}
