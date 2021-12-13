<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use App\Models\Country;
use App\Models\State;
use App\Models\Zipcode;

class City extends Model
{
    use Sluggable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
      'state_id',  'country_id', 'title', 'slug', 'status',
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
    	return $this->belongsTo(State::class, 'state_id', 'id');
    }

    public function city_zipcodes()
    {
    	return $this->hasMany(Zipcode::class, 'city_id', 'id');
    }

}
