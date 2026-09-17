<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ReadingPassageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'topic' => fake()->randomElement(['business', 'environment', 'general', 'health', 'history', 'science', 'society', 'technology']),
            'title' => fake()->sentence(4),
            'level' => fake()->randomElement(['A1', 'A2', 'B1', 'B2', 'C1']),
            'content' => fake()->paragraph(6),
        ];
    }
}
