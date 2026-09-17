<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TranslationPassage extends Model
{
    use HasFactory;

    protected $fillable = ['level', 'direction', 'source_text'];

    public function submissions(): HasMany
    {
        return $this->hasMany(TranslationSubmission::class, 'passage_id');
    }
}
