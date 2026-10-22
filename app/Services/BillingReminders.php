<?php

namespace App\Services;

use App\Enums\SchoolStatus;
use App\Mail\ExpiryReminderMail;
use App\Models\School;
use App\Models\Setting;
use App\Models\Subscription;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Daily reminder emails (runs from the scheduler):
 *  - trial / subscription ends in 7, 3 or 1 days (each one sent once)
 *  - unpaid invoices: 3 days after issue, then weekly
 */
class BillingReminders
{
    public const EXPIRY_DAYS = [7, 3, 1];

    public const UNPAID_FIRST_AFTER_DAYS = 3;

    public const UNPAID_REPEAT_DAYS = 7;

    public function __construct(protected SubscriptionPayments $payments) {}

    public static function enabled(): bool
    {
        return (bool) Setting::get('reminders_enabled', true);
    }

    /** @return array{expiry: int, unpaid: int} emails sent */
    public function send(): array
    {
        if (! self::enabled()) {
            return ['expiry' => 0, 'unpaid' => 0];
        }

        return ['expiry' => $this->expiring(), 'unpaid' => $this->unpaid()];
    }

    protected function expiring(): int
    {
        $sent = 0;
        $today = now()->startOfDay();

        School::query()
            ->whereIn('status', [SchoolStatus::Trial->value, SchoolStatus::Active->value])
            ->where(fn ($q) => $q
                ->whereBetween('trial_ends_at', [$today, $today->copy()->addDays(max(self::EXPIRY_DAYS) + 1)])
                ->orWhereBetween('subscription_ends_at', [$today, $today->copy()->addDays(max(self::EXPIRY_DAYS) + 1)]))
            ->each(function (School $school) use (&$sent, $today) {
                $trial = $school->status === SchoolStatus::Trial;
                $ends = $trial ? $school->trial_ends_at : $school->subscription_ends_at;
                $days = $ends ? (int) $today->diffInDays($ends->copy()->startOfDay(), false) : null;

                if (! in_array($days, self::EXPIRY_DAYS, true)) {
                    return;
                }

                // "2026-11-30:3" = the 3-day reminder for a period ending 30 Nov was sent.
                $key = $ends->toDateString().':'.$days;
                if ($school->expiry_reminded_for === $key || ! ($to = $school->billingEmails())) {
                    return;
                }

                try {
                    Mail::to($to)->send(new ExpiryReminderMail($school, $days, $trial));
                    $school->forceFill(['expiry_reminded_for' => $key])->saveQuietly();
                    $sent++;
                } catch (Throwable $e) {
                    report($e);
                }
            });

        return $sent;
    }

    protected function unpaid(): int
    {
        $sent = 0;

        Subscription::query()
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->where('amount', '>', 0)
            ->where('created_at', '<=', now()->subDays(self::UNPAID_FIRST_AFTER_DAYS))
            ->where(fn ($q) => $q->whereNull('reminded_at')->orWhere('reminded_at', '<=', now()->subDays(self::UNPAID_REPEAT_DAYS)))
            ->whereHas('school')
            ->with('school')
            ->each(function (Subscription $subscription) use (&$sent) {
                if ($this->payments->sendInvoice($subscription, 'reminder')) {
                    $sent++;
                }
            });

        return $sent;
    }
}
