<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMeeting extends Model
{
    protected $fillable = [
        'chat_id',  'user_id', 'meeting_id', 'meeting_password', 'status', 'attendants'
    ];
}
