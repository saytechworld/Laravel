<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;

class UserFolder extends Model
{
   	use Sluggable;

    protected $fillable = [
        'user_id', 'title', 'slug', 'user_folder_id', 'folder_type',
    ];

    public function user_child_folders()
    {
       return  $this->hasMany(UserFolder::class, 'user_folder_id', 'id');
    }

    public function parent_child_folders()
    {
        return $this->belongsTo(UserFolder::class, 'user_folder_id', 'id');
    }

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
