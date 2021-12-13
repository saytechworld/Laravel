<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_uuid', 'transaction_id', 'user_id', 'plan_id','plan_price_id', 'session_request_id', 'order_type', 'price', 'service_tax', 'transaction_fees', 'total_price', 'plan_end_date', 'status','payment_type','created_at'
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function plan() {
        return $this->belongsTo(Plan::class, 'plan_id', 'id');
    }

    public function session_request() {
        return $this->belongsTo(SessionRequest::class, 'session_request_id', 'id');
    }

    public function getCreatedAtDateAttribute()
    {
        return Carbon::parse($this->created_at)->addHour(2)->format('Y-m-d H:s:i');
    }

    protected $appends = [
        'created_at_date'
    ];
}
