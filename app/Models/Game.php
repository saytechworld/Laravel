<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use App\Models\Skill;
use App\Models\User;

class Game extends Model
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

    public function game_skills()
    {
    	return $this->hasMany(Skill::class, 'game_id', 'id');
    }

    public function game_users()
    {
        return $this->belongsToMany(User::class, 'user_game_skill', 'game_id', 'user_id')->withPivot('skill_id');
    }
}
