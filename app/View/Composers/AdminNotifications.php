<?php

namespace App\View\Composers;

use App\Enums\SchoolStatus;
use App\Models\DemoRequest;
use App\Models\School;
use App\Models\SchoolRegistration;
use App\Models\Subscription;
use App\Models\SupportTicket;
use App\Support\DatabaseStatus;
use Illuminate\View\View;

/**
 * Counts for the bell menu and sidebar badges in the Super Admin panel.
 */
class AdminNotifications
{
    public function compose(View $view): void
    {
        $user = auth('web')->user();

        // Before "Run updates" the new tables may not exist yet; show no counts instead of crashing.
        if (! $user || DatabaseStatus::isOutdated()) {
            $view->with(['notifications' => [], 'notificationTotal' => 0, 'navBadges' => []]);

            return;
        }

        $items = [];

        if ($user->can('schools.manage')) {
            $items[] = ['icon' => 'clipboard', 'count' => SchoolRegistration::query()->where('status', 'pending')->count(), 'label' => 'school registrations to review', 'route' => route('admin.registrations.index', ['status' => 'pending']), 'key' => 'registrations'];
        }

        if ($user->can('leads')) {
            $new = DemoRequest::query()->where('status', 'new')->count();
            $due = DemoRequest::query()->whereNotIn('status', ['converted', 'closed'])->whereDate('follow_up_on', '<=', today())->count();
            $items[] = ['icon' => 'inbox', 'count' => $new, 'label' => 'new demo requests', 'route' => route('admin.demo-requests.index', ['status' => 'new']), 'key' => 'leads'];
            $items[] = ['icon' => 'clock', 'count' => $due, 'label' => 'lead follow-ups due', 'route' => route('admin.demo-requests.index', ['follow_up' => 'due']), 'key' => null];
        }

        if ($user->can('tickets')) {
            $items[] = ['icon' => 'chat', 'count' => SupportTicket::query()->withoutGlobalScopes()->where('status', 'open')->count(), 'label' => 'open support tickets', 'route' => route('admin.tickets.index', ['status' => 'open']), 'key' => 'tickets'];
        }

        if ($user->can('schools.view')) {
            $renewals = School::query()
                ->where(fn ($q) => $q->where(fn ($q) => $q->where('status', SchoolStatus::Active->value)->whereBetween('subscription_ends_at', [now(), now()->addDays(7)]))
                    ->orWhere(fn ($q) => $q->where('status', SchoolStatus::Trial->value)->whereBetween('trial_ends_at', [now(), now()->addDays(7)])))
                ->count();
            $items[] = ['icon' => 'calendar', 'count' => $renewals, 'label' => 'renewals in the next 7 days', 'route' => route('admin.dashboard').'#renewals', 'key' => null];
        }

        if ($user->can('billing')) {
            $items[] = ['icon' => 'banknotes', 'count' => Subscription::query()->where('payment_status', 'unpaid')->count(), 'label' => 'unpaid invoices', 'route' => route('admin.subscriptions.index', ['payment_status' => 'unpaid']), 'key' => 'billing'];
        }

        $view->with('notifications', $items)
            ->with('notificationTotal', collect($items)->sum('count'))
            ->with('navBadges', collect($items)->whereNotNull('key')->pluck('count', 'key')->all());
    }
}
