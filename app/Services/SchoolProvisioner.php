<?php

namespace App\Services;

use App\Enums\SchoolStatus;
use App\Models\Plan;
use App\Models\School;
use App\Models\School\User as SchoolUser;
use Illuminate\Support\Arr;
use Throwable;

/**
 * Creates a new school: central record, subdomain, its own database (migrated),
 * the first school admin account, and the trial or first subscription.
 */
class SchoolProvisioner
{
    public function __construct(protected SubscriptionManager $subscriptions) {}

    /**
     * @param  array{name:string, subdomain:string, email?:?string, phone?:?string, city?:?string, address?:?string, principal_name?:?string, notes?:?string}  $school
     * @param  array{name:string, email:string, password:string}  $admin
     * @param  array{mode:string, trial_days?:?int, starts_at?:?string, amount?:?float, student_count?:?int, payment_status?:?string}  $billing
     */
    public function create(array $school, Plan $plan, array $admin, array $billing, ?int $createdBy = null): School
    {
        $isTrial = ($billing['mode'] ?? 'trial') === 'trial';
        $trialDays = (int) ($billing['trial_days'] ?? $plan->trial_days);

        // Creating the model fires TenantCreated, which creates and migrates the school database.
        $model = School::create(Arr::only($school, [
            'name', 'subdomain', 'email', 'phone', 'city', 'address', 'principal_name', 'notes',
        ]) + [
            'plan_id' => $plan->id,
            'status' => $isTrial ? SchoolStatus::Trial : SchoolStatus::Active,
            'trial_ends_at' => $isTrial ? now()->addDays($trialDays)->endOfDay() : null,
        ]);

        try {
            $model->domains()->create(['domain' => $model->subdomain]);

            $model->run(function () use ($admin) {
                SchoolUser::create([
                    'name' => $admin['name'],
                    'email' => strtolower($admin['email']),
                    'password' => $admin['password'],
                    'role' => 'admin',
                ]);
            });

            if (! $isTrial) {
                $this->subscriptions->assign($model, $plan, [
                    'starts_at' => $billing['starts_at'] ?? now()->toDateString(),
                    'student_count' => $billing['student_count'] ?? null,
                    'amount' => $billing['amount'] ?? null,
                    'payment_status' => $billing['payment_status'] ?? 'unpaid',
                ], $createdBy);
            }
        } catch (Throwable $e) {
            // Roll back fully: deleting the tenant also drops its database.
            $model->delete();

            throw $e;
        }

        return $model->refresh();
    }
}
