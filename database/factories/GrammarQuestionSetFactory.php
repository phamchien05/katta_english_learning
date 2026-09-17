<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GrammarQuestionSetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'topic_key' => fake()->randomElement(['parts-of-speech', 'tenses', 'sentence-structures', 'question-forms', 'common-structures']),
            'status' => 'available',
            'replenish_dispatched' => false,
        ];
    }
}
