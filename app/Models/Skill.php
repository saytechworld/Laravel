<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use App\Models\User;

class Skill extends Model
{
    use Sluggable;

    protected $fillable = [
        'game_id', 'title', 'slug', 'status',
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

    public function skill_users()
    {
        return $this->belongsToMany(User::class, 'user_game_skill', 'skill_id', 'user_id')->withPivot('game_id');
    }
}
