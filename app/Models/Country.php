<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use App\Models\State;
use App\Models\City;
use App\Models\Zipcode;

class Country extends Model
{
    use Sluggable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', 'slug', 'phone_code', 'ISO_code', 'currency_code', 'stripe_enabled', 'status',
    ];

    public function sluggable()
    {
        return [
            'slug' => [
                'source' => ['title'],
                'separator' => '_',
                'onUpdate'  => false,
            ]
        ];
    }
    
    public function states()
    {
    	return $this->hasMany(State::class, 'country_id', 'id');
    }
}
