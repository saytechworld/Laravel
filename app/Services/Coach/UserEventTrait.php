<?php

namespace App\Services\Coach;

use App\Http\Requests\Coach\UserEventUpdateRequest;
use App\Models\Chat;
use App\Models\EventCategory;
use App\Models\EventColor;
use App\Models\Notification;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception, Validation, Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Event;
use App\Models\EventAttendant;
use App\Http\Requests\Coach\UserEventRequest;

use Kreait\Firebase;    
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;


trait UserEventTrait
{
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $yesterday_date = Carbon::now()->subDays(1)->format('Y-m-d');
        $input = $request->all();
        $user_events = (new Event)->newQuery();
        $user_events =  $user_events->whereHas('event_attendants',function($query){
                                $query->has('users');
                                $query->where(['user_id' => auth()->id()])->where('status','!=',2);
                            })
                            ->whereDate('event_datetime', '>',$yesterday_date)
                            ->orderBy('event_datetime','ASC')
                            ->get();

        $users = array();
        $userChats = Chat::where('chat_type',1)->with('one_users', 'two_users')
            ->has('chat_messages')
            ->where(function($queryone){
                $queryone->where('one_user_id', auth()->id())->orWhere('two_user_id', auth()->id());
            })
            ->whereHas('one_users',function($querytwo){
                $querytwo->where('deleted_status','!=',1)->where(['status' => 1, 'confirmed' => 1 ]);
            })
            ->whereHas('two_users',function($query){
                $query->where('deleted_status','!=',1)->where(['status' => 1, 'confirmed' => 1 ]);
            })->get();

        foreach ($userChats as $userChatKey => $userChat) {
            $users[$userChat->one_users->id != auth()->id() ? $userChat->one_users->id : $userChat->two_users->id] = $userChat->one_users->id != auth()->id() ? $userChat->one_users->name : $userChat->two_users->name;
        }

        $event_colors = EventColor::orderBy('color_sort','ASC')->get();

        $team = Team::has('team_users')
                    ->withCount(['team_users' => function($teamquery){
                        $teamquery->where('team_user.status',1);
                    }])->where('user_id', auth()->id())->get();

        $categories = EventCategory::where('user_id', auth()->id())->get();

        return view('Coach.event.index',compact('user_events','users', 'event_colors', 'team', 'categories'))
            ->with('i', ($request->input('page', 1) - 1) * 2);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserEventRequest $request)
    {
        DB::beginTransaction();
        try {
            if($request->ajax()){
                if(isset($request->validator) && $request->validator->fails()){
                    throw new Exception(implode('<br/>',$request->validator->errors()->all()), 1);
                }

                $input = $request->all();

                if(!empty($input['attendant']) && is_array($input['attendant'])) {
                    $userChats = Chat::where('chat_type',1)->has('chat_messages')
                        ->where(function ($queryone) {
                            $queryone->where('one_user_id', auth()->id())->orWhere('two_user_id', auth()->id());
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

                    if (in_array(auth()->id(), $team_users)) {
                        unset($team_users[array_search(auth()->id(), $team_users)]);
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

                $input['event_datetime'] = $input['event_date'] ? Carbon::parse($input['event_date'])->format('Y-m-d H:i:s') : null;
                $input['end_datetime'] = $input['end_datetime'] ? Carbon::parse($input['end_datetime'])->format('Y-m-d H:i:s') : $input['event_datetime'];
                $input['user_id'] = auth()->id();
                
                $event_color = EventColor::where('id',$input['event_color'])->first();
                if(empty($event_color))
                {
                    throw new Exception("Invalid event color", 1);
                }     
                $input['event_color_id'] = $event_color->id;
                $input['color_code'] = $event_color->color_code;
                if(empty($input['category_id']))
                {
                    if($event_color->id != 2)
                    {
                        throw new Exception("Invalid event color", 1);
                    } 
                    $input['event_color_id'] = $event_color->id;
                    $input['color_code'] = $event_color->color_code;
                }
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
                                        if(!in_array($team_user_value->id,$attendant_user) && auth()->id() != $team_user_value->id ){
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
                        $different_users = User::whereRaw("(status = 1 AND confirmed = 1 AND id != ".auth()->id()." )")->whereIn('id',$input['attendant'])->get();
                        if($different_users->count() > 0){
                            foreach ($different_users as $different_user_key => $different_user_val) {
                                if(!in_array($different_user_val->id,$attendant_user) && auth()->id() != $different_user_val->id ){
                                    $inc++;
                                    $team_attendant_user[$inc] = array('user_id' => $different_user_val->id, 'email' =>  $different_user_val->email, 'attendant_type' => 'A',  'team_id' => null);
                                    array_push($attendant_user, $different_user_val->id);
                                }
                            } 
                        }
                    }
                    $event->event_attendants()->create(array('user_id' => auth()->id(), 'email' =>  auth()->user()->email, 'attendant_type' => 'C', 'status' => 1));
                    if(count($team_attendant_user) > 0)
                    {
                        foreach ($team_attendant_user as $team_attendant_user_key => $team_attendant_user_val) {
                            $event_attendant_created = $event->event_attendants()->create($team_attendant_user_val);
                            $notification = $event->notifications()->create(['type' => 2, 'from_user_id' => auth()->id(), 'to_user_id' => $team_attendant_user_val['user_id'], 'data' => 'You are invited by ' . auth()->user()->name . ' for a new event', 'notification_uuid' => Str::uuid()->toString()]);
                            self::sendFirebaseNotification($notification);
                            sendFcmNotification($team_attendant_user_val['user_id'], 'Asportcoach', 'You are invited by ' . auth()->user()->name . ' for a new event');
                        }
                    }
                    DB::commit();
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['error'] = false;
                    $this->WebApiArray['message'] = 'Event created successfully.';
                    $this->WebApiArray['data']['result'] = $event;
                    return response()->json($this->WebApiArray);  
                }
                throw new Exception("Error Processing Request", 1);
            }
            throw new Exception("HTTP request not allow", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray); 
        }
    }


    public function fetchEventDetail(Request $request, $event_id)
    {
       try {
            if($request->ajax()){
                $event = Event::with('event_attendants')->whereRaw("(id = ".$event_id." AND user_id = ".auth()->id()." )")->first();
                if(!empty($event)){
                    $users = array();
                    $userChats = Chat::where('chat_type',1)->with('one_users', 'two_users')
                        ->has('chat_messages')
                        ->where(function($queryone){
                            $queryone->where('one_user_id', auth()->id())->orWhere('two_user_id', auth()->id());
                        })
                        ->whereHas('one_users',function($query){
                            $query->where('deleted_status','!=',1)->where(['status' => 1, 'confirmed' => 1 ]);
                        })
                        ->whereHas('two_users',function($query){
                            $query->where('deleted_status','!=',1)->where(['status' => 1, 'confirmed' => 1 ]);
                        })->get();

                    foreach ($userChats as $userChatKey => $userChat) {
                        $users[$userChat->one_users->id != auth()->id() ? $userChat->one_users->id : $userChat->two_users->id] = $userChat->one_users->id != auth()->id() ? $userChat->one_users->name : $userChat->two_users->name;
                    }

                    $categories = EventCategory::where('user_id', auth()->id())->get();
                    $event_colors = EventColor::orderBy('color_sort','ASC')->get();
                    $team = Team::withCount(['team_users' => function($teamquery){
                                $teamquery->where('team_user.status',1);
                            }])->where('user_id', auth()->id())->get();
                    $html = view('Coach.event.edit_modal',compact('event','users', 'event_colors', 'team', 'categories'))->render();
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['error'] = false;
                    $this->WebApiArray['message'] = 'Record found.';
                    $this->WebApiArray['data']['result'] = $html;
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("You are not allow to edit this event.", 1);
            }
            throw new Exception("HTTP request not allow", 1);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }

    }


    public function fetchEvent(Request $request, $event_id)
    {
       try {
            if($request->ajax()){
                $event = Event::with('event_attendants')
                            ->whereHas('event_attendants',function($query){
                                $query->where(['user_id' => auth()->id()])->where('status','!=',2);
                            })
                            ->where('id',$event_id)->first();
                if(!empty($event)){

                    $html = view('Coach.event.view_modal',compact('event'))->render();
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['error'] = false;
                    $this->WebApiArray['message'] = 'Record found.';
                    $this->WebApiArray['data']['result'] = $html;
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("You are not allow to view this event.", 1);
            }
            throw new Exception("HTTP request not allow", 1);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }

    }

        public function update(UserEventUpdateRequest $request, Event $event)
    {
        DB::beginTransaction();
        try {
            if($request->ajax()){
                if(isset($request->validator) && $request->validator->fails()){
                    throw new Exception(implode('<br/>',$request->validator->errors()->all()), 1);
                }
                if ($event->user_id != auth()->id()) {
                    throw new Exception("Access Denied", 1);
                }

                $input = $request->all();

                if(!empty($input['attendant']) && is_array($input['attendant'])) {
                    $userChats = Chat::where('chat_type',1)->has('chat_messages')
                        ->where(function ($queryone) {
                            $queryone->where('one_user_id', auth()->id())->orWhere('two_user_id', auth()->id());
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

                    if (in_array(auth()->id(), $team_users)) {
                        unset($team_users[array_search(auth()->id(), $team_users)]);
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
                $input['event_datetime'] = $input['event_date'] ? Carbon::parse($input['event_date'])->format('Y-m-d H:i:s') : null;
                $input['end_datetime'] = $input['end_datetime'] ? Carbon::parse($input['end_datetime'])->format('Y-m-d H:i:s') : $input['event_datetime'];
                $input['user_id'] = auth()->id();

                if (!isset($input['category_id']) && empty($input['category_id'])) {
                    $input['category_id'] = null;
                }

                //$event_color = explode(";",$input['event_color']);
                //$input['event_color_id'] = $event_color[0];
                //$input['color_code'] = $event_color[1];



                $event_color = EventColor::where('id',$input['event_color'])->first();
                if(empty($event_color))
                {
                    throw new Exception("Invalid event color", 1);
                }     
                $input['event_color_id'] = $event_color->id;
                $input['color_code'] = $event_color->color_code;
                if(empty($input['category_id']))
                {
                    if($event_color->id != 2)
                    {
                        throw new Exception("Invalid event color", 1);
                    } 
                    $input['event_color_id'] = $event_color->id;
                    $input['color_code'] = $event_color->color_code;
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
                                        if (!in_array($team_user_value->id, $attendant_user) && auth()->id() != $team_user_value->id) {
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
                        $different_users = User::whereRaw("(status = 1 AND confirmed = 1 AND id != ".auth()->id()." )")->whereIn('id',$input['attendant'])->get();
                        if($different_users->count() > 0){
                            foreach ($different_users as $different_user_key => $different_user_val) {
                                if(!in_array($different_user_val->id,$attendant_user) && auth()->id() != $different_user_val->id ){
                                    $inc++;
                                    $team_attendant_user[$inc] = array('user_id' => $different_user_val->id, 'email' =>  $different_user_val->email, 'attendant_type' => 'A',  'team_id' => null);
                                    array_push($attendant_user, $different_user_val->id);
                                }
                            } 
                        }
                    }

                    $event_attendant = EventAttendant::where('event_id', $event->id)->where('attendant_type', 'C')->first();
                    $event_attendant->email = auth()->user()->email;
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
                                    $notification = $event->notifications()->create(['type' => 2, 'from_user_id' => auth()->id(), 'to_user_id' => $team_attendant_user_val['user_id'], 'data' => 'You are invited by ' . auth()->user()->name . ' for a new event', 'notification_uuid' => Str::uuid()->toString()]);
                                    self::sendFirebaseNotification($notification);
                                    sendFcmNotification($team_attendant_user_val['user_id'], 'Asportcoach', 'You are invited by ' . auth()->user()->name . ' for a new event');
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
                    $this->WebApiArray['error'] = false;
                    $this->WebApiArray['message'] = 'Event Updated successfully.';
                    $this->WebApiArray['data']['result'] = $event;
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("Error Processing Request", 1);
            }
            throw new Exception("HTTP request not allow", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function eventList(Request $request)
    {
        $yesterday_date = Carbon::now()->subDays(1)->format('Y-m-d');
        $events = (new Event())->newQuery();
        $events = $events->whereHas('event_attendants',function($query){
            $query->where(['user_id' => auth()->id()])->where('status','!=',2);
        })->whereDate('event_datetime', '>',$yesterday_date)
            ->orderBy('event_datetime','ASC')
            ->paginate(10);

        $users = array();
        $userChats = Chat::with('one_users', 'two_users')
            ->has('chat_messages')
            ->where(function($queryone){
                $queryone->where('one_user_id', auth()->id())->orWhere('two_user_id', auth()->id());
            })
            ->whereHas('one_users',function($query){
                $query->where('deleted_status','!=',1)->where(['status' => 1, 'confirmed' => 1 ]);
            })
            ->whereHas('two_users',function($query){
                $query->where('deleted_status','!=',1)->where(['status' => 1, 'confirmed' => 1 ]);
            })->get();
        foreach ($userChats as $userChatKey => $userChat) {
            $users[$userChat->one_users->id != auth()->id() ? $userChat->one_users->id : $userChat->two_users->id] = $userChat->one_users->id != auth()->id() ? $userChat->one_users->email : $userChat->two_users->email;
        }

        $event_colors = EventColor::all();

        return view('Coach.event.list',compact('events', 'users', 'event_colors'))
            ->with('i', ($request->input('page', 1) - 1) * 10);
    }

    public function delete(Request $request, Event $event)
    {
        if ($event->user_id != auth()->id()) {
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
                self::sendFirebaseNotification($event_notification);
                sendFcmNotification($event->user_id, 'Asportcoach', $event_notification_message, 'event');

            } else {
                return redirect()->back()->withErrors("You are not in this event");
            }
        } else {
            Notification::where(['type' => 2, 'notifiable_id' => $event->id])->delete();
            $event->delete();
        }

        return redirect()->back()->withSuccess("Event Deleted successfully.");
    }

    public function eventAction(Request $request, $event_id, $action)
    {
        DB::beginTransaction();
        try {

            $event = Event::where(['id' => $event_id])->first();

            if ($event->user_id != auth()->id()) {
                $attendant = EventAttendant::where(['event_id' => $event->id, 'user_id' => auth()->id()])->first();
                if (!empty($attendant)) {
                    $notification = Notification::where(['type' => 2, 'notifiable_id' => $event->id, 'to_user_id' => auth()->id()])->first();
                    if (!empty($notification)) {
                        $notification->read_at = Carbon::now();
                        $notification->save();
                    }

                    if ($action == 1) {
                        $attendant->status = 1;
                        $attendant->save();
                        $event_notification_message = auth()->user()->name . ' has accepted your invitation to ' . $event->title . ' event';
                        $message = 'Event Accepted Successfully';
                    } else {
                        $attendant->status = 2;
                        $attendant->save();
                        $event_notification_message = auth()->user()->name . ' has rejected your invitation to ' . $event->title . ' event';
                        $message = 'Event Rejected Successfully';
                    }
                    $event_notification = $event->notifications()->create(['type' => 5, 'from_user_id' => auth()->id(), 'to_user_id' => $event->user_id, 'data' => $event_notification_message, 'notification_uuid' => Str::uuid()->toString()]);
                    self::sendFirebaseNotification($event_notification);
                    sendFcmNotification($event->user_id, 'Asportcoach', $event_notification_message, 'event');
                    DB::commit();
                    return redirect()->back()->withSuccess($message);

                } else {
                    throw new Exception("not authorize.", 1);
                }
            }
            throw new Exception("not authorize.", 1);
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->withSuccess($e->getMessage());
        }
    }

    public function sendFirebaseNotification($notification)
    {
        $serviceAccount = ServiceAccount::fromJsonFile(public_path(env('FIREBASE_CREDENTIALS')));
        $firebase = (new Factory)
        ->withServiceAccount($serviceAccount)
        ->withDatabaseUri(env('FIREBASE_DATABASE'))
        ->create();
        $database = $firebase->getDatabase();
        $user_notification = $database->getReference('user_notifications')->push($notification);
        return true;
    }

}
