<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\EventAttendant;

class Event extends Model
{
    protected $fillable = [
        'user_id', 'title', 'description', 'event_color_id', 'color_code', 'category_id', 'event_datetime', 'end_datetime',
    ];

    public function event_creators()
    {
    	return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function event_attendants()
    {
    	return $this->hasMany(EventAttendant::class, 'event_id', 'id');
    }

    public function reject_event_attendants()
    {
        return $this->hasMany(EventAttendant::class, 'event_id', 'id')->where('status',2)->where('attendant_type','A');
    }

    public function accepted_event_attendants()
    {
        return $this->hasMany(EventAttendant::class, 'event_id', 'id')->where('status',1)->where('attendant_type','A');
    }

    public function pending_event_attendants()
    {
        return $this->hasMany(EventAttendant::class, 'event_id', 'id')->where('status',0)->where('attendant_type','A');
    }

    public function self_attendant()
    {
        return $this->hasOne(EventAttendant::class, 'event_id', 'id')->where('user_id',auth()->id());
    }


    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    public function category() {
        return $this->belongsTo(EventCategory::class);
    }

    public function getEventCreatedDateTimeAttribute()
    {
        return Carbon::parse($this->created_at)->format('D, F jS Y, H:i');
    }

    public function getEventCreatedDateAttribute()
    {
        return Carbon::parse($this->event_datetime)->format('d');
    }
    public function getEventCreatedMonthAttribute()
    {
        return Carbon::parse($this->event_datetime)->format('M');
    }

    public function getStartMonthNumberAttribute()
    {
        return Carbon::parse($this->event_datetime)->format('m');
    }

    public function getEndMonthNumberAttribute()
    {
        if (!empty($this->end_datetime)) {
            return Carbon::parse($this->end_datetime)->format('m');
        }
        return null;
    }
    protected $appends = [
        'event_created_date_time',
        'event_created_date',
        'event_created_month',
        'start_month_number',
        'end_month_number',
    ];
}
