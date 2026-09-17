<?php

namespace Database\Factories;

use App\Models\GrammarQuestionSet;
use Illuminate\Database\Eloquent\Factories\Factory;

class GrammarQuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'set_id' => GrammarQuestionSet::factory(),
            'type' => 'fill',
            'question' => fake()->sentence() . ' ____.',
            'options' => [],
            'correct_answer' => [fake()->word()],
            'order' => 0,
        ];
    }
}
