<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrammarQuestion extends Model
{
    use HasFactory;

    protected $fillable = ['set_id', 'type', 'question', 'options', 'correct_answer', 'order'];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            // Luôn là mảng chuỗi: 1 phần tử cho fill/mcq, nhiều phần tử cho multi (chọn nhiều đáp án)
            'correct_answer' => 'array',
        ];
    }

    public function set(): BelongsTo
    {
        return $this->belongsTo(GrammarQuestionSet::class, 'set_id');
    }
}
