<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'title', 'description', 'price', 'status', 'validity',
    ];

    public function planPrice() {
        return $this->hasMany(PlanPrice::class, 'plan_id', 'id');
    }
}
