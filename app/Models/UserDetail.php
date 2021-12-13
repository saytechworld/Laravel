<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Zipcode;

class UserDetail extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'image', 'mobile', 'gender', 'dob', 'address_line_1', 'address_line_2', 'country_id', 'state_id', 'city_id', 'zipcode_id', 'experience','about','mobile_code_id',
    ];

    public function users()
    {
    	return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }
    public function state()
    {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id', 'id');
    }

    public function mobile_code()
    {
        return $this->belongsTo(Country::class, 'mobile_code_id', 'id');
    }

    public function zip_code()
    {
        return $this->belongsTo(Zipcode::class, 'zipcode_id', 'id');
    }

    public function getUserProfileImageAttribute()
    {
        if(!empty($this->image) && file_exists(public_path('images/users/'.$this->image)))
        {
            return asset('images/users/'.$this->image);
        }
        return null;
    }

    protected $appends = [
        'user_profile_image', 
    ];




}
