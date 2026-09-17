<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserVocabProgress extends Model
{
    use HasFactory;

    protected $table = 'user_vocab_progress';

    protected $fillable = ['user_id', 'vocabulary_id', 'is_mastered', 'last_reviewed_at'];

    protected function casts(): array
    {
        return [
            'is_mastered' => 'boolean',
            'last_reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vocabulary(): BelongsTo
    {
        return $this->belongsTo(Vocabulary::class);
    }
}
