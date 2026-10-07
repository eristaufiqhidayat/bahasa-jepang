<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseTrack extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['focus' => 'array'];
    }
}
