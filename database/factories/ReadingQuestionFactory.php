<?php

namespace Database\Factories;

use App\Models\ReadingPassage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReadingQuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'passage_id' => ReadingPassage::factory(),
            'type' => 'fill',
            'question' => fake()->sentence() . ' ____.',
            'options' => [],
            'correct_answer' => [fake()->word()],
            'order' => 0,
        ];
    }
}
