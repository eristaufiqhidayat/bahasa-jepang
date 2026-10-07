<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = ['lesson_id', 'prompt', 'type', 'options', 'correct_index', 'explanation', 'audio_path', 'position'];

    protected $hidden = ['correct_index', 'explanation'];

    protected function casts(): array
    {
        return ['options' => 'array', 'correct_index' => 'integer'];
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
