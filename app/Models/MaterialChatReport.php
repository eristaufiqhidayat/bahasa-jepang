<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialChatReport extends Model
{
    protected $guarded = ['id'];

    public function message()
    {
        return $this->belongsTo(MaterialChatMessage::class, 'material_chat_message_id');
    }
}
