<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->word()).' Plan';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'billing_model' => 'monthly',
            'price' => 5000,
            'price_per_student' => 0,
            'trial_days' => 14,
            'max_branches' => 1,
            'storage_gb' => 5,
            'modules' => ['students', 'attendance', 'fees'],
            'is_active' => true,
            'show_on_website' => true,
        ];
    }

    public function perStudent(float $rate = 50): static
    {
        return $this->state(['billing_model' => 'per_student', 'price' => 0, 'price_per_student' => $rate]);
    }

    public function yearly(float $price = 60000): static
    {
        return $this->state(['billing_model' => 'yearly', 'price' => $price]);
    }

    public function lifetime(float $price = 300000): static
    {
        return $this->state(['billing_model' => 'lifetime', 'price' => $price]);
    }
}
