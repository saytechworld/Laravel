<?php

namespace App\Services\Api\v3;

use App\Models\Chat;
use App\Models\EventColor;
use App\Models\Notification;
use App\Models\Team;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception, Validation, Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Event;
use App\Models\EventAttendant;

trait UserEventTrait
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function createEvent(Request $request)
    {
        DB::beginTransaction();
        try {
                if(!$request->isMethod('post'))
                {
                    throw new Exception("Request not allowed.", 1);
                }

                $user = $this->getAuthenticatedUser();

                $input = $request->all();

                $validator = Validator::make($input,[
                    'title'       => 'required|max:50',
                    'attendant'   => 'nullable|array|min:1',
                    'attendant.*'   => 'nullable|exists:users,id,status,1,confirmed,1,deleted_status,0',
                    'team'   => 'nullable|array|min:1',
                    'team.*'   => 'nullable|exists:teams,id,user_id,'.$user->id,
                    'category_id'   => 'nullable|exists:event_categories,id,user_id,'.$user->id,
                    'event_date'    => 'required|date_format:Y/m/d H:i|after:'.Carbon::now()->format('Y/m/d H:i'),
                    'event_color_id'    => 'required|exists:event_colors,id',
                    'end_datetime'    => 'nullable|date_format:Y/m/d H:i|after_or_equal :'.$request->get('event_date'),
                ]);

                if ($validator->fails()) {
                    throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
                }

                if(!empty($input['attendant']) && is_array($input['attendant'])) {
                    $userChats = Chat::where('chat_type',1)->has('chat_messages')
                        ->where(function ($queryone) use($user) {
                            $queryone->where('one_user_id', $user->id)->orWhere('two_user_id', $user->id);
                        })
                        ->whereHas('one_users',function($querytwo){
                            $querytwo->where('deleted_status','!=',1)->where(['status' => 1, 'confirmed' => 1 ]);
                        })
                        ->whereHas('two_users',function($query){
                            $query->where('deleted_status','!=',1)->where(['status' => 1, 'confirmed' => 1 ]);
                        })
                        ->selectRaw("one_user_id, two_user_id")
                        ->get();
                    $filtered_collection = $userChats->filter(function ($item) use ($input) {
                        if (in_array($item->one_user_id, $input['attendant']) || in_array($item->two_user_id, $input['attendant'])) {
                            return $item;
                        }
                    })->values();

                    if ($filtered_collection->count() == 0) {
                        throw new Exception("User is not exist in chat", 1);
                    }
                    $filtered_chats = $filtered_collection->toArray();
                    $one_user = array_column($filtered_chats, 'one_user_id');
                    $two_user = array_column($filtered_chats, 'two_user_id');
                    $team_users = array_unique(array_merge($one_user, $two_user));

                    if (in_array($user->id, $team_users)) {
                        unset($team_users[array_search($user->id, $team_users)]);
                    }
                    $filtered_teams = array_values($team_users);
                    $input['attendant'] = $filtered_teams;
                }

                if(!empty($input['team']) && is_array($input['team']))
                {
                    $checkAcceptedTeam = Team::withCount(['team_users' => function($teamquery){
                        $teamquery->where('team_user.status',1);
                    }])->whereIn('id',$input['team'])->get();
                    $filteredAcceptedteam = $checkAcceptedTeam->filter(function ($checkAcceptedTeamItem) {
                        if($checkAcceptedTeamItem['team_users_count'] > 0){
                            return $checkAcceptedTeamItem;
                        }
                    })->values();
                    if($filteredAcceptedteam->count() != $checkAcceptedTeam->count()){
                      throw new Exception("None of your team member have accepted your request yet.", 1); 
                    }
                }

                $color = EventColor::find($input['event_color_id']);

                $input['event_datetime'] = $input['event_date'] ? Carbon::parse($input['event_date'])->format('Y-m-d H:i:s') : null;
                $input['end_datetime'] = $input['end_datetime'] ? Carbon::parse($input['end_datetime'])->format('Y-m-d H:i:s') : null;
                $input['user_id'] = $user->id;




                $input['event_color_id'] = $color->id;
                $input['color_code'] = $color->color_code;
                if(empty($input['category_id']))
                {
                    if($color->id != 2)
                    {
                        throw new Exception("Invalid event color", 1);
                    }
                }


                //$input['color_id'] = $input['event_color_id'];
                //$input['color_code'] = $color->color_code;

                $event = new Event;
                if($event->fill($input)->save()){
                    $team_attendant_user = array();
                    $attendant_user = array();
                    $inc = 0;
                    if(!empty($input['team']) && is_array($input['team']))
                    {
                        $team = Team::with('team_users')->whereIn('id',$input['team'])->get();
                        foreach ($team as $team_key => $team_value) {
                            if($team_value->team_users->count() > 0){
                                foreach ($team_value->team_users as $team_user_key => $team_user_value) {
                                    if($team_user_value->pivot->status == 1) {
                                        if(!in_array($team_user_value->id,$attendant_user) && $user->id != $team_user_value->id ){
                                            $inc++;
                                            $team_attendant_user[$inc] = array('user_id' => $team_user_value->id, 'email' =>  $team_user_value->email, 'attendant_type' => 'A',  'team_id' => $team_value->id);
                                            array_push($attendant_user, $team_user_value->id);
                                        }
                                    }
                                }
                            } 
                        }
                    }
                    if(!empty($input['attendant']) && is_array($input['attendant']))
                    {
                        $different_users = User::whereRaw("(status = 1 AND confirmed = 1 AND id != ".$user->id." )")->whereIn('id',$input['attendant'])->get();
                        if($different_users->count() > 0){
                            foreach ($different_users as $different_user_key => $different_user_val) {
                                if(!in_array($different_user_val->id,$attendant_user) && $user->id != $different_user_val->id ){
                                    $inc++;
                                    $team_attendant_user[$inc] = array('user_id' => $different_user_val->id, 'email' =>  $different_user_val->email, 'attendant_type' => 'A',  'team_id' => null);
                                    array_push($attendant_user, $different_user_val->id);
                                }
                            } 
                        }
                    }
                    $event->event_attendants()->create(array('user_id' => $user->id, 'email' =>  $user->email, 'attendant_type' => 'C', 'status' => 1));


                    if(count($team_attendant_user) > 0)
                    {
                        foreach ($team_attendant_user as $team_attendant_user_key => $team_attendant_user_val) {
                            $event_attendant_created = $event->event_attendants()->create($team_attendant_user_val);
                            $notification_message = 'You are invited by ' . $user->name . ' for a new event';
                            $notification = $event->notifications()->create(['type' => 2, 'from_user_id' => $user->id, 'to_user_id' => $team_attendant_user_val['user_id'], 'data' => $notification_message, 'notification_uuid' => Str::uuid()->toString()]);
                            $this->sendFirebaseNotification($notification, 'user_notifications');
                            sendFcmNotification($team_attendant_user_val['user_id'],'Asportcoach', $notification_message);
                        }
                    }
                    DB::commit();
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Event created successfully.';
                    $this->WebApiArray['data'] = $event;
                    return response()->json($this->WebApiArray);  
                }
                throw new Exception("Error Processing Request", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray); 
        }
    }

    public function fetchEvent(Request $request)
    {
       try {

           $input = $request->all();

           $validator = Validator::make($input,[
               'event_id' => 'required|exists:events,id',
           ]);

           if ($validator->fails()) {
               throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
           }

           $user = $this->getAuthenticatedUser();

            $event = Event::selectRaw('id,user_id,title,description,event_color_id,color_code,event_datetime,end_datetime')->with(['event_attendants' => function($query) {
                            $query->where('attendant_type', '=', 'A');
                        }])
                        ->whereHas('event_attendants',function($query) use($user){
                            $query->where(['user_id' => $user->id])->where('status','!=',2);
                        })->with('event_creators:id,name')
                        ->where('id',$input['event_id'])->first();

            if(!empty($event)){
                $event->event_attendants->map(function ($attendant) {
                    $attendant->makeHidden(['id','event_id','user_id','email', 'attendant_type','event_type','team_id','created_at','updated_at']);
                    $attendant->users->makeHidden(['username','user_uuid','role_type','total_balance','remaining_balance','user_details','product_tour','privacy','created_at','updated_at','deleted_status','notification_setting','user_image','user_thumb_image']);
                });

                $event->event_creators->makeHidden(['user_image','user_thumb_image','role_type','total_balance','remaining_balance','user_details']);

                $this->WebApiArray['status'] = true;
                
                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data'] = $event;
                return response()->json($this->WebApiArray);
            }
            throw new Exception("You are not allow to view this event.", 1);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function updateEvent(Request $request)
    {
        DB::beginTransaction();
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }

            $user = $this->getAuthenticatedUser();

            $input = $request->all();

            $validator = Validator::make($input,[
                'title'       => 'required|max:50',
                'attendant'   => 'nullable|array|min:1',
                'attendant.*'   => 'nullable|exists:users,id,status,1,confirmed,1,deleted_status,0',
                'team'   => 'nullable|array|min:1',
                'team.*'   => 'nullable|exists:teams,id,user_id,'.$user->id,
                'category_id'   => 'nullable|exists:event_categories,id,user_id,'.$user->id,
                'event_date'    => 'required|date_format:Y/m/d H:i',
                'event_color_id'    => 'required|exists:event_colors,id',
                'end_datetime'    => 'nullable|date_format:Y/m/d H:i|after_or_equal :'.$request->get('event_date'),
                'event_id' => 'required|exists:events,id'
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $event = Event::find($input['event_id']);

            if(!empty($input['attendant']) && is_array($input['attendant'])) {
                $userChats = Chat::where('chat_type',1)->has('chat_messages')
                    ->where(function ($queryone) use($user) {
                        $queryone->where('one_user_id', $user->id)->orWhere('two_user_id', $user->id);
                    })
                    ->whereHas('one_users',function($querytwo){
                        $querytwo->where('deleted_status','!=',1)->where(['status' => 1, 'confirmed' => 1 ]);
                    })
                    ->whereHas('two_users',function($querythree){
                        $querythree->where('deleted_status','!=',1)->where(['status' => 1, 'confirmed' => 1 ]);
                    })
                    ->selectRaw("one_user_id, two_user_id")
                    ->get();
                $filtered_collection = $userChats->filter(function ($item) use ($input) {
                    if (in_array($item->one_user_id, $input['attendant']) || in_array($item->two_user_id, $input['attendant'])) {
                        return $item;
                    }
                })->values();

                if ($filtered_collection->count() == 0) {
                    throw new Exception("User is not exist in team", 1);
                }
                $filtered_chats = $filtered_collection->toArray();
                $one_user = array_column($filtered_chats, 'one_user_id');
                $two_user = array_column($filtered_chats, 'two_user_id');
                $team_users = array_unique(array_merge($one_user, $two_user));

                if (in_array($user->id, $team_users)) {
                    unset($team_users[array_search($user->id, $team_users)]);
                }
                $filtered_teams = array_values($team_users);
                $input['attendant'] = $filtered_teams;
            }


            if(!empty($input['team']) && is_array($input['team']))
            {
                $checkAcceptedTeam = Team::withCount(['team_users' => function($teamquery){
                    $teamquery->where('team_user.status',1);
                }])->whereIn('id',$input['team'])->get();
                $filteredAcceptedteam = $checkAcceptedTeam->filter(function ($checkAcceptedTeamItem) {
                    if($checkAcceptedTeamItem['team_users_count'] > 0){
                        return $checkAcceptedTeamItem;
                    }
                })->values();
                if($filteredAcceptedteam->count() != $checkAcceptedTeam->count()){
                  throw new Exception("None of your team member have accepted your request yet.", 1);
                }
            }

            $color = EventColor::find($input['event_color_id']);

            $input['event_datetime'] = $input['event_date'] ? Carbon::parse($input['event_date'])->format('Y-m-d H:i:s') : null;
            $input['end_datetime'] = $input['end_datetime'] ? Carbon::parse($input['end_datetime'])->format('Y-m-d H:i:s') : null;
            $input['user_id'] = $user->id;

            //$input['event_color_id'] = $input['event_color_id'];
            //$input['color_code'] = $color->color_code;
            $input['event_color_id'] = $color->id;
            $input['color_code'] = $color->color_code;
            if(empty($input['category_id']))
            {
                if($color->id != 2)
                {
                    throw new Exception("Invalid event color", 1);
                }
            }

            if($event->fill($input)->save()){

                $team_attendant_user = array();
                $attendant_user = array();
                $inc = 0;
                if(!empty($input['team']) && is_array($input['team']))
                {
                    $team = Team::with('team_users')->whereIn('id',$input['team'])->get();

                    foreach ($team as $team_key => $team_value) {
                        if($team_value->team_users->count() > 0){
                            foreach ($team_value->team_users as $team_user_key => $team_user_value) {
                                if($team_user_value->pivot->status == 1) {
                                    if (!in_array($team_user_value->id, $attendant_user) && $user->id != $team_user_value->id) {
                                        $inc++;
                                        $team_attendant_user[$inc] = array('user_id' => $team_user_value->id, 'email' => $team_user_value->email, 'attendant_type' => 'A', 'team_id' => $team_value->id);
                                        array_push($attendant_user, $team_user_value->id);
                                    }
                                }
                            }
                        }
                    }
                }
                if(!empty($input['attendant']) && is_array($input['attendant']))
                {
                    $different_users = User::whereRaw("(status = 1 AND confirmed = 1 AND id != ".$user->id." )")->whereIn('id',$input['attendant'])->get();
                    if($different_users->count() > 0){
                        foreach ($different_users as $different_user_key => $different_user_val) {
                            if(!in_array($different_user_val->id,$attendant_user) && $user->id != $different_user_val->id ){
                                $inc++;
                                $team_attendant_user[$inc] = array('user_id' => $different_user_val->id, 'email' =>  $different_user_val->email, 'attendant_type' => 'A',  'team_id' => null);
                                array_push($attendant_user, $different_user_val->id);
                            }
                        }
                    }
                }

                $event_attendant = EventAttendant::where('event_id', $event->id)->where('attendant_type', 'C')->first();
                $event_attendant->email = $user->email;
                $event_attendant->save();
                if(count($team_attendant_user) > 0)
                {

                    $delete_attendent = EventAttendant::where(['event_id' => $event->id, 'attendant_type' => 'A'])->whereNotIn('user_id', $attendant_user)->delete();
                    
                    $delete = Notification::where(['notifiable_id' => $event->id, 'type' => 2])->whereNotIn('to_user_id', $attendant_user)->delete();

                    foreach ($team_attendant_user as $team_attendant_user_key => $team_attendant_user_val) {
                        $event_attendant = EventAttendant::where(['event_id' => $event->id, 'user_id' => $team_attendant_user_val['user_id']])->first();
                        if(empty($event_attendant)){
                            $event_attendant = new EventAttendant;
                            $event_attendant->event_id = $event->id;
                        }
                        if($event_attendant->fill($team_attendant_user_val)->save()){
                            $getNotification = Notification::where(['notifiable_id' => $event->id, 'type' => 2, 'to_user_id' => $team_attendant_user_val['user_id']])->first();
                            if (!$getNotification) {
                                $notification_message = 'You are invited by ' . $user->name . ' for a new event';
                                $notification = $event->notifications()->create(['type' => 2, 'from_user_id' => $user->id, 'to_user_id' => $team_attendant_user_val['user_id'], 'data' => $notification_message, 'notification_uuid' => Str::uuid()->toString()]);
                                $this->sendFirebaseNotification($notification, 'user_notifications');
                                sendFcmNotification($team_attendant_user_val['user_id'],'Asportcoach', $notification_message);
                            }
                        }else{
                            throw new Exception("Error occured! while updating event.", 1);
                        }
                    }
                }else {
                    EventAttendant::where('event_id', $event->id)->where('attendant_type', 'A')->delete();
                    Notification::where(['notifiable_id' => $event->id, 'type' => 2])->delete();
                }
                DB::commit();
                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Event Updated successfully.';
                $this->WebApiArray['data'] = $event;
                return response()->json($this->WebApiArray);
            }
            throw new Exception("Error Processing Request", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function eventList(Request $request)
    {
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request method not allowed.", 1);
            }

            $user = $this->getAuthenticatedUser();

            $input = $request->all();

            $page = isset($input['page']) ? $input['page'] : 1;

            $yesterday_date = Carbon::now()->subDays(1)->format('Y-m-d');
            $events = (new Event())->newQuery();
            $events = $events->selectRaw('id,user_id,title,description,event_color_id,color_code,event_datetime,end_datetime')->whereHas('event_attendants',function($query) use($user){
                $query->where(['user_id' => $user->id])->where('status','!=',2);
            })->with('event_creators:id,name')->whereDate('event_datetime', '>',$yesterday_date)
                ->orderBy('event_datetime','ASC')
                ->paginate(10, ['*'], 'page', $page);

            $this->WebApiArray['status'] = true;
            if($events->count() > 0){
                $events->map(function ($event) {
                   $event->makeHidden(['event_attendants']);
                   $event->event_creators->makeHidden(['user_image','user_thumb_image','role_type','total_balance','remaining_balance','user_details']);
                });

                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data'] = $events;
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

    public function calenderEventList(Request $request)
    {
        try {
            $user = $this->getAuthenticatedUser();

            $yesterday_date = Carbon::now()->subDays(1)->format('Y-m-d');
            $events = (new Event())->newQuery();
            $events = $events->selectRaw('id,user_id,title,description,event_color_id,color_code,event_datetime,end_datetime')->whereHas('event_attendants',function($query) use($user){
                $query->where(['user_id' => $user->id])->where('status','!=',2);
            })->with('event_creators:id,name')->whereDate('event_datetime', '>',$yesterday_date)
                ->orderBy('event_datetime','ASC')
                ->get();

            foreach ($events as $event) {
                $event['dates'] = [];
                if (!empty($event->end_datetime)) {
                    $start = Carbon::parse($event->event_datetime)->format('Y-m-d');
                    $end = Carbon::parse($event->end_datetime)->format('Y-m-d');
                    $period = CarbonPeriod::create($start, $end);
                    $dates = [];
                    foreach ($period as $date) {
                        array_push($dates,$date->format('Y-m-d'));
                    }
                    $event['dates'] = $dates;
                }
            }

            $this->WebApiArray['status'] = true;
            if($events->count() > 0){
                $events->map(function ($event) {
                    $event->makeHidden(['event_attendants']);
                    $event->event_creators->makeHidden(['user_image','user_thumb_image','role_type','total_balance','remaining_balance','user_details']);
                });

                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data'] = $events;
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

    public function deleteEvent(Request $request)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();

            $validator = Validator::make($input,[
                'event_id' => 'required|exists:events,id',
            ]);

            $user = $this->getAuthenticatedUser();

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $event = Event::where(['id' => $input['event_id']])->first();

            if ($event->user_id != $user->id) {
                $attendant = EventAttendant::where(['event_id' => $event->id, 'user_id' => auth()->id()])->first();
                if (!empty($attendant)) {

                    $notification = Notification::where(['type' => 2, 'notifiable_id' => $event->id, 'to_user_id' => auth()->id()])->first();
                    if (!empty($notification)) {
                        $notification->read_at = Carbon::now();
                        $notification->save();
                    }

                    $attendant->status = 2;
                    $attendant->save();

                    $event_notification_message = auth()->user()->name.' has rejected your invitation to '.$event->title.' event ';
                    $event_notification = $event->notifications()->create(['type' => 5, 'from_user_id' => auth()->id(), 'to_user_id' => $event->user_id, 'data' => $event_notification_message, 'notification_uuid' => Str::uuid()->toString()]);
                    self::sendFirebaseNotification($event_notification, 'user_notifications');
                    sendFcmNotification($event->user_id, 'Asportcoach', $event_notification_message, 'event');

                } else {
                    throw new Exception("not authorized to delete this event.", 1);
                }
            } else {
                Notification::where(['type' => 2, 'notifiable_id' => $event->id])->delete();
                $event->delete();
            }

            DB::commit();

            $this->WebApiArray['status'] = true;

            $this->WebApiArray['message'] = 'Event Deleted Successfully.';
            return response()->json($this->WebApiArray);
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function eventDetail(Request $request)
    {
        try {
            $input = $request->all();

            $validator = Validator::make($input,[
                'event_id' => 'required|exists:events,id',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }
            $user = $this->getAuthenticatedUser();

            $event = Event::selectRaw('id,user_id,title,description,event_color_id,color_code,category_id,event_datetime, end_datetime')
                ->whereHas('event_attendants',function($query) use($user){
                $query->where(['user_id' => $user->id, 'status' => 1]);
            })->with(['event_attendants' => function($query) {
                    $query->where('attendant_type', '=', 'A');
                    $query->whereNull('team_id');
                }])->with('event_creators:id,name')->where(['id' => $input['event_id']])->first();

            if (!empty($event)) {

                if ($event->category) {
                    $event->category->makeHidden(['user_id','color_id','color_code','created_at','updated_at']);
                }

                if ($event->end_datetime) {
                    $event->end_datetime = Carbon::parse($event->end_datetime)->format('Y/m/d H:i');
                }

                if ($event->event_datetime) {
                    $event->event_datetime = Carbon::parse($event->event_datetime)->format('Y/m/d H:i');
                }

                $teams = EventAttendant::where('event_id', $event->id)->with('teams')->whereNotNull('team_id')->groupBy('team_id')->get();

                $filter_team = [];
                foreach ($teams as $team) {
                    $attendant_team['id'] = $team['teams']['id'];
                    $attendant_team['title'] = $team['teams']['title'];
                    array_push($filter_team, $attendant_team);
                }

                $event->teams = $filter_team;

                $filter_user = [];
                foreach ($event->event_attendants as $attendant) {
                    $attendant_user['id'] = $attendant['users']['id'];
                    $attendant_user['name'] = $attendant['users']['name'];
                    array_push($filter_user, $attendant_user);
                }

                $event->user = $filter_user;

                $event->event_creators->makeHidden(['role_type','total_balance','remaining_balance','user_details','user_image','user_thumb_image']);
                $event->makeHidden(['event_attendants']);


                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Event Detail';


                $this->WebApiArray['data'] = $event;
                return response()->json($this->WebApiArray);
            }
            throw new Exception('Event Id not valid',1);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function getEventTeam() {
        try {

            $user = $this->getAuthenticatedUser();

            $teams = Team::selectRaw('id,title,slug')->has('team_users')
                ->whereHas('team_users',function($query){
                    $query->where(['team_user.status' => 1]);
                })->where('user_id', $user->id)->get();


            $this->WebApiArray['status'] = true;
            if (count($teams) > 0) {
                $this->WebApiArray['message'] = 'Team List.';
                $this->WebApiArray['data'] = $teams;
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

    public function calenderYear(Request $request)
    {
        try {
            $user = $this->getAuthenticatedUser();

            $input = $request->all();

            $validator = Validator::make($input,[
                'year' => 'required',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $year = $input['year'];

           $last_date = $year.'-12-31';
            $now_year = Carbon::now()->year;
            if ($year > $now_year) {
                $first_date = $year.'-01-01';
                $first_date = Carbon::parse($first_date)->subDays(1)->format('Y-m-d');
            } else {
                $first_date = Carbon::now()->subDays(1)->format('Y-m-d');
            }

            $events = (new Event())->newQuery();
            $events = $events->selectRaw('id,user_id,title,description,event_color_id,color_code,event_datetime,end_datetime')
                ->whereHas('event_attendants',function($query) use($user){
                    $query->where(['user_id' => $user->id])->where('status','!=',2);
                })->with('event_creators:id,name')
                ->whereDate('event_datetime', '>',$first_date)
                ->whereDate('event_datetime', '<=',$last_date)
                ->orderBy('event_datetime','ASC')
                ->get();

            $events->map(function ($event) {
                $event->makeHidden(['event_attendants']);
                $event->event_creators->makeHidden(['user_image','user_thumb_image','role_type','total_balance','remaining_balance','user_details']);
            });

            $current_month = '';
            $data = [];
            $date_array = [];
            foreach ($events as $event) {
                $month = Carbon::createFromFormat('Y-m-d H:i:s', $event->event_datetime)->format('F');
                    if ($current_month != $month) {
                        $current_month = $month;
                        $date_array['title'] = $month;
                        $first_array = $event;
                        $date_array['date_arr'] =  array($first_array);
                        array_push($data, $date_array);
                    } else {
                        $second_array = $event;
                        array_push($data[count($data)-1]['date_arr'], $second_array);
                    }
            }

            $this->WebApiArray['status'] = true;
            if(count($data) > 0){
                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data'] = $data;
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

    public function calenderMonthData(Request $request)
    {
        try {
            $user = $this->getAuthenticatedUser();

            $input = $request->all();

            $validator = Validator::make($input,[
                'year' => 'required',
                'month' => 'required',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $year = $input['year'];
            $month = $input['month'];

            $last_date = $year.'-'.$month.'-31';

            $now_year = Carbon::now()->year;
            $now_month = Carbon::now()->month;
            if ($year > $now_year || $month > $now_month) {
                $first_date = $year.'-'.$month.'-01';
                $first_date = Carbon::parse($first_date)->subDays(1)->format('Y-m-d');
            } else {
                $first_date = Carbon::now()->subDays(1)->format('Y-m-d');
            }

            $events = (new Event())->newQuery();
            $events = $events->selectRaw('id,user_id,title,description,event_color_id,color_code,event_datetime,end_datetime')
                ->whereHas('event_attendants',function($query) use($user){
                    $query->where(['user_id' => $user->id])->where('status','!=',2);
                })->with('event_creators:id,name')
                ->whereDate('event_datetime', '>',$first_date)
                ->whereDate('event_datetime', '<=',$last_date)
                ->orderBy('event_datetime','ASC')
                ->get();

            $events->map(function ($event) {
                $event->makeHidden(['event_attendants']);
                $event->event_creators->makeHidden(['user_image','user_thumb_image','role_type','total_balance','remaining_balance','user_details']);
            });

            $this->WebApiArray['status'] = true;
            if($events->count() > 0){
                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data'] = $events;
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
}
