<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vocabulary extends Model
{
    use HasFactory;

    protected $fillable = ['level', 'word', 'part_of_speech', 'ipa', 'meaning_vi'];

    public function progress(): HasMany
    {
        return $this->hasMany(UserVocabProgress::class);
    }
}
