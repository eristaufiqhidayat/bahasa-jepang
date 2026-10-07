<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualTestTemplate extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['sections' => 'array', 'question_ids' => 'array', 'official_score_reference' => 'array', 'duration_seconds' => 'integer'];
    }
}
