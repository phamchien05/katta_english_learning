<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingQuestion extends Model
{
    use HasFactory;

    protected $fillable = ['passage_id', 'type', 'question', 'options', 'correct_answer', 'order'];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            // Luôn là mảng chuỗi: 1 phần tử cho fill/mcq/boolean, nhiều phần tử cho multi (chọn nhiều đáp án)
            'correct_answer' => 'array',
        ];
    }

    public function passage(): BelongsTo
    {
        return $this->belongsTo(ReadingPassage::class, 'passage_id');
    }
}
