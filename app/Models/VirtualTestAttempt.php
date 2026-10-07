<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualTestAttempt extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $hidden = ['owner_hash', 'questions'];

    protected function casts(): array
    {
        return ['questions' => 'array', 'answers' => 'array', 'result' => 'array', 'started_at' => 'datetime', 'expires_at' => 'datetime', 'completed_at' => 'datetime'];
    }
}
