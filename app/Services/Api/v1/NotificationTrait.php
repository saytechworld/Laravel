<?php

namespace App\Services\Api\v1;

use App\Models\Chat;
use App\Models\Event;
use App\Models\EventAttendant;
use App\Models\GroupUsers;
use App\Models\Notification;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception, Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait NotificationTrait
{

    public function notification(Request $request) {
        try {

            if(!$request->isMethod('post'))
            {
                throw new Exception("Request method not allowed.", 1);
            }

            $user = $this->getAuthenticatedUser();

            $input = $request->all();
            $page = isset($input['page']) ? $input['page'] : 1;

            $notifications = (new Notification())->newQuery();
            $notifications = $notifications
                ->selectRaw('id,from_user_id,type,notification_uuid,data,read_at,created_at')
                ->with('from_user:id,user_uuid')->where('to_user_id', $user->id)
                ->orderBy('created_at','DESC')
                ->paginate(10, ['*'], 'page', $page);

            $notifications->map(function ($notification) {
                $notification->from_user->makeHidden(['remaining_balance','total_balance', 'user_details', 'roles', 'role_type','user_image','user_thumb_image']);
            });

            $this->WebApiArray['status'] = true;
            if ($notifications->count() > 0) {
                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data'] = $notifications;
                $this->WebApiArray['statusCode'] = 0;
            } else {
                $this->WebApiArray['message'] = 'Record not found.';
                $this->WebApiArray['statusCode'] = 1;
            }
            return response()->json($this->WebApiArray);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function unreadNotificationList(Request $request) {
        try {
            $input = $request->all();

            $page = isset($input['page']) ? $input['page'] : 1;

            $user = $this->getAuthenticatedUser();

            $notification = Notification::selectRaw('id,type,notification_uuid,data,read_at,created_at')->where('to_user_id', $user->id)->whereNull('read_at')
                ->orderBy('created_at', 'DESC')->paginate(10, ['*'], 'page', $page);

            $this->WebApiArray['status'] = true;
            if ($notification->count() > 0) {
                $this->WebApiArray['message'] = 'Record found';
                $this->WebApiArray['data'] = $notification;
                $this->WebApiArray['statusCode'] = 0;
            } else {
                $this->WebApiArray['message'] = 'Record Not Found';
                $this->WebApiArray['statusCode'] = 0;
            }
            return response()->json($this->WebApiArray);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function makeReadNotification(Request $request) {
        DB::beginTransaction();
        try {

            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }
            $input = $request->all();
            $validator = Validator::make($input,[
                'notification_id'   =>  'required|exists:notifications,notification_uuid,to_user_id,'.auth()->id(),
            ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            $notification  = Notification::where(['notification_uuid' => $input['notification_id'], 'to_user_id' => $user->id])->first();

            if (!empty($notification)) {
                if(!empty($notification->read_at)){
                    throw new Exception("You have already read this notification.", 1);
                }
                $notification->read_at = Carbon::now();
                $notification->save();
                DB::commit();
                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'You read this notification.';
                return response()->json($this->WebApiArray);
            }
            throw new Exception("The notification id is invalid.", 1);
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function readNotification(Request $request)
    {
        DB::beginTransaction();
        try{
            $user = $this->getAuthenticatedUser();
            Notification::where(['to_user_id' => $user->id, 'read_all' => 0])
                ->update(['read_all' => 1]);

                DB::commit();
                
                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Status Updated Successfully.';
                return response()->json($this->WebApiArray);
        }catch(Exception $e){
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function notificationAction(Request $request)
    {
        DB::beginTransaction();
        try {
                if(!$request->isMethod('post'))
                {
                    throw new Exception("Request not allowed.", 1);
                }
                $input = $request->all();

                $user = $this->getAuthenticatedUser();

                $validator = Validator::make($input,[
                    'notification_id'   =>  'required|exists:notifications,notification_uuid,to_user_id,'.$user->id,
                    'action'   =>  'required|in:1,2',
                ]);
                if ($validator->fails()) {
                    throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
                }
                $notification  = Notification::whereIn('type',[2,3])->where(['notification_uuid' => $input['notification_id']])->first();

                if(!empty($notification)){
                    if(!empty($notification->read_at)){
                        throw new Exception("You have already read this notification.", 1);
                    }
                    $notification->read_at = Carbon::now();
                    $notification->save();
                    $readnotification  = Notification::whereNotNull('read_at')->where('notification_uuid',$input['notification_id'])->first();
                    if(!empty($readnotification)){

                        if ($notification->type == 3) {
                            $team = Team::where('id', $notification->notifiable_id)->first();
                            $team_user = $team->team_users()->wherePivot('user_id',$user->id)->first();
                            $team_user->pivot->status = $input['action'];
                            $team_user->pivot->save();
                            if ($input['action'] == 1) {
                                $chat = Chat::where(['team_id' => $team->id])->first();
                                if (!empty($chat)) {
                                    $group_user = new GroupUsers();
                                    $group_user->chat_id = $chat->id;
                                    $group_user->user_id = $user->id;
                                    $group_user->admin = 0;
                                    $group_user->save();
                                }
                                $current_time = Carbon::now()->format('Y-m-d H:i:s');
                                $checkTeamEvent = Event::with('event_creators')->whereHas('event_attendants',function($query) use($team){
                                    $query->where('team_id', $team->id);
                                })
                                    ->whereRaw("( (end_datetime IS NULL AND event_datetime > '".$current_time."' ) OR ( end_datetime  IS NOT NULL AND   end_datetime > '".$current_time."'))")
                                    ->get();

                                if($checkTeamEvent->count() > 0)
                                {
                                    foreach ($checkTeamEvent as $Team_Event_Key => $Team_Event_Val) {
                                        $event_attendant = EventAttendant::where(['event_id' => $Team_Event_Val->id, 'user_id' => $user->id ])->first();
                                        if(empty($event_attendant))
                                        {
                                            $team_event_data = array('user_id' => $user->id, 'email' => $user->email, 'attendant_type' => 'A', 'team_id' => $team->id);
                                            $added_event_attendant = new EventAttendant;
                                            $added_event_attendant->event_id = $Team_Event_Val->id;
                                            if($added_event_attendant->fill($team_event_data)->save()){
                                                $notification_message = 'You are invited by ' . $Team_Event_Val->event_creators->name . ' for a new event';
                                                $notification = $Team_Event_Val->notifications()->create(['type' => 2, 'from_user_id' => $Team_Event_Val->user_id, 'to_user_id' => $team_event_data['user_id'], 'data' => $notification_message, 'notification_uuid' => Str::uuid()->toString()]);
                                                $this->sendFirebaseNotification($notification, 'user_notifications');

                                                sendFcmNotification($team_event_data['user_id'],'Asportcoach', $notification_message);

                                            }else{
                                                throw new Exception("Error occured! while updating event.", 1);
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            $event = Event::where('id', $notification->notifiable_id)->first();
                            $attendant = EventAttendant::where(['event_id' => $event->id, 'user_id' =>$user->id])->first();
                            $attendant->status = $input['action'];
                            $attendant->save();
                        }
                        DB::commit();

                        if ($input['action'] == 1) {
                            $message = "Request Accepted Successfully";
                        } else {
                            $message = "Request Rejected Successfully";
                        }

                        
                        $this->WebApiArray['status'] = true;
                        $this->WebApiArray['message'] =$message;
                        $this->WebApiArray['data'] = $readnotification;
                        return response()->json($this->WebApiArray);
                    }
                    throw new Exception("Error Processing Request", 1);
                }
                throw new Exception("The notification id is invalid.", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }

    }

    public function notificationSetting(Request $request)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();

            $validator = Validator::make($input,[
               'action' => 'required|in:0,1'
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            if ($user->fill(['notification_setting' => 1])->save()) {
                DB::commit();
                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Notification setting changed successfully';
                return response()->json($this->WebApiArray);
            }
            throw new Exception('Error processing request',1);
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

}
