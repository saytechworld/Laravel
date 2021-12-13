<?php

namespace App\Models;

use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Chat extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
      'chat_uuid',  'one_user_id', 'two_user_id', 'message', 'message_type', 'team_id', 'chat_type', 'group_name','delete_chat'
    ];

    public function one_users()
    {
    	return $this->belongsTo(User::class, 'one_user_id', 'id');
    }

    public function two_users()
    {
    	return $this->belongsTo(User::class, 'two_user_id', 'id');
    }

    public function meeting()
    {
    	return $this->belongsTo(ChatMeeting::class, 'id', 'chat_id');
    }

    public function chat_messages()
    {
      return $this->hasMany(Message::class, 'chat_id', 'id');
    }

    public function group_users()
    {
      return $this->hasMany(GroupUsers::class, 'chat_id', 'id');
    }

    public function active_group_users()
    {
      return $this->hasMany(GroupUsers::class, 'chat_id', 'id')->where('status', 1);
    }

    public function getLastMessageAttribute(){
        if ($this->chat_type == 1) {
            $message = Message::where('chat_id', $this->id)->whereIn('message_type', [1,2,3])->whereNull('delete_everyone')
                ->whereRaw("((delete_one is null or delete_one != ".auth()->id().") AND (delete_two is null or  delete_two != ".auth()->id()."))")
                ->orderBy('id', 'DESC')->first();
        } else {
            $group_user = GroupUsers::whereHas('chat',function($query){
                $query->where('chat_uuid',$this->chat_uuid);
            })->where('user_id', auth()->id())->first();

            if (!empty($group_user)) {
                if ($group_user->status == 1) {
                    $message = Message::where('chat_id', $this->id)->whereIn('message_type', [1,2,3])->whereNull('delete_everyone')->
                    whereRaw("(group_delete_message is null OR NOT FIND_IN_SET(" . auth()->id() . ",group_delete_message))")
                    ->orderBy('id', 'DESC')->first();
                } else {
                    $message = Message::where('chat_id', $this->id)->whereIn('message_type', [1,2,3])->whereNull('delete_everyone')->
                    whereRaw("(group_delete_message is null OR NOT FIND_IN_SET(" . auth()->id() . ",group_delete_message))")->with(['senders'])
                        ->where('created_at', '<=', $group_user->updated_at)->orderBy('id', 'DESC')->first();
                }
            } else{
                return null;
            }
        }
        if (!empty($message)) {
            return $message->message;
        }
        return null;
    }

    public function getUpdatedTimeAttribute()
    {
        return Carbon::parse($this->updated_at)->addHour(2)->format('Y-m-d H:i:s');
    }

    protected $appends = [
        'last_message',
        'updated_time'
    ];
}
