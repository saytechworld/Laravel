<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Support extends Model
{
    protected $fillable = [
        'name', 'email', 'status','enquiry', 'ip_address',
    ];
}
