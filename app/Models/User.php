<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Cviebrock\EloquentSluggable\Sluggable;
use App\Notifications\ResetPasswordNotification;
use App\Models\Role;
use App\Models\UserDetail;
use App\Models\Game;
use App\Models\Skill;
use App\Models\Language;
use App\Models\Team;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable,Sluggable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'username', 'email', 'password', 'user_uuid', 'confirmation_code', 'status', 'email_verified_at', 'confirmed', 'privacy', 'deleted_status', 'notification_setting','test_user'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'email', 'confirmation_code', 'status', 'email_verified_at', 'confirmed', 'stripe_id', 'stripe_account_id',
    ];

    public function sluggable()
    {
        return [
            'username' => [
                'source' => ['name'],
                'separator' => '_',
                'onUpdate'  => false,
            ]
        ];
    }



     /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }


    
    public function IsActive()
    {
        return $this->status == 1;     
    }

    public function IsConfirmed()
    {
        return $this->confirmed == 1;          
    }   

    public function IsDeletedStatus()
    {
        return $this->deleted_status != 1;     
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role', 'user_id', 'role_id');
    }

    public function hasRoles($role_id)
    {   
        $role_flag = false;
        if($this->roles->count() > 0)
        {
            foreach ($this->roles as $user_role_key => $user_role_val) {
                if($user_role_val->id == $role_id || $user_role_val->name == $role_id ){
                    $role_flag = true;
                    break;
                }
            }
        }
        return $role_flag;
    }

    public function ManageRoles($userroles)
    {   
        $user_roles = explode('|', $userroles);
        $user_role_flag = false;
        if($this->roles->count() > 0)
        {
            foreach ($this->roles as $user_role_key => $user_role_val) {
                if(in_array($user_role_val->id, $user_roles) || in_array($user_role_val->name, $user_roles)){
                    $user_role_flag = true;
                    break;
                }
            }
        }
        return $user_role_flag;
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function user_details()
    {
        return $this->hasOne(UserDetail::class, 'user_id', 'id');
    }

    public function coach_games()
    {
        return $this->belongsToMany(Game::class, 'user_game_skill','user_id', 'game_id')->withPivot('skill_id');
    }

    public function coach_games_skills()
    {
        return $this->belongsToMany(Skill::class, 'user_game_skill', 'user_id', 'skill_id')->withPivot('game_id');
    }

    public function user_spoken_languages()
    {
        return $this->belongsToMany(Language::class, 'user_language', 'user_id', 'language_id');
    }

    public function athelete_games()
    {
        return $this->belongsToMany(Game::class, 'athelete_game','user_id', 'game_id');
    }

    public function videos()
    {
        return $this->hasMany(Video::class, 'user_id', 'id');
    }

    public function getUserImageAttribute()
    {
        if(!empty($this->user_details->image) &&  Storage::disk('s3')->exists('users/'.$this->user_details->image))
        {

            return config('staging_live_config.AWS_URL').'users/'.$this->user_details->image;
        }
        return null;
    }

    public function getUserThumbImageAttribute()
    {
        if(!empty($this->user_details->image) &&  Storage::disk('s3')->exists('users/thumb/'.$this->user_details->image))
        {

            return config('staging_live_config.AWS_URL').'users/thumb/'.$this->user_details->image;
        }
        return null;
    }

    public function getRoleTypeAttribute()
    {
        if($this->roles->count() > 0)
        {
            $user_roles = $this->roles->pluck('id')->toArray();
            if(in_array(3,$user_roles)){
                return 'coach';
            }
            if(in_array(4,$user_roles)){
                return 'athlete';
            }else{
                return 'admin';
            }  
        }
        return null;
        if(!empty($this->user_details->image) && Storage::disk('s3')->exists('users/'.$this->user_details->image))
        {
            return config('staging_live_config.AWS_URL').'users/'.$this->user_details->image;
        }
        return null;
    }

    public function getTotalBalanceAttribute() {
        return $this->coachSessionRequest()
            ->where('status', 7)
            ->sum('session_price');
    }

    public function getWithdrawableBalanceAttribute() {
        $days = 7;
        $today_date = Carbon::now()->format('Y-m-d');
        return $this->coachSessionRequest()
            ->whereRaw("( date(DATE_ADD(created_at, INTERVAL ".$days." DAY)) < '".$today_date."')")
            ->where('status', 7)
            ->sum('session_price');
    }

    public function getNonWithdrawableAmountAttribute() {
        return $this->getTotalBalanceAttribute() - $this->getWithdrawableBalanceAttribute();
    }

    public function getRemainingBalanceAttribute() {
        $withdrawal_amount =  $this->withdrawalAmount()->sum('amount');
        return $this->getWithdrawableBalanceAttribute() - $withdrawal_amount;
    }

    public function coachSessionRequest() {
        return $this->hasMany(SessionRequest::class, 'coach_id', 'id');
    }
    public function withdrawalAmount() {
        return $this->hasMany(Withdrawal::class, 'user_id', 'id')->where('status', 1);
    }

    public function user_teams() {
        return $this->belongsToMany(Team::class, 'team_user', 'user_id', 'team_id')->withPivot('status');
    }


    protected $appends = [
        'user_image', 'user_thumb_image', 'role_type', 'total_balance', 'remaining_balance','non_withdrawable_amount'
    ];


}
