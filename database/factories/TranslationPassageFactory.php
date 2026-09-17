<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TranslationPassageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'level' => fake()->randomElement(['A1', 'A2', 'B1', 'B2', 'C1']),
            'direction' => 'en_vi',
            'source_text' => fake()->paragraph(4),
        ];
    }
}
