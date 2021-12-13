<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use App\Models\Country;
use App\Models\City;

class State extends Model
{
    use Sluggable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'country_id', 'title', 'slug', 'status',
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
    
    public function countries()
    {
    	return $this->belongsTo(Country::class, 'country_id', 'id');
    }

    public function cities()
    {
    	return $this->hasMany(City::class, 'state_id', 'id');
    }
}
