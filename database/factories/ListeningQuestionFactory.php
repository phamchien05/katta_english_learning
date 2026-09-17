<?php

namespace Database\Factories;

use App\Models\ListeningPassage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ListeningQuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'passage_id' => ListeningPassage::factory(),
            'type' => 'fill',
            'question' => fake()->sentence() . ' ____.',
            'options' => [],
            'correct_answer' => [fake()->word()],
            'order' => 0,
        ];
    }
}
