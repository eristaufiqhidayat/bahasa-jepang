<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialAnswer extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['keywords' => 'array'];
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function scopeVisible($q)
    {
        return $q->where('status', 'published')->where(fn ($q) => $q->whereNull('lesson_id')->orWhereHas('lesson', fn ($q) => $q->where('status', 'published')));
    }
}
