<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialChatMessage extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $guarded = [];

    protected $hidden = ['owner_hash'];

    public function report()
    {
        return $this->hasOne(MaterialChatReport::class);
    }

    protected function casts(): array
    {
        return ['response' => 'array'];
    }
}
