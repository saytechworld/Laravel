<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Event;

class EventAttendant extends Model
{
   	protected $fillable = [
        'user_id', 'event_id', 'email', 'attendant_type', 'event_type', 'team_id', 'status',
    ];

    public function events()
    {
    	return $this->belongsTo(Event::class, 'event_id', 'id');
    }

    public function users()
    {
    	return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function teams()
    {
    	return $this->belongsTo(Team::class, 'team_id', 'id');
    }
}
