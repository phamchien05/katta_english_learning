<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListeningQuestion extends Model
{
    use HasFactory;

    protected $fillable = ['passage_id', 'type', 'question', 'options', 'correct_answer', 'order'];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'correct_answer' => 'array',
        ];
    }

    public function passage(): BelongsTo
    {
        return $this->belongsTo(ListeningPassage::class, 'passage_id');
    }
}
