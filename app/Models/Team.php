<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{

    use Sluggable;

    protected $fillable = [
        'title', 'user_id', 'description', 'slug',
    ];

    public function team_users() {
        return $this->belongsToMany(User::class, 'team_user', 'team_id', 'user_id')->withPivot('status');
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    public function team_group()
    {
        return $this->hasOne(Chat::class, 'team_id', 'id');
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
