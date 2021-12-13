<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventColor extends Model
{
    protected $fillable = [
        'name', 'color_code_id', 'color_code', 'color_sort',
    ];
}
