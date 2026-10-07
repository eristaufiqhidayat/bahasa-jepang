<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = ['lesson_id', 'prompt', 'type', 'options', 'correct_index', 'explanation', 'audio_path', 'position', 'level', 'reference_id', 'section', 'skill', 'item_type', 'source_reference', 'audio_script', 'review_status'];

    protected $hidden = ['correct_index', 'explanation', 'audio_script'];

    protected function casts(): array
    {
        return ['options' => 'array', 'correct_index' => 'integer', 'source_reference' => 'array'];
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
