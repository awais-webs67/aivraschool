<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SchoolStatus;
use App\Http\Controllers\Controller;
use App\Models\DemoRequest;
use App\Models\School;
use App\Models\Subscription;
use App\Services\SubscriptionManager;
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

        return view('admin.dashboard', [
            'total' => $counts->sum(),
            'counts' => $counts,
            'mrr' => $subscriptions->monthlyRecurringRevenue(),
            'collectedThisMonth' => (float) Subscription::query()
                ->where('payment_status', 'paid')
                ->where('paid_at', '>=', now()->startOfMonth())
                ->sum('amount'),
            'unpaid' => (float) Subscription::query()->where('payment_status', 'unpaid')->sum('amount'),
            'newDemoRequests' => DemoRequest::query()->where('status', 'new')->count(),
            'expiringSoon' => $expiringSoon,
            'recentSchools' => School::query()->with('plan')->latest()->limit(6)->get(),
        ]);
    }
}
