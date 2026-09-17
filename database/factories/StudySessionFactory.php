<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudySessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['vocabulary', 'translate', 'reading', 'grammar', 'listening']),
            'duration_seconds' => fake()->numberBetween(60, 900),
            'completed_at' => now(),
        ];
    }
}
