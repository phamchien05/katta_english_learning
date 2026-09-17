<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReadingPassage extends Model
{
    use HasFactory;

    protected $fillable = ['topic', 'title', 'level', 'content'];

    public function questions(): HasMany
    {
        return $this->hasMany(ReadingQuestion::class, 'passage_id')->orderBy('order');
    }
}
