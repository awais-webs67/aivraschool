<?php

namespace App\Services;

use App\Enums\SchoolStatus;
use App\Models\Plan;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Creates a new school: its record, the first school admin account, and the
 * trial or first subscription — all in one transaction, in the shared database.
 */
class SchoolProvisioner
{
    public function __construct(protected SubscriptionManager $subscriptions) {}

    /**
     * @param  array{name:string, slug:string, email?:?string, phone?:?string, city?:?string, address?:?string, principal_name?:?string, notes?:?string}  $school
     * @param  array{name:string, email:string, password:string}  $admin
     * @param  array{mode:string, trial_days?:?int, starts_at?:?string, amount?:?float, student_count?:?int, payment_status?:?string}  $billing
     */
    public function create(array $school, Plan $plan, array $admin, array $billing, ?int $createdBy = null): School
    {
        $isTrial = ($billing['mode'] ?? 'trial') === 'trial';
        $trialDays = (int) ($billing['trial_days'] ?? $plan->trial_days);

        return DB::transaction(function () use ($school, $plan, $admin, $billing, $createdBy, $isTrial, $trialDays) {
            $model = School::create(Arr::only($school, [
                'name', 'slug', 'email', 'phone', 'city', 'address', 'principal_name', 'notes',
            ]) + [
                'plan_id' => $plan->id,
                'status' => $isTrial ? SchoolStatus::Trial : SchoolStatus::Active,
                'trial_ends_at' => $isTrial ? now()->addDays($trialDays)->endOfDay() : null,
            ]);

            $model->run(fn () => User::create([
                'name' => $admin['name'],
                'email' => strtolower($admin['email']),
                'password' => $admin['password'],
                'role' => 'admin',
            ]));

            if (! $isTrial) {
                $this->subscriptions->assign($model, $plan, [
                    'starts_at' => $billing['starts_at'] ?? now()->toDateString(),
                    'student_count' => $billing['student_count'] ?? null,
                    'amount' => $billing['amount'] ?? null,
                    'payment_status' => $billing['payment_status'] ?? 'unpaid',
                ], $createdBy);
            }

            return $model->refresh();
        });
    }
}
