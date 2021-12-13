<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IosVersionController extends Model
{
    protected $fillable = [
        'version',  'status'
    ];
}
