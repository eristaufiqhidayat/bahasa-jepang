<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Character extends Model
{
    protected $fillable = ['script', 'symbol', 'romaji', 'group', 'position', 'audio_path'];
}
