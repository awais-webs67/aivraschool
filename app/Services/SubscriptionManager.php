<?php

namespace App\Services;

use App\Enums\SchoolStatus;
use App\Models\Plan;
use App\Models\School;
use App\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionManager
{
    /**
     * Start a subscription period for a school and make it active on that plan.
     *
     * @param  array{starts_at?:string, ends_at?:?string, student_count?:?int, amount?:?float, payment_status?:?string, payment_method?:?string, payment_reference?:?string, notes?:?string}  $data
     */
    public function assign(School $school, Plan $plan, array $data = [], ?int $createdBy = null): Subscription
    {
        $starts = Carbon::parse($data['starts_at'] ?? now())->startOfDay();
        $ends = ! empty($data['ends_at'])
            ? Carbon::parse($data['ends_at'])->startOfDay()
            : $this->endDateFor($plan, $starts);

        $students = isset($data['student_count']) ? (int) $data['student_count'] : null;
        $amount = isset($data['amount']) && $data['amount'] !== '' ? (float) $data['amount'] : $plan->priceFor($students ?? 0);
        $paymentStatus = $data['payment_status'] ?? 'unpaid';

        return DB::transaction(function () use (
            $school, $plan, $data, $createdBy, $starts, $ends, $students, $amount, $paymentStatus
        ) {
            $subscription = $school->subscriptions()->create([
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'billing_model' => $plan->billing_model,
                'starts_at' => $starts,
                'ends_at' => $ends,
                'student_count' => $students,
                'amount' => $amount,
                'payment_status' => $paymentStatus,
                'payment_method' => $data['payment_method'] ?? null,
                'payment_reference' => $data['payment_reference'] ?? null,
                'paid_at' => $paymentStatus === 'paid' ? now() : null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $createdBy,
            ]);

            $school->update([
                'plan_id' => $plan->id,
                'status' => SchoolStatus::Active,
                'subscription_ends_at' => $ends?->copy()->endOfDay(),
                'trial_ends_at' => null,
                'suspended_at' => null,
                'suspension_reason' => null,
            ]);

            return $subscription;
        });
    }

    /** End date of one billing cycle (day before the next cycle starts); null for lifetime. */
    public function endDateFor(Plan $plan, Carbon $starts): ?Carbon
    {
        $months = $plan->cycleMonths();

        return $months ? $starts->copy()->addMonthsNoOverflow($months)->subDay() : null;
    }

    /**
     * Expire schools whose trial ended or whose subscription ended beyond the grace period.
     *
     * @return int number of schools expired
     */
    public function expireOverdue(): int
    {
        $count = 0;
        $graceCutoff = now()->subDays(config('skooline.grace_days'));

        School::query()
            ->where(function ($q) use ($graceCutoff) {
                $q->where(fn ($q) => $q->where('status', SchoolStatus::Trial->value)->where('trial_ends_at', '<', now()))
                    ->orWhere(fn ($q) => $q->where('status', SchoolStatus::Active->value)->where('subscription_ends_at', '<', $graceCutoff));
            })
            ->each(function (School $school) use (&$count) {
                $school->update(['status' => SchoolStatus::Expired]);
                $count++;
            });

        return $count;
    }

    /** Estimated monthly recurring revenue from schools currently active. */
    public function monthlyRecurringRevenue(): float
    {
        return (float) School::query()
            ->where('status', SchoolStatus::Active->value)
            ->with('latestSubscription')
            ->get()
            ->sum(fn (School $school) => $school->latestSubscription?->monthlyValue() ?? 0);
    }
}
