<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;

class Language extends Model
{
	use Sluggable;
	
    protected $fillable = [
        'title', 'slug', 'lang_code', 'short_code', 'status',
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
