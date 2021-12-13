<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Role extends Model
{
    protected $fillable = [
        'name', 'description', 'all', 'sort',
    ];

    public function users()
    {
    	return $this->belongsToMany(User::class, 'user_role', 'role_id', 'user_id');
    }

    /*public function permissions()
    {
    	return $this->belongsToMany(Permission::class, 'permission_role', 'role_id', 'permission_id');
    }*/
}
