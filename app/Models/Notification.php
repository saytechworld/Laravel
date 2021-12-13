<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
	protected $fillable = [
        'type', 'notification_uuid', 'notifiable_type', 'notifiable_id', 'from_user_id', 'to_user_id', 'data', 'read_at',
    ];


    public function notifiable()
    {
        return $this->morphTo();
    }

    public function from_user()
    {
        return $this->belongsTo(User::class, 'from_user_id', 'id');
    }

    public function to_user()
    {
        return $this->belongsTo(User::class, 'to_user_id', 'id');
    }
}
