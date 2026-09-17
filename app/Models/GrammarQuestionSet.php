<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GrammarQuestionSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_key', 'status', 'replenish_dispatched', 'user_id', 'score', 'total', 'answers', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'replenish_dispatched' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(GrammarQuestion::class, 'set_id')->orderBy('order');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
