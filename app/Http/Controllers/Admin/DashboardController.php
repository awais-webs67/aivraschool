<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SchoolStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DemoRequest;
use App\Models\Plan;
use App\Models\School;
use App\Models\Subscription;
use App\Models\SupportTicket;
use App\Services\SubscriptionManager;
use App\Support\MonthlySeries;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(SubscriptionManager $subscriptions): View
    {
        $counts = School::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $expiringSoon = School::query()
            ->with('plan')
            ->where(function ($q) {
                // Includes schools already in their grace period, which need renewing first.
                $q->where(fn ($q) => $q->where('status', SchoolStatus::Active->value)->whereBetween('subscription_ends_at', [now()->subDays(config('skooline.grace_days')), now()->addDays(30)]))
                    ->orWhere(fn ($q) => $q->where('status', SchoolStatus::Trial->value)->whereBetween('trial_ends_at', [now(), now()->addDays(30)]));
            })
            ->orderByRaw('coalesce(subscription_ends_at, trial_ends_at)')
            ->limit(8)
            ->get();

        $revenue = MonthlySeries::sum(Subscription::query()->where('payment_status', 'paid'), 'paid_at', 'amount', 12);
        $growth = MonthlySeries::count(School::query(), 'created_at', 12);

        $planMix = Plan::query()->withCount(['schools' => fn ($q) => $q->whereIn('status', [SchoolStatus::Active->value, SchoolStatus::Trial->value])])
            ->ordered()->get()->filter(fn ($p) => $p->schools_count > 0)->values();

        $trialsStarted = School::query()->where('created_at', '>=', now()->subDays(90))->count();
        $converted = School::query()->where('created_at', '>=', now()->subDays(90))->whereHas('subscriptions')->count();

        return view('admin.dashboard', [
            'total' => $counts->sum(),
            'counts' => $counts,
            'mrr' => $subscriptions->monthlyRecurringRevenue(),
            'collectedThisMonth' => (float) Subscription::query()->where('payment_status', 'paid')->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
            'collectedLastMonth' => (float) Subscription::query()->where('payment_status', 'paid')->whereBetween('paid_at', [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()])->sum('amount'),
            'unpaid' => (float) Subscription::query()->where('payment_status', 'unpaid')->sum('amount'),
            'unpaidCount' => Subscription::query()->where('payment_status', 'unpaid')->count(),
            'newDemoRequests' => DemoRequest::query()->where('status', 'new')->count(),
            'openTickets' => SupportTicket::query()->withoutGlobalScopes()->where('status', 'open')->count(),
            'conversion' => $trialsStarted ? round($converted / $trialsStarted * 100) : null,
            'expiringSoon' => $expiringSoon,
            'recentSchools' => School::query()->with('plan')->latest()->limit(5)->get(),
            'revenue' => $revenue,
            'growth' => $growth,
            'planMix' => $planMix,
            'activity' => auth()->user()->can('activity') ? ActivityLog::query()->where('action', 'not like', 'login.%')->latest('id')->limit(8)->get() : collect(),
        ]);
    }
}
