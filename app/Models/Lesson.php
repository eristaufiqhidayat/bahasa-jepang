<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = ['title', 'slug', 'summary', 'content', 'japanese', 'romaji', 'translation', 'audio_path', 'position', 'duration_minutes', 'status', 'level', 'reference_id', 'chapter', 'patterns', 'source_reference', 'review_status'];

    protected function casts(): array
    {
        return ['patterns' => 'array', 'source_reference' => 'array', 'chapter' => 'integer'];
    }

    public function vocabularies()
    {
        return $this->hasMany(Vocabulary::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('position')->orderBy('id');
    }
}
