<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VocabularyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'level' => fake()->randomElement(['A1', 'A2', 'B1', 'B2', 'C1', 'C2']),
            'word' => fake()->unique()->word(),
            'part_of_speech' => fake()->randomElement(['N.', 'V.', 'Adj.']),
            'ipa' => '/test/',
            'meaning_vi' => fake()->word(),
        ];
    }
}
