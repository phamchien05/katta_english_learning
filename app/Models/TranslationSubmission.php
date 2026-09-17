<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TranslationSubmission extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'passage_id', 'user_translation', 'ai_score', 'ai_feedback'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function passage(): BelongsTo
    {
        return $this->belongsTo(TranslationPassage::class, 'passage_id');
    }
}
