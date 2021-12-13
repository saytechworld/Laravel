<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;

class StaticPage extends Model
{
    use Sluggable;

    protected $fillable = [
        'title', 'slug', 'description', 'featured_image','status',
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
