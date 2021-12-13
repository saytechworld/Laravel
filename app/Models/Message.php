<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Chat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class Message extends Model
{
   /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
      'message_uuid',  'user_id', 'chat_id', 'message', 'message_type', 'read_flag', 'delete_one', 'delete_two', 'delete_everyone', 'group_delete_message','thumbnail','delete_media','message_sent_uuid'
    ];

    public function user_conversations()
    {
    	return $this->belongsTo(Chat::class, 'chat_id', 'id');
    }

    public function senders()
    {
    	return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getAwsFileUrlAttribute()
    {
        if($this->message_type == 2 || $this->message_type == 3 || $this->message_type == 12)
        {
            return config('staging_live_config.AWS_URL').'messages/'.$this->message;
        }
        return null;
    }

    public function getAwsThumbFileUrlAttribute()
    {
        if($this->message_type == 2 || $this->message_type == 3)
        {
            if ($this->message_type == 2) {
                return config('staging_live_config.AWS_URL').'messages/thumb/'.$this->message;
            } else {
                if (!empty($this->thumbnail)) {
                    return config('staging_live_config.AWS_URL').'messages/thumb/'.$this->thumbnail;
                }
                return null;
            }
        }
        return null;
    }

    public function getMessageCreatedDateTimeAttribute()
    {
        return Carbon::parse($this->created_at)->addHour(2)->format('h:i A, F jS Y');
    }
    public function getMessageCreatedDateAttribute()
    {
        return Carbon::parse($this->created_at)->addHour(2)->format('F jS Y');
    }
    public function getMessageCreatedTimeAttribute()
    {
        return Carbon::parse($this->created_at)->addHour(2)->format('H:i');
    }

    public function getSentStatusAttribute()
    {
        return true;
    }
    protected $appends = [
      'message_created_date_time',
      'message_created_date',
      'message_created_time',
      'aws_file_url',
      'aws_thumb_file_url',
      'sent_status',
    ];



}
