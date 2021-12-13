<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\City;

class Zipcode extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
      'city_id', 'state_id',  'country_id', 'zip_code', 'status',
    ];

    public function cities()
    {
    	return $this->belongsTo(City::class, 'city_id', 'id');
    }
}
