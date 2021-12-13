<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;

class Tag extends Model
{
   use Sluggable;

    protected $fillable = [
        'title', 'slug', 'status',
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
}
