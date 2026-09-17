<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ListeningPassageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'topic' => fake()->randomElement(['general', 'everyday_conversation', 'social_monologue', 'academic_discussion', 'academic_lecture']),
            'title' => fake()->sentence(4),
            'level' => fake()->randomElement(['A1', 'A2', 'B1', 'B2', 'C1']),
            'transcript' => fake()->paragraph(6),
        ];
    }
}
