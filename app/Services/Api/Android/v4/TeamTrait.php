<?php

namespace App\Services\Api\Android\v4;

use App\Models\Chat;
use App\Models\EventAttendant;
use App\Models\GroupUsers;
use App\Models\Notification;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Exception, Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

trait TeamTrait
{
    public function getTeam(Request $request) {
        try {

            if(!$request->isMethod('post'))
            {
                throw new Exception("Request method not allowed.", 1);
            }

            $input = $request->all();
            $user = $this->getAuthenticatedUser();

            $page = isset($input['page']) ? $input['page'] : 1;

            $teams = (new Team())->newQuery();
            $teams = $teams->selectRaw('id,title,slug')->with('team_users:id,name,user_uuid,username')
                ->where('user_id', $user->id)
                ->orderBy('title','ASC')->paginate(25, ['*'], 'page', $page);

            $this->WebApiArray['status'] = true;
            if ($teams->count() > 0) {

                $teams->map(function ($team) {
                    $team->team_users->map(function ($user) {
                        $user->makeHidden(['role_type', 'total_balance', 'remaining_balance', 'user_details', 'pivot']);
                    });
                });

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

    public function getTeamUser() {
        try {

            $user = $this->getAuthenticatedUser();

            $users = array();
            $userChats = Chat::with('one_users:id,name', 'two_users:id,name')->where('chat_type', 1)
                ->has('chat_messages')
                ->where(function($queryone) use ($user){
                    $queryone->where('one_user_id', $user->id)->orWhere('two_user_id', $user->id);
                })
                ->whereHas('one_users',function($querytwo){
                    $querytwo->where('deleted_status','!=',1)->where(['status' => 1, 'confirmed' => 1 ]);
                })
                ->whereHas('two_users',function($query){
                    $query->where('deleted_status','!=',1)->where(['status' => 1, 'confirmed' => 1 ]);
                })->get();

            $userChats->map(function ($chat) {
                $chat->one_users->makeHidden(['remaining_balance','total_balance', 'user_details', 'roles', 'role_type']);
                $chat->two_users->makeHidden(['remaining_balance','total_balance', 'user_details', 'roles', 'role_type']);
            });

            foreach ($userChats as $userChatKey => $userChat) {
                array_push($users,$userChat->one_users->id != $user->id ? $userChat->one_users : $userChat->two_users);
            }

            $this->WebApiArray['status'] = true;

            if (count($users) > 0) {
                $this->WebApiArray['message'] = 'Users List.';
                $this->WebApiArray['data'] = $users;
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

    public function createTeam(Request $request) {
        DB::beginTransaction();
        try {

            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }

            $input = $request->all();

            $validator = Validator::make($input,[
                'title'       => 'required|max:50',
                'member'   => 'nullable|array',
                'member.*'   => 'nullable|exists:users,id,status,1,confirmed,1,deleted_status,0',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            if (isset($input['member']) && count($input['member']) > 0) {

                if (in_array($user->id, $input['member'])){
                    throw new Exception('You can not add yourself as member', 1);
                }

                $userChats = Chat::has('chat_messages')
                    ->where(function($queryone) use ($user){
                        $queryone->where('one_user_id', $user->id)->orWhere('two_user_id', $user->id);
                    })
                    ->whereHas('one_users',function($querytwo){
                        $querytwo->where('deleted_status','!=',1)->where(['status' => 1, 'confirmed' => 1 ]);
                    })
                    ->whereHas('two_users',function($query){
                        $query->where('deleted_status','!=',1)->where(['status' => 1, 'confirmed' => 1 ]);
                    })->selectRaw("one_user_id, two_user_id")
                    ->get();
                $filtered_collection = $userChats->filter(function($item) use ($input)
                {
                    if(in_array($item->one_user_id, $input['member']) || in_array($item->two_user_id,$input['member']))
                    {
                        return $item;
                    }
                })->values();

                if ($filtered_collection->count() == 0) {
                    throw new Exception("User is not exist in chat.", 1);
                }
                $filtered_chats = $filtered_collection->toArray();
                $one_user  = array_column($filtered_chats, 'one_user_id');
                $two_user = array_column($filtered_chats, 'two_user_id');
                $team_users = array_unique(array_merge($one_user,$two_user));

                if (in_array($user->id, $team_users))
                {
                    unset($team_users[array_search($user->id,$team_users)]);
                }
                $filtered_teams = array_values($team_users);
                $input['member'] = $filtered_teams;
            }

            $input['user_id'] = $user->id;
            $team = new Team;
            if($team->fill($input)->save()){

                if (isset($input['member']) && count($input['member']) > 0) {

                    $team->team_users()->sync($input['member']);

                    foreach ($input['member'] as $team_user) {
                        $notification_message = 'You invited for team request by ' . $user->name . '.';
                        $notification = $team->notifications()->create(['type' => 3, 'from_user_id' => $user->id, 'to_user_id' => $team_user, 'data' => $notification_message, 'notification_uuid' => Str::uuid()->toString()]);

                        $notification_data = Notification::where('id', $notification->id)->first();

                        $this->sendFirebaseNotification($notification_data, 'user_notifications');
                        sendFcmNotification($team_user, 'Asportcoach', $notification_message);
                    }
                }

                DB::commit();

                $this->WebApiArray['status'] = true;

                $this->WebApiArray['message'] = 'Team created successfully.';
                return response()->json($this->WebApiArray);
            }
            throw new Exception("Error Processing Request", 1);

        }  catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function updateTeam(Request $request) {
        DB::beginTransaction();
        try {

            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }

            $input = $request->all();

            $validator = Validator::make($input,[
                'title'       => 'required|max:50',
                'member'   => 'nullable|array',
                'member.*'   => 'nullable|exists:users,id,status,1,confirmed,1,deleted_status,0',
                'team_id' => 'required|numeric|exists:teams,id'
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            if (isset($input['member']) && count($input['member']) > 0) {

                if (in_array($user->id, $input['member'])) {
                    throw new Exception('You can not add yourself as member', 1);
                }

                $userChats = Chat::has('chat_messages')
                    ->where(function ($queryone) use ($user) {
                        $queryone->where('one_user_id', $user->id)->orWhere('two_user_id', $user->id);
                    })
                    ->whereHas('one_users',function($querytwo){
                        $querytwo->where('deleted_status','!=',1)->where(['status' => 1, 'confirmed' => 1 ]);
                    })
                    ->whereHas('two_users',function($query){
                        $query->where('deleted_status','!=',1)->where(['status' => 1, 'confirmed' => 1 ]);
                    })->selectRaw("one_user_id, two_user_id")
                    ->get();
                $filtered_collection = $userChats->filter(function ($item) use ($input) {
                    if (in_array($item->one_user_id, $input['member']) || in_array($item->two_user_id, $input['member'])) {
                        return $item;
                    }
                })->values();

                if ($filtered_collection->count() == 0) {
                    throw new Exception("User is not exist in chat.", 1);
                }
                $filtered_chats = $filtered_collection->toArray();
                $one_user = array_column($filtered_chats, 'one_user_id');
                $two_user = array_column($filtered_chats, 'two_user_id');
                $team_users = array_unique(array_merge($one_user, $two_user));

                if (in_array($user->id, $team_users)) {
                    unset($team_users[array_search($user->id, $team_users)]);
                }
                $filtered_teams = array_values($team_users);
                $input['member'] = $filtered_teams;
            }

            $team = Team::with('team_users')->whereRaw("(id = ".$input['team_id']." AND user_id = ".$user->id." )")->first();
            if (!empty($team) && $team->user_id == $user->id) {
                if($team->fill($input)->save()){

                    if (isset($input['member']) && count($input['member']) > 0) {
                        $team->team_users()->sync($input['member']);

                        //Delete Notification
                        Notification::where(['notifiable_id' => $input['team_id'], 'type' => 3])->whereNotIn('to_user_id', $input['member'])->delete();

                        //Delete Event Attendant
                        EventAttendant::where(['team_id' => $input['team_id']])->whereNotIn('user_id', $input['member'])->delete();

                        foreach ($input['member'] as $team_user) {
                            $getNotification = Notification::where(['notifiable_id' => $input['team_id'], 'to_user_id' => $team_user, 'type' => 3])->first();
                            if (!$getNotification) {
                                $notification_message = 'You invited for team request by ' . $user->name . '.';
                                $notification = $team->notifications()->create(['type' => 3, 'from_user_id' => $user->id, 'to_user_id' => $team_user, 'data' => $notification_message, 'notification_uuid' => Str::uuid()->toString()]);
                                $notification_data = Notification::where('id', $notification->id)->first();
                                $this->sendFirebaseNotification($notification_data,'user_notifications');
                                sendFcmNotification($team_user, 'Asportcoach', $notification_message);
                            }
                        }
                    } else {
                        //Delete Notification
                        Notification::where(['notifiable_id' => $input['team_id'], 'type' => 3])->delete();

                        //Delete Event Attendant
                        EventAttendant::where(['team_id' => $input['team_id']])->delete();

                        $team->team_users()->detach();
                    }

                    DB::commit();

                    $this->WebApiArray['status'] = true;

                    $this->WebApiArray['message'] = 'Team Updated successfully.';
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("Error Processing Request", 1);
            }
            throw new Exception("Un-authorized", 1);

        }  catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function teamDetail(Request $request)
    {
        try {
            $input = $request->all();

            $validator = Validator::make($input,[
                'team_id'   =>  'required|exists:teams,id',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            $team = Team::with('team_users:id,name,user_uuid,username')->selectRaw('id,title,slug')->where(['user_id' => $user->id, 'id' => $input['team_id']])->first();

            if (!empty($team)) {
                $team->team_users->map(function ($user) {
                   $user->makeHidden(['role_type','total_balance','remaining_balance','user_details']);
                   $user->pivot->makeHidden(['team_id','user_id']);
                });

                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Team Detail';
                $this->WebApiArray['data'] = $team;
                return response()->json($this->WebApiArray);
            }
            throw new Exception('Team id not valid', 1);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function deleteTeam(Request $request )
    {
        DB::beginTransaction();
        try {
            $input =$request->all();
            $validator = Validator::make($input,[
                'team_id' => 'required|exists:teams,id'
            ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>', $validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            $team = Team::where(['user_id' => $user->id, 'id' => $input['team_id']])->first();
            if (!empty($team)) {
                if ($team->delete()) {
                    $team->notifications()->delete();
                    DB::commit();
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Team deleted successfully';
                    return response()->json($this->WebApiArray);
                }
                throw new Exception('Error Processing Request', 1);
            }
            throw new Exception('You are not authorize to delete this',1);
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function createGroupByTeam(Request $request) {
        DB::beginTransaction();
        try {

            $input =$request->all();
            $validator = Validator::make($input,[
                'team_id' => 'required|exists:teams,id'
            ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>', $validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            $team_id = $input['team_id'];

            $team = Team::find($input['team_id']);
            print_r($team->id);
            if ($team->user_id != $user->id) {
                throw new Exception('Un-authorized');
            }

            $checkAcceptedTeam = Team::withCount(['team_users' => function($teamquery){
                $teamquery->where('team_user.status',1);
            }])->where('id',$team_id)->get();

            $filteredAcceptedteam = $checkAcceptedTeam->filter(function ($checkAcceptedTeamItem) {
                if($checkAcceptedTeamItem['team_users_count'] > 0){
                    return $checkAcceptedTeamItem;
                }
            })->values();

            if($filteredAcceptedteam->count() != $checkAcceptedTeam->count()){
                throw new Exception("None of your team member have accepted your request yet.", 1);
            }

            $chat = Chat::where('team_id', $team_id)->first();
            if (!empty($chat)) {

                $chat_data = (new Chat)->newQuery();
                $chat_data = $chat_data->where('chat_uuid', $chat->chat_uuid)->withCount(['chat_messages' => function($query) use($user){
                    $query->whereRaw("( user_id != ".$user->id." AND  read_flag != 1 )")
                        ->whereRaw("(group_read_message is null OR NOT FIND_IN_SET(".$user->id.",group_read_message))");
                }])->whereRaw("(one_user_id = ".$user->id." OR two_user_id = ".$user->id." )")
                    ->orWhereHas('group_users',function ( $subquery ) use($user){
                        $subquery->where('user_id', $user->id);
                    })
                    ->with(['one_users:id,name,username','two_users:id,name,username', 'group_users.users:id,name,username'])
                    ->first();

                $chat_data->one_users->makeHidden(['remaining_balance','total_balance', 'user_details', 'roles']);
                $chat_data->two_users->makeHidden(['remaining_balance','total_balance', 'user_details', 'roles']);
                $chat_data->group_users->map(function ($group_users) {
                    $group_users->users->makeHidden(['remaining_balance','total_balance', 'user_details', 'roles']);
                });

                $this->WebApiArray['data'] = $chat_data;
                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Group chat created';
                return response()->json($this->WebApiArray);
            } else {

                $team = Team::with(['team_users' => function($query){
                    $query->where('team_user.status',1);
                }])->where('id',$team_id)->first();

                $chat = new Chat();

                $chat_data['chat_uuid'] =  Str::uuid()->toString();
                $chat_data['one_user_id'] = auth()->id();
                $chat_data['two_user_id'] = auth()->id();
                $chat_data['team_id'] = $team_id;
                $chat_data['chat_type'] = 2;
                $chat_data['group_name'] = $team->title;

                if ($chat->fill($chat_data)->save()) {

                    $group_user_admin = new GroupUsers();
                    $group_user_admin->chat_id = $chat->id;
                    $group_user_admin->user_id = auth()->id();
                    $group_user_admin->admin = 1;
                    $group_user_admin->save();

                    foreach ($team->team_users as $team_user) {
                        $group_user = new GroupUsers();
                        $group_user->chat_id = $chat->id;
                        $group_user->user_id = $team_user->id;
                        $group_user->admin = 0;
                        $group_user->save();
                    }
                    DB::commit();

                    $chat_data = (new Chat)->newQuery();
                    $chat_data = $chat_data->where('chat_uuid', $chat->chat_uuid)->withCount(['chat_messages' => function($query) use($user){
                        $query->whereRaw("( user_id != ".$user->id." AND  read_flag != 1 )")
                            ->whereRaw("(group_read_message is null OR NOT FIND_IN_SET(".$user->id.",group_read_message))");
                    }])->whereRaw("(one_user_id = ".$user->id." OR two_user_id = ".$user->id." )")
                        ->orWhereHas('group_users',function ( $subquery ) use($user){
                            $subquery->where('user_id', $user->id);
                        })
                        ->with(['one_users:id,name,username','two_users:id,name,username', 'group_users.users:id,name,username'])
                        ->first();

                    $chat_data->one_users->makeHidden(['remaining_balance','total_balance', 'user_details', 'roles']);
                    $chat_data->two_users->makeHidden(['remaining_balance','total_balance', 'user_details', 'roles']);
                    $chat_data->group_users->map(function ($group_users) {
                        $group_users->users->makeHidden(['remaining_balance','total_balance', 'user_details', 'roles']);
                    });

                    $this->WebApiArray['data'] = $chat_data;
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Group chat created';
                    return response()->json($this->WebApiArray);
                }
            }
            throw new Exception("Error Processing Request", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

}
