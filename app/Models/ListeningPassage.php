<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ListeningPassage extends Model
{
    use HasFactory;

    protected $fillable = ['topic', 'title', 'level', 'transcript'];

    public function questions(): HasMany
    {
        return $this->hasMany(ListeningQuestion::class, 'passage_id')->orderBy('order');
    }
}
