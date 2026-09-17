<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VocabularySubmission extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'level', 'score', 'total', 'results', 'created_at'];

    protected function casts(): array
    {
        return [
            'results' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
