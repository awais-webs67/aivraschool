<?php

namespace Database\Factories;

use App\Enums\SchoolStatus;
use App\Models\Plan;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->lastName().' School';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'city' => fake()->city(),
            'status' => SchoolStatus::Trial,
            'plan_id' => Plan::factory(),
            'trial_ends_at' => now()->addDays(14),
        ];
    }
}
