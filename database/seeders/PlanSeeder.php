<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $core = ['students', 'admissions', 'attendance', 'fees', 'exams', 'communication', 'certificates', 'reports'];
        $standard = [...$core, 'timetable', 'homework', 'hr_payroll', 'accounts', 'library', 'mobile_apps'];
        $all = array_keys(config('skooline.modules'));

        $plans = [
            [
                'name' => 'Starter', 'slug' => 'starter', 'tagline' => 'For small schools getting started',
                'billing_model' => 'monthly', 'price' => 4000, 'max_students' => 200, 'max_staff' => 25,
                'max_branches' => 1, 'storage_gb' => 5, 'sms_credits' => 500, 'modules' => $core, 'sort_order' => 1,
            ],
            [
                'name' => 'Standard', 'slug' => 'standard', 'tagline' => 'Pay only for active students',
                'billing_model' => 'per_student', 'price' => 0, 'price_per_student' => 50, 'max_students' => null,
                'max_staff' => 150, 'max_branches' => 1, 'storage_gb' => 20, 'sms_credits' => 2000,
                'modules' => $standard, 'is_featured' => true, 'sort_order' => 2,
            ],
            [
                'name' => 'Premium', 'slug' => 'premium', 'tagline' => 'Everything, for multi-campus schools',
                'billing_model' => 'yearly', 'price' => 150000, 'max_students' => null, 'max_staff' => null,
                'max_branches' => 5, 'storage_gb' => 100, 'sms_credits' => 10000, 'modules' => $all, 'sort_order' => 3,
            ],
            [
                'name' => 'Lifetime', 'slug' => 'lifetime', 'tagline' => 'One-time license with yearly support',
                'billing_model' => 'lifetime', 'price' => 450000, 'setup_fee' => 0, 'max_students' => null,
                'max_staff' => null, 'max_branches' => 3, 'storage_gb' => 50, 'sms_credits' => 0,
                'modules' => $all, 'show_on_website' => false, 'sort_order' => 4,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan + ['trial_days' => config('skooline.default_trial_days')]);
        }
    }
}
