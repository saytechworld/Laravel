<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupUsers extends Model
{
    protected $fillable = [
        'user_id', 'chat_id', 'admin', 'status',
    ];

    public function users()
    {
    	return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function chat()
    {
        return $this->belongsTo(Chat::class, 'chat_id', 'id');
    }
}
