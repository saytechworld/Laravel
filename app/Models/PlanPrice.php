<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanPrice extends Model
{
    protected $fillable = [
        'plan_id', 'price', 'validity',
    ];

    public function getFinalPriceAttribute()
    {
        $service_tax = (env('SERVICE_TAX') / 100) * $this->price;
        $total_price = $this->price + $service_tax;
        return round($total_price,2);
    }

    protected $appends = [
        'final_price'
    ];
}
