<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\Chat;
use App\Models\User;

class SessionRequest extends Model
{
    protected $fillable = [
        'coach_id', 'athelete_id', 'chat_id', 'chat_session_uuid', 'status', 'session_price', 'start_session_time', 'end_session_time',
    ];

    public function session_chats()
    {
        return $this->belongsTo(Chat::class, 'chat_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'athelete_id', 'id');
    }

    public function athelete_user()
    {
        return $this->belongsTo(User::class, 'athelete_id', 'id');
    }

    public function coach_user()
    {
        return $this->belongsTo(User::class, 'coach_id', 'id');
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    public function getTotalSessionPriceAttribute()
    {
        if($this->session_price > 0) {
            $total_session_price = floatval($this->session_price) + floatval($this->session_platform_fees) + config('staging_live_config.SERVICE_TAX')/100 * floatval($this->session_platform_fees);
            return number_format($total_session_price, 2);
        }
        return '0';
    }

    public function getSessionPlatformFeesAttribute()
    {
        if($this->session_price > 0)
        {
            $session_platform_fees = config('staging_live_config.PLATFORM_FEES')/100 * floatval($this->session_price);
            return number_format($session_platform_fees,2);
        }
        return '0';
    }

    public function getSessionPriceVatAttribute()
    {
        if($this->session_price > 0)
        {
            $session_price_vat = config('staging_live_config.SERVICE_TAX')/100 * floatval($this->session_platform_fees);
            return number_format($session_price_vat,2);
        }
        return '0';
    }

    public function getParsingSessionPriceAttribute()
    {
        if($this->session_price > 0)
        {
            return number_format($this->session_price,2);
        }
        return '0';
    }

    public function getStartSessionDateTimeAttribute()
    {
        if (!empty($this->start_session_time)){
            return Carbon::parse($this->start_session_time)->addHour(2)->format('Y-m-d H:s:i');
        }
        return null;
    }

    protected $appends = [
        'session_platform_fees', 'session_price_vat', 'total_session_price', 'parsing_session_price','start_session_date_time',
    ];


}
