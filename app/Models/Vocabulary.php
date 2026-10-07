<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vocabulary extends Model
{
    protected $fillable = ['lesson_id', 'japanese', 'reading', 'romaji', 'meaning', 'category', 'example', 'example_translation', 'audio_path'];

    protected $table = 'vocabularies';

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
