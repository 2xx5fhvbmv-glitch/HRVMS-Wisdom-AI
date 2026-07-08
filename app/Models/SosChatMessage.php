<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SosChatMessage extends Model
{
    protected $table = 'sos_chat_messages';

    protected $fillable = [
        'resort_id',
        'sos_history_id',
        'sender_id',
        'message',
    ];
}
