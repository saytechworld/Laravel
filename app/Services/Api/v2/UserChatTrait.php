<?php

namespace App\Services\Api\v2;

use App\Models\Chat;
use App\Models\GroupUsers;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Report;
use App\Models\SessionRequest;
use App\Models\Team;
use App\Models\User;
use App\Models\UserFolder;
use App\Models\Video;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception, Validation, Validator;
use Illuminate\Support\Str;
use Cartalyst\Stripe\Stripe;
use Cartalyst\Stripe\Exception\BadRequestException;
use Cartalyst\Stripe\Exception\CardErrorException;
use Cartalyst\Stripe\Exception\InvalidRequestException;
use Cartalyst\Stripe\Exception\NotFoundException;

trait UserChatTrait
{

    public function startChatting(Request $request)
    {
        DB::beginTransaction();
        try{
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }
            $input = $request->all();
            $validator = Validator::make($input,[
                'user_uuid'   =>  'required|exists:users,user_uuid',
            ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            $receiver = User::whereHas('roles',function($query){
                $query->whereIn('role_id',[3,4]);
            })->where('id','!=', $user->id)->whereRaw("(user_uuid = '".$input['user_uuid']."' AND status = 1 AND confirmed = 1 AND deleted_status != 1 )")->first();

            if (!empty($receiver)) {
                $roles = $user->roles->pluck('id')->toArray();
                if (count($roles) > 0 && (in_array(3, $roles) || in_array(4, $roles))) {
                    $checkchat = Chat::whereRaw("( (one_user_id = ".$user->id." AND  two_user_id = ".$receiver->id.") OR (two_user_id = ".$user->id." AND  one_user_id = ".$receiver->id."))")->first();
                    if(empty($checkchat)){
                        $chatData = new Chat();
                        $chatData->one_user_id = $user->id;
                        $chatData->two_user_id = $receiver->id;
                        $chatData->chat_uuid = Str::uuid()->toString();
                        $chatData->save();
                        DB::commit();
                    }
                    $chat = Chat::whereRaw("( (one_user_id = ".$user->id." AND  two_user_id = ".$receiver->id.") OR (two_user_id = ".$user->id." AND  one_user_id = ".$receiver->id.")    )")
                        ->with(['one_users:id,name,username','two_users:id,name,username', 'group_users'])->withCount(['chat_messages' => function($query) use($user){
                            $query->whereRaw("( user_id != ".$user->id." AND  read_flag != 1 )");
                        }])->first();
                    if (!empty($chat)) {

                        $chat->one_users->makeHidden(['remaining_balance','total_balance', 'user_details', 'roles']);
                        $chat->two_users->makeHidden(['remaining_balance','total_balance', 'user_details', 'roles']);

                        $this->WebApiArray['status'] = true;
                        $this->WebApiArray['data'] = $chat;
                        $this->WebApiArray['message'] = "Chat Initiate Successfully";
                        return response()->json($this->WebApiArray);
                    }
                    throw new Exception("Chat not Initiate.", 1);
                }
                throw new Exception("You can't initiated this chat! You are not belongs to athlete or coach role.", 1);
            }
            throw new Exception("This coach doesn't belong to our database", 1);
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function fetchChatUser(Request $request)
    {
        try {

            $user = $this->getAuthenticatedUser();

            $input = $request->all();

            $filter = isset($input['filter']) ? $input['filter'] : 'A';


            if ($filter == 'O') {
                $directtion = 'ASC';
            } else {
                $directtion = 'DESC';
            }

            $chatusers = (new Chat)->newQuery();
            $chatusers = $chatusers->withCount(['chat_messages' => function($query) use($user){
                $query->whereRaw("( user_id != ".$user->id." AND  read_flag != 1 )")
                    ->whereRaw("(group_read_message is null OR NOT FIND_IN_SET(".$user->id.",group_read_message))");
            }])->whereRaw("(one_user_id = ".$user->id." OR two_user_id = ".$user->id." )")
                ->orWhereHas('group_users',function ( $subquery ) use($user){
                    $subquery->where('user_id', $user->id);
                })
                ->with(['one_users:id,name,username','two_users:id,name,username', 'group_users.users:id,name,username'])
                ->orderBy('updated_at',$directtion)
                ->get();


            $chatusers->each(function ($item , $key) use ($user) {
                if ($item->one_users->id == $user->id) {
                    $item['chat_user'] = $item->two_users->name;
                } else {
                    $item['chat_user'] = $item->one_users->name;
                }
                if ($item->chat_type == 2) {
                    $item->group_users->each(function ($item1, $key) use($item, $user) {
                        if ($item1->user_id == $user->id && $item1->status != 1) {
                            $message_count = Message::where('chat_id', $item->id)->where('created_at', '<=', $item1->updated_at)
                                ->whereRaw("( user_id != ".$user->id." AND  read_flag != 1 ) && (group_read_message is null OR NOT FIND_IN_SET(".$user->id.",group_read_message))")->count();
                            $item->chat_messages_count = $message_count;
                        }
                    });
                    $item['chat_user'] = $item->group_name;
                }
            });

            $chatusers->map(function ($chat) {
                $chat->one_users->makeHidden(['remaining_balance','total_balance', 'user_details', 'roles']);
                $chat->two_users->makeHidden(['remaining_balance','total_balance', 'user_details', 'roles']);
                $chat->group_users->map(function ($group_users) {
                    $group_users->users->makeHidden(['remaining_balance','total_balance', 'user_details', 'roles']);
                });
            });


            $this->WebApiArray['status'] = true;
            if($chatusers->count() > 0){
                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data'] = $chatusers;
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

    public function fetchChatUserDetail(Request $request)
    {
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }
            $input = $request->all();
            $validator = Validator::make($input,[
                    'chatting_id'   =>  'required|exists:chats,chat_uuid',
                    'user_id'     => 'required|exists:users,id',
                ],
                    [
                        'chatting_id.required' => 'The chat id is required.',
                        'chatting_id.exists' => 'The chat id is invalid.',
                    ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            $check_chat = Chat::where('chat_uuid', $input['chatting_id'])->first();

                if ($check_chat->chat_type == 1) {
                    $user_chat = Chat::with(['one_users','two_users'])
                        ->has('one_users')
                        ->has('two_users')->where('chat_uuid', $input['chatting_id'])
                        ->whereRaw("( ((one_user_id = ".$user->id." AND  two_user_id = ".$input['user_id'].") OR (one_user_id = ".$input['user_id']." AND  two_user_id = ".$user->id.")))")
                        ->first();

                    $user_details = User::where('id',$input['user_id'])->first();
                } else {
                    $user_chat = Chat::with(['one_users','two_users'])
                        ->has('one_users')
                        ->has('two_users')->where('chat_uuid', $input['chatting_id'])
                        ->WhereHas('group_users',function ( $subquery ) use ($user){
                            $subquery->where('user_id', $user->id);
                        })->first();

                    $user_details = GroupUsers::with('users')->where(['chat_id' =>$user_chat->id, 'user_id' => $user->id])->first();
                }
                if(!empty($user_chat) && !empty($user_details) )
                {
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Record found.';
                    $this->WebApiArray['user'] = $user_details;
                    $this->WebApiArray['chat'] = $user_chat;
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("Invalid user.", 1);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function fetchUserMessage(Request $request)
    {
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }
            $input = $request->all();
            $validator = Validator::make($input,[
                'chatting_id'   =>  'required|exists:chats,chat_uuid',
            ],
                [
                    'chatting_id.required' => 'The chat id is required.',
                    'chatting_id.exists' => 'The chat id is invalid.',
                ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            $page = isset($input['page']) ? $input['page'] : 1;

            $user_messages  = (new Message)->newQuery()->with('senders:id,name');
            $user_messages = $user_messages->selectRaw('id,message_uuid,chat_id,user_id,message,thumbnail,message_type,read_flag,delete_one,delete_two,delete_everyone,created_at')->whereNull('delete_everyone');
            $user_messages  = $user_messages->whereHas('user_conversations',function($query) use($input){
                $query->where('chat_uuid',$input['chatting_id']);
            })->whereRaw("((delete_one is null or delete_one != ".$user->id.") AND (delete_two is null or  delete_two != ".$user->id."))")->with(['senders:id,name'])->orderBy('id', 'DESC')->paginate(25, ['*'], 'page', $page);

            $this->WebApiArray['status'] = true;
            if($user_messages->count() > 0){
                $user_messages->map(function ($user_message) {
                    $user_message->makeHidden(['user_id', 'thumbnail', 'read_flag','delete_one', 'delete_two', 'delete_everyone','message_created_date']);
                    $user_message->senders->makeHidden(['role_type', 'remaining_balance','total_balance', 'user_details', 'roles']);
                });

                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data'] = $user_messages;
                $this->WebApiArray['statusCode'] = 0;
            } else {
                $this->WebApiArray['message'] = 'No record found.';
                $this->WebApiArray['statusCode'] = 1;
            }
            return response()->json($this->WebApiArray);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function fetchGroupMessage(Request $request)
    {
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }
            $input = $request->all();
            $validator = Validator::make($input,[
                'chatting_id'   =>  'required|exists:chats,chat_uuid',
            ],
                [
                    'chatting_id.required' => 'The chat id is required.',
                    'chatting_id.exists' => 'The chat id is invalid.',
                ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            $page = isset($input['page']) ? $input['page'] : 1;

            $group_user = GroupUsers::whereHas('chat',function($query) use($input){
                $query->where('chat_uuid',$input['chatting_id']);
            })->where('user_id', $user->id)->first();

            if (!empty($group_user)) {
                if ($group_user->status == 1) {
                    $user_messages = (new Message)->newQuery()->with('senders:id,name');
                    $user_messages = $user_messages->whereNull('delete_everyone');
                    $user_messages = $user_messages->whereHas('user_conversations', function ($query) use ($input) {
                        $query->where('chat_uuid', $input['chatting_id']);
                    })->whereRaw("(group_delete_message is null OR NOT FIND_IN_SET(" . $user->id . ",group_delete_message))")->with(['senders:id,name'])->orderBy('id', 'DESC')->paginate(25);
                } else {
                    $user_messages = (new Message)->newQuery()->with('senders:id,name');
                    $user_messages = $user_messages->whereNull('delete_everyone');
                    $user_messages = $user_messages->whereHas('user_conversations', function ($query) use ($input) {
                        $query->where('chat_uuid', $input['chatting_id']);
                    })->whereRaw("(group_delete_message is null OR NOT FIND_IN_SET(" . $user->id . ",group_delete_message))")->with(['senders:id,name'])
                        ->where('created_at', '<=', $group_user->updated_at)->orderBy('id', 'DESC')->paginate(25, ['*'], 'page', $page);
                }

                $this->WebApiArray['status'] = true;
                if($user_messages->count() > 0){
                    $user_messages->map(function ($user_message) {
                        $user_message->makeHidden(['user_id', 'read_flag','delete_one', 'delete_two', 'delete_everyone','message_created_date','group_delete_message','group_read_message','created_at','updated_at']);
                        $user_message->senders->makeHidden(['role_type', 'remaining_balance','total_balance', 'user_details', 'roles']);
                    });

                    $this->WebApiArray['message'] = 'Record found.';
                    $this->WebApiArray['data'] = $user_messages;
                    $this->WebApiArray['statusCode'] = 0;
                } else {
                    $this->WebApiArray['message'] = 'No record found.';
                    $this->WebApiArray['statusCode'] = 1;
                }
                return response()->json($this->WebApiArray);
            }
            throw new Exception("Group user not exist.", 1);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function makeReadMessage(Request $request)
    {
        DB::beginTransaction();
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }
            $input = $request->all();
            $validator = Validator::make($input,[
                'chatting_id'   =>  'required|exists:chats,chat_uuid',
                'type'      => 'required|in:R,U,A',
                'message_id'   =>  'required_if:type,==,R',
            ],
                [
                    'chatting_id.required' => 'The chat id is required.',
                    'chatting_id.exists' => 'The chat id is invalid.',
                ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            if ($input['type'] == 'R') {
                $unread_message = Message::whereHas('user_conversations',function($query) use($input, $user){
                    $query->whereRaw( "(chat_uuid = '".$input['chatting_id']."' AND (one_user_id = ".$user->id." OR two_user_id = ".$user->id."))");
                })->whereRaw("( user_id != ".$user->id." AND  read_flag != 1 AND message_uuid = '".$input['message_id']."' )")->first();
                if(!empty($unread_message))
                {
                    $unread_message->read_flag = 1;
                    $unread_message->save();
                }
            }

            if($input['type'] == 'A')
            {
                $unread_messages = Message::whereHas('user_conversations',function($query) use($input, $user){
                    $query->whereRaw( "(chat_uuid = '".$input['chatting_id']."' AND (one_user_id = ".$user->id." OR two_user_id = ".$user->id."))");
                })->whereRaw("( user_id != ".$user->id." AND  read_flag != 1 )")->pluck('id')->toArray();
                if(count($unread_messages) > 0)
                {
                    Message::whereIn('id',$unread_messages)->update(['read_flag' => 1]);
                }
            }

            $chatuser = Chat::withCount(['chat_messages' => function($query) use($user){
                $query->whereRaw("( user_id != ".$user->id." AND  read_flag != 1 )");
            }])->whereRaw( "(chat_uuid = '".$input['chatting_id']."' AND (one_user_id = ".$user->id." OR two_user_id = ".$user->id."))")->with(['one_users.user_details','two_users.user_details'])->orderBy('updated_at','DESC')->first();
            if(!empty($chatuser)){
                $unread_message =   Message::whereHas('user_conversations',function($query) use ($user){
                    $query->whereRaw( "((one_user_id = ".$user->id." OR two_user_id = ".$user->id."))");
                })->whereRaw("( user_id != ".$user->id." AND  read_flag != 1 )")
                    ->whereRaw("(group_read_message is null OR NOT FIND_IN_SET(".$user->id.",group_read_message))")->count();

                DB::commit();
                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Record found.';
                return response()->json($this->WebApiArray);
            }
            throw new Exception("No record found.", 1);
        } catch (Exception $e){
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function makeReadGroupMessage(Request $request)
    {
        DB::beginTransaction();
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }
            $input = $request->all();
            $validator = Validator::make($input,[
                'chatting_id'   =>  'required|exists:chats,chat_uuid',
                'type'      => 'required|in:R,U,A',
                'message_id'   =>  'required_if:type,==,R',
            ],
                [
                    'chatting_id.required' => 'The chat id is required.',
                    'chatting_id.exists' => 'The chat id is invalid.',
                ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }
            $user = $this->getAuthenticatedUser();
            if($input['type'] == 'R')
            {
                $unread_message = Message::whereHas('user_conversations',function($query) use($input, $user){
                    $query->where('chat_uuid', $input['chatting_id'])->whereHas('group_users',function ( $subquery ) use($user){
                        $subquery->where('user_id', $user->id);
                    });
                })->whereRaw("( user_id != ".$user->id." AND  read_flag != 1 AND message_uuid = '".$input['message_id']."' ) AND (group_read_message is null OR NOT FIND_IN_SET(".$user->id.",group_read_message))")->first();
                if(!empty($unread_message))
                {
                    $read_array = explode(',',$unread_message->group_read_message);
                    $read_array = array_filter($read_array);
                    array_push($read_array, $user->id);

                    $unread_message->group_read_message = implode(',', array_unique($read_array));

                    $unread_message->save();
                }
            }

            if($input['type'] == 'A')
            {
                $unread_messages = Message::whereHas('user_conversations',function($query) use($input, $user){
                    $query->where('chat_uuid', $input['chatting_id'])->whereHas('group_users',function ( $subquery ) use ($user){
                        $subquery->where('user_id', $user->id);
                    });
                })->whereRaw("( user_id != ".$user->id." AND  read_flag != 1 ) AND (group_read_message is null OR NOT FIND_IN_SET(".$user->id.",group_read_message))")->get();

                if($unread_messages->count() > 0)
                {
                    foreach ($unread_messages as $unmessage) {
                        $read_array = explode(',',$unmessage->group_read_message);
                        $read_array = array_filter($read_array);
                        array_push($read_array, $user->id);
                        $unmessage->group_read_message = implode(',', array_unique($read_array));
                        $unmessage->save();
                    }
                }
            }

            $chatuser = Chat::withCount(['chat_messages' => function($query) use($input, $user) {
                $query->whereRaw("( user_id != ".$user->id." AND  read_flag != 1 )")
                    ->whereRaw("(group_read_message is null OR NOT FIND_IN_SET(".$user->id.",group_read_message))");
            }])->where('chat_uuid', $input['chatting_id'])->whereHas('group_users',function ( $subquery ) use ($user){
                $subquery->where('user_id', $user->id);
            })->with(['one_users.user_details','two_users.user_details'])->orderBy('updated_at','DESC')->first();
            if(!empty($chatuser)){
                $unread_message =   Message::whereHas('user_conversations',function($query) use ($user){
                    $query->whereHas('group_users',function ( $subquery ) use ($user){
                        $subquery->where('user_id', $user->id);
                    });
                })->whereRaw("( user_id != ".$user->id." AND  read_flag != 1 )")->count();
                DB::commit();

                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Record found.';
                return response()->json($this->WebApiArray);
            }
            throw new Exception("No record found.", 1);
        } catch (Exception $e){
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function createGroup(Request $request)
    {
        DB::beginTransaction();
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }

            $user = $this->getAuthenticatedUser();

            $input = $request->all();
            $validator = Validator::make($input, [
                'title' => 'required:max:50',
                'group_type' => 'required'
            ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>', $validator->errors()->all()), 1);
            }

            if ($input['group_type'] == 1) {

                $validatorUser = Validator::make($input, [
                    'member' => 'required|array',
                    'member.*' => 'required|exists:users,id,status,1,confirmed,1,deleted_status,0',
                ]);
                if ($validatorUser->fails()) {
                    throw new Exception(implode('<br/>', $validatorUser->errors()->all()), 1);
                }
            } else {
                $validatorTeam = Validator::make($input, [
                    'team' => 'required|exists:teams,id'
                ]);
                if ($validatorTeam->fails()) {
                    throw new Exception(implode('<br/>', $validatorTeam->errors()->all()), 1);
                }
            }
            if ($input['group_type'] == 1) {
                if (isset($input['member']) && count($input['member']) > 0) {

                    if (in_array($user->id, $input['member'])) {
                        throw new Exception('You can not add yourself as member', 1);
                    }

                    $userChats = Chat::has('chat_messages')
                        ->where(function ($queryone) use($user) {
                            $queryone->where('one_user_id', $user->id)->orWhere('two_user_id', $user->id);
                        })
                        ->whereHas('one_users', function ($querytwo) {
                            $querytwo->where('deleted_status', '!=', 1)->where(['status' => 1, 'confirmed' => 1]);
                        })
                        ->whereHas('two_users', function ($query) {
                            $query->where('deleted_status', '!=', 1)->where(['status' => 1, 'confirmed' => 1]);
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

                if (isset($input['member']) && count($input['member']) > 0) {
                    $chat = new Chat();
                    $chat_data['chat_uuid'] = Str::uuid()->toString();
                    $chat_data['one_user_id'] = $user->id;
                    $chat_data['two_user_id'] = $user->id;
                    $chat_data['chat_type'] = 2;
                    $chat_data['group_name'] = $input['title'];

                    if ($chat->fill($chat_data)->save()) {

                        $group_user_admin = new GroupUsers();
                        $group_user_admin->chat_id = $chat->id;
                        $group_user_admin->user_id = $user->id;
                        $group_user_admin->admin = 1;
                        $group_user_admin->save();

                        foreach ($input['member'] as $team_user) {
                            $group_user = new GroupUsers();
                            $group_user->chat_id = $chat->id;
                            $group_user->user_id = $team_user;
                            $group_user->admin = 0;
                            $group_user->save();
                        }
                    }
                }
            } else {
                $team_id = $input['team'];
                $checkAcceptedTeam = Team::withCount(['team_users' => function ($teamquery) {
                    $teamquery->where('team_user.status', 1);
                }])->where('id', $team_id)->get();

                $filteredAcceptedteam = $checkAcceptedTeam->filter(function ($checkAcceptedTeamItem) {
                    if ($checkAcceptedTeamItem['team_users_count'] > 0) {
                        return $checkAcceptedTeamItem;
                    }
                })->values();

                if ($filteredAcceptedteam->count() != $checkAcceptedTeam->count()) {
                    throw new Exception("None of your team member have accepted your request yet.", 1);
                }

                $chat = Chat::where('team_id', $team_id)->first();
                if (!empty($chat)) {
                    throw new Exception("Team already added as group.", 1);
                } else {

                    $team = Team::with(['team_users' => function ($query) {
                        $query->where('team_user.status', 1);
                    }])->where('id', $team_id)->first();

                    $chat = new Chat();

                    $chat_data['chat_uuid'] = Str::uuid()->toString();
                    $chat_data['one_user_id'] = $user->id;
                    $chat_data['two_user_id'] = $user->id;
                    $chat_data['team_id'] = $team_id;
                    $chat_data['chat_type'] = 2;
                    $chat_data['group_name'] = $team->title;

                    if ($chat->fill($chat_data)->save()) {

                        $group_user_admin = new GroupUsers();
                        $group_user_admin->chat_id = $chat->id;
                        $group_user_admin->user_id = $user->id;
                        $group_user_admin->admin = 1;
                        $group_user_admin->save();

                        foreach ($team->team_users as $team_user) {
                            $group_user = new GroupUsers();
                            $group_user->chat_id = $chat->id;
                            $group_user->user_id = $team_user->id;
                            $group_user->admin = 0;
                            $group_user->save();
                        }
                    }
                }
            }
            if (!empty($chat)) {
                DB::commit();
                $chatuser = Chat::where('chat_uuid', $chat->chat_uuid)->with(['one_users.user_details','two_users.user_details','group_users'])->first();
                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Group Created Successfully.';
                $this->sendFirebaseNotification($chatuser, 'chat_users');
                return response()->json($this->WebApiArray);
            }
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function sendUserMessage(Request $request)
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
                'message_type' => 'required|in:I,V,N,MI,MV',
                'chatting_id'   =>  'required|exists:chats,chat_uuid',
            ],
                [
                    'message_type.required' => 'The messsage type is required.',
                    'message_type.in' => 'The messsage type is invalid.',
                    'chatting_id.required' => 'The chat id is required.',
                    'chatting_id.exists' => 'The chat id is invalid.',
                ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $file_validation = $input['message_type'] == 'N' || $input['message_type'] == 'MI' ||
            $input['message_type'] == 'MV' ? 'required|max:65536' : ($input['message_type'] == 'I' ? 'required|image|mimes:jpeg,jpg,png|max:1024' : 'required');

            $message_validator = Validator::make($input,[
                'message_file' => $file_validation,
            ],
                [
                    'message_file.required' => 'The messsage is required.',
                    'message_file.max' => 'The messsage length is invalid.',
                    'message_file.mimes' => 'The messsage type is invalid.',
                ]);
            if ($message_validator->fails()) {
                throw new Exception(implode('<br/>',$message_validator->errors()->all()), 1);
            }
            $checkchat = Chat::whereRaw("( (one_user_id = ".$user->id." OR  two_user_id = ".$user->id.") AND chat_uuid = '".$input['chatting_id']."'  )")->first();

            if(empty($checkchat)){
                throw new Exception("Chat not exist.", 1);
            }
            if($checkchat->one_users->deleted_status == 1 || $checkchat->two_users->deleted_status == 1)
            {
                throw new Exception("This user account has been deleted.", 1);
            }
            $chat_data = array();
            if($input['message_type'] == 'I' || $input['message_type'] == 'V' )
            {
                if ($input['message_type'] == 'I') {
                    $file_name = AwsBucketMessageVideoImageFileUpload($input['message_file'],'messages',$input['message_type']);
                    if(empty($file_name)){
                        throw new Exception("Error in file uploading.", 1);
                    }
                    $chat_data['message'] = $file_name['file_name'];
                    $chat_data['thumbnail'] = $file_name['file_name'];
                } else {
                    $file_name = AwsBucketMessageVideoImageFileUpload($input['message_file'],'messages',$input['message_type']);
                    if(empty($file_name)){
                        throw new Exception("Error in file uploading.", 1);
                    }
                    $chat_data['message'] = $file_name['file_name'];
                    $image = explode(".",$file_name['file_name']);
                    $imagename = $image[0].'.jpg';
                    $chat_data['thumbnail'] = $imagename;
                }
                $chat_data['message_type'] = $input['message_type'] == 'I' ? 2 : 3 ;
            }elseif ($input['message_type'] == 'MI' || $input['message_type'] == 'MV'){

                $media = Video::where('id', $input['message_file'])->first();

                $oldPath = $media->aws_video_uploaded_path;
                $newPath =  'messages/';

                if ($input['message_type'] == 'MI') {
                    $type = "I";
                } else {
                    $type = "V";
                }

                $copyFile = AwsCopyFileToAnotherFolder($oldPath, $newPath, $type);
                if (!empty($copyFile)) {

                    if ($input['message_type'] == 'MI') {
                        $thumbnail = $copyFile;
                    } else {
                        $image = explode(".",$copyFile);
                        $thumbnail = $image[0].'.jpg';
                    }
                    $chat_data['thumbnail'] = $thumbnail;

                    $chat_data['message'] = $copyFile;
                    $chat_data['message_type'] = $input['message_type'] == 'MI' ? 2 : 3 ;
                }
            }else{
                $chat_data['message'] = $input['message_file'];
                $chat_data['message_type'] = 1;
                $chat_data['thumbnail'] = null;
            }

            if($checkchat->fill($chat_data)->save()){
                $chat_message_data = array();
                $chat_message_data['user_id'] =  $user->id;
                $chat_message_data['message'] =  $chat_data['message'];
                $chat_message_data['thumbnail'] =  $chat_data['thumbnail'];
                $chat_message_data['message_type'] =  $chat_data['message_type'];
                $chat_message_data['message_uuid'] =  Str::uuid()->toString();
                if($createdmessage = $checkchat->chat_messages()->create($chat_message_data)){
                    DB::commit();
                    $reciever_id = $checkchat->one_user_id == $user->id ? $checkchat->two_user_id : $checkchat->one_user_id;

                    sendFcmNotification($reciever_id,'Asportcoach', 'You receive a new message', "messenger", $user->id);
                    $sentmessage  = Message::whereHas('user_conversations',function($query) use($input){
                        $query->where('chat_uuid',$input['chatting_id']);
                    })->with(['user_conversations.one_users.user_details','user_conversations.two_users.user_details','senders','user_conversations.group_users'])->where('id',$createdmessage->id)->first();
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Message Sent Successfully.';
                    $this->WebApiArray['data'] = $sentmessage;
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("Error Processing Request", 1);
            }
            throw new Exception("Error Processing Request", 1);
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function sendGroupMessage(Request $request)
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
                'message_type' => 'required|in:I,V,N,MI,MV',
                'chatting_id'   =>  'required|exists:chats,chat_uuid',
            ],
                [
                    'message_type.required' => 'The messsage type is required.',
                    'message_type.in' => 'The messsage type is invalid.',
                    'chatting_id.required' => 'The chat id is required.',
                    'chatting_id.exists' => 'The chat id is invalid.',
                ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }
            $file_validation = $input['message_type'] == 'N' || $input['message_type'] == 'MI' ||
            $input['message_type'] == 'MV' ? 'required|max:65536' : ($input['message_type'] == 'I' ? 'required|image|mimes:jpeg,jpg,png|max:1024' : 'required');

            $message_validator = Validator::make($input,[
                'message_file' => $file_validation,
            ],
                [
                    'message_file.required' => 'The messsage is required.',
                    'message_file.max' => 'The messsage length is invalid.',
                    'message_file.mimes' => 'The messsage type is invalid.',
                ]);
            if ($message_validator->fails()) {
                throw new Exception(implode('<br/>',$message_validator->errors()->all()), 1);
            }
            $checkchat = Chat::where('chat_uuid', $input['chatting_id'])->whereHas('group_users',function ( $subquery ) use($user){
                $subquery->where('user_id', $user->id);
            })->first();

            if(empty($checkchat)){
                throw new Exception("Chat not exist.", 1);
            }
            if($checkchat->one_users->deleted_status == 1 || $checkchat->two_users->deleted_status == 1)
            {
                throw new Exception("This user account has been deleted.", 1);
            }
            $chat_data = array();
            if($input['message_type'] == 'I' || $input['message_type'] == 'V' )
            {
                if ($input['message_type'] == 'I') {
                    $file_name = AwsBucketMessageVideoImageFileUpload($input['message_file'],'messages',$input['message_type']);
                    if(empty($file_name)){
                        throw new Exception("Error in file uploading.", 1);
                    }
                    $chat_data['message'] = $file_name['file_name'];
                    $chat_data['thumbnail'] = $file_name['file_name'];
                } else {
                    $file_name = AwsBucketMessageVideoImageFileUpload($input['message_file'],'messages',$input['message_type']);
                    if(empty($file_name)){
                        throw new Exception("Error in file uploading.", 1);
                    }
                    $chat_data['message'] = $file_name['file_name'];
                    $image = explode(".",$file_name['file_name']);
                    $imagename = $image[0].'.jpg';
                    $chat_data['thumbnail'] = $imagename;
                }

                $chat_data['message_type'] = $input['message_type'] == 'I' ? 2 : 3 ;
            }elseif ($input['message_type'] == 'MI' || $input['message_type'] == 'MV'){

                $media = Video::where('id', $input['message_file'])->first();

                $oldPath = $media->aws_video_uploaded_path;
                $newPath =  'messages/';

                if ($input['message_type'] == 'MI') {
                    $type = 'I';
                } else {
                    $type = 'V';
                }

                $copyFile = AwsCopyFileToAnotherFolder($oldPath, $newPath, $type);
                if (!empty($copyFile)) {

                    if ($input['message_type'] == 'MI') {
                        $thumbnail = $copyFile;
                    } else {
                        $image = explode(".",$copyFile);
                        $thumbnail = $image[0].'.jpg';
                    }
                    $chat_data['thumbnail'] = $thumbnail;

                    $chat_data['message'] = $copyFile;
                    $chat_data['message_type'] = $input['message_type'] == 'MI' ? 2 : 3 ;
                }
            }else{
                $chat_data['message'] = $input['message_file'];
                $chat_data['message_type'] = 1;
                $chat_data['thumbnail'] = null;
            }

            if($checkchat->fill($chat_data)->save()){
                $chat_message_data = array();
                $chat_message_data['user_id'] =  $user->id;
                $chat_message_data['message'] =  $chat_data['message'];
                $chat_message_data['thumbnail'] =  $chat_data['thumbnail'];
                $chat_message_data['message_type'] =  $chat_data['message_type'];
                $chat_message_data['message_uuid'] =  Str::uuid()->toString();
                if($createdmessage = $checkchat->chat_messages()->create($chat_message_data)){
                    DB::commit();
                    $sentmessage  = Message::whereHas('user_conversations',function($query) use($input){
                        $query->where('chat_uuid',$input['chatting_id']);
                    })->with(['user_conversations.one_users.user_details','user_conversations.two_users.user_details','senders','user_conversations.group_users'])->where('id',$createdmessage->id)->first();
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Message Sent Successfully.';
                    $this->WebApiArray['data'] = $sentmessage;
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("Error Processing Request", 1);
            }
            throw new Exception("Error Processing Request", 1);
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function addParticipant(Request $request) {
        DB::beginTransaction();
        try {
            if (!$request->isMethod('post')) {
                throw new Exception("Request not allowed.", 1);
            }

            $user = $this->getAuthenticatedUser();

            $input = $request->all();

            $validator = Validator::make($input, [
                'chatting_id'   =>  'required|exists:chats,chat_uuid',
                'member' => 'required|array',
                'member.*' => 'required|exists:users,id,status,1,confirmed,1,deleted_status,0',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>', $validator->errors()->all()), 1);
            }

            $chat = Chat::where(['chat_uuid' => $input['chatting_id'], 'chat_type' => 2])->WhereHas('group_users',function ( $subquery ) use ($user){
                $subquery->where(['user_id' => $user->id, 'admin' => 1]);
            })->first();

            if (empty($chat)) {
                throw new Exception('Chat not exist', 1);
            }

            if (isset($input['member']) && count($input['member']) > 0) {

                if (in_array($user->id, $input['member'])) {
                    throw new Exception('You can not add yourself as member', 1);
                }

                $userChats = Chat::has('chat_messages')
                    ->where(function ($queryone) use ($user) {
                        $queryone->where('one_user_id', $user->id)->orWhere('two_user_id', $user->id);
                    })
                    ->whereHas('one_users', function ($querytwo) {
                        $querytwo->where('deleted_status', '!=', 1)->where(['status' => 1, 'confirmed' => 1]);
                    })
                    ->whereHas('two_users', function ($query) {
                        $query->where('deleted_status', '!=', 1)->where(['status' => 1, 'confirmed' => 1]);
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

            foreach ($input['member'] as $team_user) {
                $group_user_check = GroupUsers::where(['chat_id' => $chat->id, 'user_id' => $team_user])->first();

                if (empty($group_user_check)) {
                    $group_user = new GroupUsers();
                    $group_user->chat_id = $chat->id;
                    $group_user->user_id = $team_user;
                    $group_user->admin = 0;
                    $group_user->save();
                    $chatuser = Chat::where('chat_uuid', $chat->chat_uuid)->with(['one_users.user_details','two_users.user_details','group_users'])->first();
                    $this->sendFirebaseNotification($chatuser, 'chat_users');
                } else {
                    $group_user_check->status = 1;
                    $group_user_check->save();
                    $this->sendFirebaseNotification($group_user_check, 'group_chat');
                }
            }
            DB::commit();
            $this->WebApiArray['status'] = true;
            $this->WebApiArray['message'] = 'Participant Added Successfully.';
            $this->WebApiArray['data'] = $chat;
            return response()->json($this->WebApiArray);
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function removeFromGroup(Request $request) {
        DB::beginTransaction();
        try {
            if (!$request->isMethod('post')) {
                throw new Exception("Request not allowed.", 1);
            }
            $input = $request->all();
            $validator = Validator::make($input, [
                'user_id' => 'required|exists:users,id',
                'chatting_id'   =>  'required|exists:chats,chat_uuid',
            ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>', $validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();


            $chat = Chat::where(['chat_uuid' => $input['chatting_id'], 'chat_type' => 2])->WhereHas('group_users',function ( $subquery ) use($user){
                $subquery->where(['user_id' => $user->id, 'admin' => 1]);
            })->first();

            if (empty($chat)) {
                throw new Exception('Chat not exist', 1);
            }

            if ($input['user_id'] == $user->id) {
                throw new Exception("You cant't remove yourself from group", 1);
            }

            $group_user = GroupUsers::where(['chat_id' => $chat->id, 'user_id' => $input['user_id'], 'status' => 1])->first();

            if (!empty($group_user)) {
                $data['status'] = 2;
                if($group_user->fill($data)->save()){
                    DB::commit();
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Participant Removed Successfully.';
                    $this->WebApiArray['data'] = $group_user;
                    $this->sendFirebaseNotification($group_user, 'group_chat');
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("Error Processing Request", 1);
            }
            throw new Exception("User not exist in group", 1);
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function exitGroup(Request $request) {
        DB::beginTransaction();
        try {
            if (!$request->isMethod('post')) {
                throw new Exception("Request not allowed.", 1);
            }
            $input = $request->all();
            $validator = Validator::make($input, [
                'chatting_id'   =>  'required|exists:chats,chat_uuid',
            ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>', $validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            $chat = Chat::where(['chat_uuid' => $input['chatting_id'], 'chat_type' => 2])->WhereHas('group_users',function ( $subquery ) use($user){
                $subquery->where(['user_id' => $user->id, 'admin' => 0]);
            })->first();

            if (empty($chat)) {
                throw new Exception('Chat not exist', 1);
            }

            $group_user = GroupUsers::where(['chat_id' => $chat->id, 'user_id' => $user->id, 'status' => 1])->first();

            if (!empty($group_user)) {
                $data['status'] = 3;
                if($group_user->fill($data)->save()){
                    DB::commit();
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Exit Successfully.';
                    $this->WebApiArray['data'] = $group_user;
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("Error Processing Request", 1);
            }
            throw new Exception("User Not exist in this group", 1);
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function changeGroupName(Request $request) {
        DB::beginTransaction();
        try {
            if (!$request->isMethod('post')) {
                throw new Exception("Request not allowed.", 1);
            }
            $input = $request->all();
            $validator = Validator::make($input, [
                'chatting_id'   =>  'required|exists:chats,chat_uuid',
                'name'   =>  'required|max:50',
            ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>', $validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            $chat = Chat::where(['chat_uuid' => $input['chatting_id'], 'chat_type' => 2])->WhereHas('group_users',function ( $subquery ) use ($user){
                $subquery->where(['user_id' => $user->id, 'admin' => 1]);
            })->with('group_users')->first();
            if (empty($chat)) {
                throw new Exception('Chat not exist', 1);
            }

            $chat->group_name = $input['name'];
            $chat->save();

            DB::commit();
            $this->WebApiArray['status'] = true;
            $this->WebApiArray['message'] = 'Group Name Changed Successfully.';
            self::sendFirebaseNotification($chat, 'group_name');
            return response()->json($this->WebApiArray);
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function storeSessionRequest(Request $request) {
        DB::beginTransaction();
        try {
            if (!$request->isMethod('post')) {
                throw new Exception("Request not allowed.", 1);
            }
            $input = $request->all();
            $validator = Validator::make($input, [
                'chatting_id'   =>  'required|exists:chats,chat_uuid',
                'receiver_id'     => 'required|exists:users,id',
                'sender_id'     => 'required|exists:users,id',
            ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>', $validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            $sender = User::whereHas('roles', function ($query) {
               $query->whereIn('role_id', [3,4]);
            })->where('id', $input['sender_id'])->first();

            $receiver = User::whereHas('roles', function ($query){
               $query->where('role_id', 3);
            })->where('id', $input['receiver_id'])->first();

            if(!empty($sender) && !empty($receiver) && $user->id == $input['sender_id'] &&  $user->id != $input['receiver_id'] )
            {
                if($sender->deleted_status == 1 || $receiver->deleted_status == 1  )
                {
                    throw new Exception("This user account has been deleted.", 1);
                }
                $checkchat = Chat::whereRaw("( (one_user_id = ".$user->id." OR  two_user_id = ".$user->id.") AND (one_user_id = ".$input['receiver_id']." OR  two_user_id = ".$input['receiver_id'].") AND chat_uuid = '".$input['chatting_id']."'  )")->first();
                if(empty($checkchat)){
                    throw new Exception("Chat not exist.", 1);
                }
                $checkActiveSession = SessionRequest::where(['coach_id' => $receiver->id, 'athelete_id' => $sender->id, 'chat_id' => $checkchat->id ])->whereRaw("(status = 1 OR status = 2 || status = 4 || status = 6 )")->first();

                if(!empty($checkActiveSession)){
                    throw new Exception("You have pending session with this coach.", 1);
                }

                if($checkchat->fill(['message_type' => 4])->save()){
                    if($createdmessage = $checkchat->chat_messages()->create(['user_id' => $user->id, 'message_type' => 4, 'message_uuid' => Str::uuid()->toString() ])){
                        if($athelete_session_request = SessionRequest::create(['coach_id' => $receiver->id, 'athelete_id' => $sender->id, 'chat_session_uuid' => Str::uuid()->toString(), 'chat_id' => $checkchat->id ])){
                            if($notification = $athelete_session_request->notifications()->create(['type' => 1, 'from_user_id' => $input['sender_id'], 'to_user_id' => $input['receiver_id'], 'data' => 'You received new session request.', 'notification_uuid' => Str::uuid()->toString()])){

                                DB::commit();
                                sendFcmNotification($input['receiver_id'], 'Asportcoach', 'You received new session request', "messenger", $input['sender_id']);
                                $sentmessage  = Message::whereHas('user_conversations',function($query) use($input){
                                    $query->where('chat_uuid',$input['chatting_id']);
                                })->with(['user_conversations.one_users.user_details','user_conversations.two_users.user_details','senders','user_conversations.group_users'])->where('id',$createdmessage->id)->first();
                                $sentrequest  = SessionRequest::where(['id' => $athelete_session_request->id, 'athelete_id' => $user->id])->first();
                                $notification_data  = Notification::where('id',$notification->id)->first();
                                $this->sendFirebaseNotification($notification_data,'user_notifications');
                                $this->WebApiArray['status'] = true;
                                $this->WebApiArray['message'] = 'Session request sent successfully.';
                                $this->WebApiArray['data']['message'] = $sentmessage;
                                $this->WebApiArray['data']['session_request'] = $sentrequest;
                                return response()->json($this->WebApiArray);
                            }
                            throw new Exception("Error Processing Request", 1);
                        }
                        throw new Exception("Error Processing Request", 1);
                    }
                    throw new Exception("Error Processing Request", 1);
                }
                throw new Exception("Error Processing Request", 1);
            }
            throw new Exception("Invalid user.", 1);

        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function fetchSessionRequest(Request $request) {
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }
            $input = $request->all();

            $validator = Validator::make($input,[
                'chatting_id'   =>  'required|exists:chats,chat_uuid',
            ],
                [
                    'chatting_id.required' => 'The chat id is required.',
                    'chatting_id.exists' => 'The chat id is invalid.',
                ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            $user_request = SessionRequest::whereHas('session_chats', function ($query) use($input, $user) {
                $query->whereRaw("( (one_user_id = ".$user->id." OR  two_user_id = ".$user->id.") AND chat_uuid = '".$input['chatting_id']."'  )");
            })->whereRaw("(coach_id = ".$user->id." OR athelete_id = ".$user->id." )")->whereIn('status', [1,2,4,6])->orderBy('created_at', 'DESC')->first();

            $this->WebApiArray['status'] = true;
            if(!empty($user_request)){
                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data'] = $user_request;
                $this->WebApiArray['statusCode'] = 0;
            } else {
                $this->WebApiArray['statusCode'] = 1;
                $this->WebApiArray['message'] = 'Record not found.';
            }
            return response()->json($this->WebApiArray);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
        }
    }

    public function updateSessionRequest(Request $request) {
        DB::beginTransaction();
        try {

            if (!$request->isMethod('post')) {
                throw new Exception("Request not allowed.", 1);
            }
            $input = $request->all();
            if (isset($input['request_by']) && $input['request_by'] == 2) {
                $validator = Validator::make($input,[
                    'chatting_id'   =>  'required|exists:chats,chat_uuid',
                    'request_status'  => 'required|in:2,3',
                    'session_price'  => 'required_if:request_status,==,2|regex:/^\d+(\.\d{1,2})?$/'
                ],
                    [
                        'chatting_id.required' => 'The chat id is required.',
                        'chatting_id.exists' => 'The chat id is invalid.',
                        'request_status.required' => 'The status is required.',
                        'request_status.in' => 'The status is invalid.',
                        'session_price.required_if' => 'The session_price is required.',
                        'session_price.regex' => 'The session price Should be float type',
                    ]);
            } else {
                $validator = Validator::make($input,[
                    'chatting_id'   =>  'required|exists:chats,chat_uuid',
                    'session_request_id'   =>  'required|exists:session_requests,chat_session_uuid',
                    'request_status'  => 'required|in:2,3',
                    'session_price'  => 'required_if:request_status,==,2|regex:/^\d+(\.\d{1,2})?$/'
                ],
                    [
                        'chatting_id.required' => 'The chat id is required.',
                        'chatting_id.exists' => 'The chat id is invalid.',
                        'session_request_id.required' => 'The session request id is required.',
                        'session_request_id.exists' => 'The session request id is invalid.',
                        'request_status.required' => 'The status is required.',
                        'request_status.in' => 'The status is invalid.',
                        'session_price.required_if' => 'The session_price is required.',
                        'session_price.regex' => 'The session price Should be float type',
                    ]);
            }

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            $checkdeletedChatUser = Chat::whereRaw("( (one_user_id = ".$user->id." OR  two_user_id = ".$user->id.") AND chat_uuid = '".$input['chatting_id']."'  )")->first();
            if(empty($checkdeletedChatUser))
            {
                throw new Exception("Invalid chat.", 1);
            }
            if($checkdeletedChatUser->one_users->deleted_status == 1 || $checkdeletedChatUser->two_users->deleted_status == 1)
            {
                throw new Exception("This user account has been deleted.", 1);
            }

            if (isset($input['request_by']) && $input['request_by'] == 2) {
                $chat_request = Chat::whereRaw("( (one_user_id = ".$user->id." OR  two_user_id = ".$user->id.") AND chat_uuid = '".$input['chatting_id']."'  )")->first();

                $user_requests = new SessionRequest();

                if ($chat_request->one_user_id == $user->id) {
                    $user_requests->athelete_id = $chat_request->two_user_id;
                } else {
                    $user_requests->athelete_id = $chat_request->one_user_id;
                }

                $checkActiveSession = SessionRequest::where(['chat_id' => $chat_request->id ])
                    ->whereRaw("(status = 1 OR status = 2 || status = 4 || status = 6 )")->first();

                if(!empty($checkActiveSession)){
                    throw new Exception("You have pending session with this user.", 1);
                }

                $user_requests->coach_id = $user->id;
                $user_requests->chat_session_uuid = Str::uuid()->toString();
                $user_requests->chat_id = $chat_request->id;
                $user_requests->request_by = 2;
            } else {
                $user_requests  = SessionRequest::whereHas('session_chats',function($query) use($input, $user){
                    $query->whereRaw("( (one_user_id = ".$user->id." OR  two_user_id = ".$user->id.") AND chat_uuid = '".$input['chatting_id']."'  )");
                })->where(['coach_id' => $user->id, 'chat_session_uuid' =>  $input['session_request_id'] ])->first();
            }

            if (!empty($user_requests)) {
                    if($input['request_status'] == 2){
                    $user_requests->session_price = $input['session_price'];
                }
                $user_requests->status = $input['request_status'];

                if($user_requests->save()) {
                    $message_type = $input['request_status'] == 2 ? 5 : 6;
                    $userchat = Chat::whereRaw("( (one_user_id = ".$user->id." OR  two_user_id = ".$user->id.") AND chat_uuid = '".$input['chatting_id']."'  )")->first();
                    if($userchat->fill(['message_type' => $message_type ])->save()){
                        $chat_message_data = array();
                        $chat_message_data['user_id'] =  $user->id;
                        $chat_message_data['message_type'] =  $message_type;
                        $chat_message_data['message_uuid'] =  Str::uuid()->toString();
                        if($createdmessage = $userchat->chat_messages()->create($chat_message_data)){
                            $notification_message =  $input['request_status'] == 2 ? 'Your session request has been accepted.' : 'Your session request has been declined.';
                            if ($user_requests->request_by == 2) {
                                $notification_message = 'Coach requested for session';
                            }
                            if($notification = $user_requests->notifications()->create(['type' => 1, 'from_user_id' =>$user->id, 'to_user_id' => $user_requests->athelete_id, 'data' => $notification_message, 'notification_uuid' => Str::uuid()->toString()])){
                                DB::commit();

                                sendFcmNotification($user_requests->athelete_id, 'Asportcoach', $notification_message, "messenger", $user->id);

                                $sentmessage  = Message::whereHas('user_conversations',function($query) use($input){
                                    $query->where('chat_uuid',$input['chatting_id']);
                                })->with(['user_conversations.one_users.user_details','user_conversations.two_users.user_details','senders','user_conversations.group_users'])->where('id',$createdmessage->id)->first();
                                $notification_data  = Notification::where('id',$notification->id)->first();
                                $sentrequest  = SessionRequest::where('id',$user_requests->id)->first();
                                $this->sendFirebaseNotification($notification_data, 'user_notifications');
                                $this->WebApiArray['status'] = true;
                                $this->WebApiArray['message'] =  $input['request_status'] == 2 ? 'Session request accepted successfully.' : 'Session request declined successfully.';
                                $this->WebApiArray['data']['message'] = $sentmessage;
                                $this->WebApiArray['data']['session_request'] = $sentrequest;
                                return response()->json($this->WebApiArray);
                            }
                            throw new Exception("Error Processing Request", 1);
                        }
                        throw new Exception("Error Processing Request", 1);
                    }
                    throw new Exception("Error Processing Request", 1);
                }
                throw new Exception("Error Processing Request", 1);
            }
            throw new Exception("Invalid session request.", 1);
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function startSession(Request $request) {
        DB::beginTransaction();
        try {

            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }
            $input = $request->all();
            $validator = Validator::make($input,[
                'chatting_id'   =>  'required|exists:chats,chat_uuid',
                'session_request_id'   =>  'required|exists:session_requests,chat_session_uuid',
                'request_status'  => 'required|in:6',
            ],
                [
                    'chatting_id.required' => 'The chat id is required.',
                    'chatting_id.exists' => 'The chat id is invalid.',
                    'session_request_id.required' => 'The session request id is required.',
                    'session_request_id.exists' => 'The session request id is invalid.',
                    'request_status.required' => 'The status is required.',
                    'request_status.in' => 'The status is invalid.',
                ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            $user_requests  = SessionRequest::whereHas('session_chats',function($query) use($input, $user){
                $query->whereRaw("( (one_user_id = ".$user->id." OR  two_user_id = ".$user->id.") AND chat_uuid = '".$input['chatting_id']."'  )");
            })->where(['coach_id' => $user->id, 'chat_session_uuid' =>  $input['session_request_id'] ])->first();

            if(!empty($user_requests)){

                if($user_requests->athelete_user->deleted_status == 1 || $user_requests->coach_user->deleted_status == 1)
                {
                    throw new Exception("This user account has been deleted.", 1);
                }

                if($user_requests->status == 4){
                    if($input['request_status'] == 6){
                        $user_requests->start_session_time = Carbon::now();
                    }

                    $user_requests->status = $input['request_status'];
                    if($user_requests->save())
                    {
                        $message_type = 9;
                        $userchat = Chat::whereRaw("( (one_user_id = ".$user->id." OR  two_user_id = ".$user->id.") AND chat_uuid = '".$input['chatting_id']."'  )")->first();
                        if($userchat->fill(['message_type' => $message_type ])->save()){
                            $chat_message_data = array();
                            $chat_message_data['user_id'] =  $user->id;
                            $chat_message_data['message_type'] =  $message_type;
                            $chat_message_data['message_uuid'] =  Str::uuid()->toString();
                            if($createdmessage = $userchat->chat_messages()->create($chat_message_data)){
                                if($notification = $user_requests->notifications()->create(['type' => 1, 'from_user_id' =>$user->id, 'to_user_id' => $user_requests->athelete_id, 'data' => 'Your session has been started', 'notification_uuid' => Str::uuid()->toString()])){
                                    DB::commit();

                                    sendFcmNotification($user_requests->athelete_id,'Asportcoach', 'Your session has been started', "messenger", $user->id);

                                    $sentmessage  = Message::whereHas('user_conversations',function($query) use($input){
                                        $query->where('chat_uuid',$input['chatting_id']);
                                    })->with(['user_conversations.one_users.user_details','user_conversations.two_users.user_details','senders','user_conversations.group_users'])->where('id',$createdmessage->id)->first();
                                    $notification_data  = Notification::where('id',$notification->id)->first();
                                    $sentrequest  = SessionRequest::where('id',$user_requests->id)->first();
                                    $this->sendFirebaseNotification($notification_data, 'user_notifications');
                                    $this->WebApiArray['status'] = true;
                                    $this->WebApiArray['message'] =  'Session started successfully.';
                                    $this->WebApiArray['data']['message'] = $sentmessage;
                                    $this->WebApiArray['data']['session_request'] = $sentrequest;
                                    return response()->json($this->WebApiArray);
                                }
                                throw new Exception("Error Processing Request", 1);
                            }
                            throw new Exception("Error Processing Request", 1);
                        }
                        throw new Exception("Error Processing Request", 1);
                    }
                    throw new Exception("Error Processing Request", 1);
                }
                throw new Exception("Invalid session request.", 1);
            }
            throw new Exception("Invalid session request.", 1);
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function completeSession(Request $request) {
        DB::beginTransaction();
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }
            $input = $request->all();
            $validator = Validator::make($input,[
                'chatting_id'   =>  'required|exists:chats,chat_uuid',
                'session_request_id'   =>  'required|exists:session_requests,chat_session_uuid',
                'request_status'  => 'required|in:7',
            ],
                [
                    'chatting_id.required' => 'The chat id is required.',
                    'chatting_id.exists' => 'The chat id is invalid.',
                    'session_request_id.required' => 'The session request id is required.',
                    'session_request_id.exists' => 'The session request id is invalid.',
                    'request_status.required' => 'The status is required.',
                    'request_status.in' => 'The status is invalid.',
                ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            $user_requests  = SessionRequest::whereHas('session_chats',function($query) use($input, $user){
                $query->whereRaw("( (one_user_id = ".$user->id." OR  two_user_id = ".$user->id.") AND chat_uuid = '".$input['chatting_id']."'  )");
            })->where(['chat_session_uuid' =>  $input['session_request_id'] ])->whereRaw("( coach_id = ".$user->id." OR  athelete_id = ".$user->id." )")->first();

            if(!empty($user_requests)){

                if($user_requests->athelete_user->deleted_status == 1 || $user_requests->coach_user->deleted_status == 1)
                {
                    throw new Exception("This user account has been deleted.", 1);
                }

                if($user_requests->status == 6){
                    if($input['request_status'] == 7){
                        $user_requests->end_session_time = Carbon::now();
                    }
                    $user_requests->status = $input['request_status'];
                    if($user_requests->save())
                    {
                        $message_type = 10;
                        $userchat = Chat::whereRaw("( (one_user_id = ".$user->id." OR  two_user_id = ".$user->id.") AND chat_uuid = '".$input['chatting_id']."'  )")->first();
                        if($userchat->fill(['message_type' => $message_type ])->save()){
                            $chat_message_data = array();
                            $chat_message_data['user_id'] =  $user->id;
                            $chat_message_data['message_type'] =  $message_type;
                            $chat_message_data['message_uuid'] =  Str::uuid()->toString();
                            if($createdmessage = $userchat->chat_messages()->create($chat_message_data)){
                                $to_user_id = $user_requests->athelete_id == $user->id ? $user_requests->coach_id : $user_requests->athelete_id;
                                if($notification = $user_requests->notifications()->create(['type' => 1, 'from_user_id' =>$user->id, 'to_user_id' => $to_user_id, 'data' => 'Your session has been completed', 'notification_uuid' => Str::uuid()->toString()])){
                                    DB::commit();
                                    sendFcmNotification($to_user_id, 'Asportcoach','Your session has been completed', "messenger", $user->id);
                                    $sentmessage  = Message::whereHas('user_conversations',function($query) use($input){
                                        $query->where('chat_uuid',$input['chatting_id']);
                                    })->with(['user_conversations.one_users.user_details','user_conversations.two_users.user_details','senders','user_conversations.group_users'])->where('id',$createdmessage->id)->first();
                                    $notification_data  = Notification::where('id',$notification->id)->first();
                                    $sentrequest  = SessionRequest::where('id',$user_requests->id)->first();
                                    $this->WebApiArray['error'] = false;
                                    $this->WebApiArray['status'] = true;
                                    $this->WebApiArray['message'] = 'Session completed successfully.';
                                    $this->WebApiArray['data']['message'] = $sentmessage;
                                    $this->WebApiArray['data']['session_request'] = $sentrequest;
                                    $this->WebApiArray['notification'] = $notification_data;
                                    return response()->json($this->WebApiArray);
                                }
                                throw new Exception("Error Processing Request", 1);
                            }
                            throw new Exception("Error Processing Request", 1);
                        }
                        throw new Exception("Error Processing Request", 1);
                    }
                    throw new Exception("Error Processing Request", 1);
                }
                throw new Exception("Invalid session request.", 1);
            }
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function fetchGroupInfo(Request $request) {
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }
            $input = $request->all();
            $validator = Validator::make($input,[
                'chatting_id'   =>  'required|exists:chats,chat_uuid',
            ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $loginUser = $this->getAuthenticatedUser();

            $users = Chat::with('active_group_users.users:id,name,username')->selectRaw('id,group_name')->where(['chat_uuid'=> $input['chatting_id'],'chat_type'=>'2'])->first();

            if (empty($users)) {
                throw new Exception('Group not exist',1);
            }

            $check_admin = Chat::where('chat_uuid', $input['chatting_id'])->WhereHas('group_users',function ( $adminqry ) use($loginUser){
                $adminqry->where('user_id', $loginUser->id)->where('admin', 1);
            })->first();
            $admin = 0;
            if(!empty($check_admin)) {
                $admin = 1;
            }

            $users->active_group_users->map(function ($user) {
                $user->makeHidden(['id','user_id','chat_id','admin','created_at','updated_at']);
                $user->users->makeHidden(['role_type','total_balance','remaining_balance','roles']);
                $user->users->user_details->makeHidden(['country_id','state_id','city_id','zipcode_id','about','created_at','updated_at','user_profile_image','id','user_id','image','mobile_code_id','mobile','gender','dob','address_line_1','address_line_2']);
            });

            if(!empty($users)){
                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data']['group_user'] = $users;
                $this->WebApiArray['data']['admin'] = $admin;
                return response()->json($this->WebApiArray);
            }
            throw new Exception("No record found.", 1);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function mediaArchive(Request $request) {
        try {

            $user = $this->getAuthenticatedUser();
            $videos = Video::selectRaw('id, user_id, file_name,thumbnail,file_type')->where('user_id', $user->id)->where('file_type', 1)->whereNull('user_folder_id')->orderBy('updated_at','DESC')->get();
            $photos = Video::selectRaw('id, user_id, file_name,thumbnail,file_type')->where('user_id', $user->id)->where('file_type', 2)->whereNull('user_folder_id')->orderBy('updated_at','DESC')->get();

            $videoFolder = UserFolder::selectRaw('id, title, slug')->whereNotNull('user_folder_id')->where('user_id', $user->id)->where('folder_type', 1)->orderBy('updated_at','DESC')->get();
            $photoFolder = UserFolder::selectRaw('id, title, slug')->whereNotNull('user_folder_id')->where('user_id', $user->id)->where('folder_type', 2)->orderBy('updated_at','DESC')->get();

            $videos->map(function ($video) {
               $video->makeHidden(['user_id','thumbnail','file_type','video_parent_folder','video_folder_path','video_uploaded_path','aws_video_uploaded_path','user_folders']);
            });

            $photos->map(function ($photo) {
                $photo->makeHidden(['user_id','thumbnail','file_type','video_parent_folder','video_folder_path','video_uploaded_path','aws_video_uploaded_path','user_folders']);
            });

            $this->WebApiArray['status'] = true;
            if ($videos->count() > 0 || $photos->count() > 0 || $videoFolder->count() > 0 || $photoFolder->count() > 0) {
                $this->WebApiArray['message'] = 'Record found';
                $this->WebApiArray['data']['video'] = $videos;
                $this->WebApiArray['data']['videoFolder'] = $videoFolder;
                $this->WebApiArray['data']['photo'] = $photos;
                $this->WebApiArray['data']['photoFolder'] = $photoFolder;
                $this->WebApiArray['statusCode'] = 0;
            } else {
                $this->WebApiArray['message'] = 'Record not found';
                $this->WebApiArray['statusCode'] = 1;
            }
            return response()->json($this->WebApiArray);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function fetchFolderMediaArchive(Request $request) {
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }
            $input = $request->all();
            $validator = Validator::make($input,[
                'folder_id'   =>  'required|exists:user_folders,id',
            ],
                [
                    'folder_id.required' => 'The Folder id is required.',
                    'folder_id.exists' => 'The Folder id is invalid.',
                ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            $media = Video::selectRaw('id, user_id, file_name,thumbnail,file_type,user_folder_id')->where('user_id', $user->id)->where(['user_folder_id' => $input['folder_id']])->orderBy('updated_at','DESC')->get();

            $media->map(function ($video) {
                $video->makeHidden(['user_id','thumbnail','file_type','video_parent_folder','video_folder_path','user_folder_id','video_uploaded_path','aws_video_uploaded_path','user_folders']);
            });

            $this->WebApiArray['status'] = true;
            if($media->count() > 0 || $media->count() > 0){
                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['statusCode'] = 1;
                $this->WebApiArray['data'] = $media;
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

    public function chatMediaToArchive(Request $request) {
        DB::beginTransaction();
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }
            $input = $request->all();

            $validator = Validator::make($input,[
                'title'       => 'required|max:50',
                'description' => 'nullable|max:1000',
                'user_folder_id'   => 'nullable|exists:user_folders,id,user_id,'.auth()->id(),
                'video_tag'   => 'nullable|array|min:1',
                'video_tag.*'   => 'nullable|exists:tags,id',
                'file_id'   => 'required|exists:messages,id',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            $child_folder = "";
            if(!empty($input['user_folder_id']))
            {
                $check_folder = UserFolder::where('id',$input['user_folder_id'])->first();
                if(empty($check_folder->user_folder_id))
                {
                    throw new Exception("You can't access parent Directory.", 1);
                }
                $child_folder = $check_folder->slug;
            }

            $parent_directories = self::createParentFolder($user);

            if(empty($parent_directories['status'])){
                throw new Exception("Error occured! Directories could not be created.", 1);
            }

            $parent_folder = $parent_directories['folder_name'];

            $media = Message::where('id', $input['file_id'])->whereIn('message_type', [2,3])->first();

            if (empty($media)) {
                throw new Exception("Message id is wrong", 1);
            }

            if ($media->message_type == 2) {
                $type = 2;
                $folder = 'images';
                $file_type = 'I';
            } else {
                $type = 1;
                $folder = 'videos';
                $file_type = 'V';
            }

            $newPath = $parent_folder.'/'.$folder.'/';
            if(!empty($child_folder))
            {
                $newPath = $parent_folder.'/'.$folder.'/'.$child_folder.'/';
            }

            $oldPath = 'messages/'.$media->message;

            $copyFile = AwsCopyFileToAnotherFolder($oldPath, $newPath, $file_type);

            if ($media->message_type == 2) {
                $imagename = $copyFile;
            } else {
                $image = explode(".",$copyFile);
                $imagename = $image[0].'.jpg';
            }

            if (!empty($copyFile)) {
                $video = new Video;
                $input['status'] = 1;
                $input['file_name'] = $copyFile;
                $input['thumbnail'] = $imagename;
                $input['file_type'] = $type;
                $input['user_id'] = $user->id;
                if($video->fill($input)->save()){
                    if(!empty($input['video_tag']) && count($input['video_tag']) > 0) {
                        $video->video_tags()->sync($input['video_tag']);
                    }
                    DB::commit();
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Saved Successfully.';
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("Error Processing Request", 1);
            }

        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function deleteMessage(Request $request) {
        DB::beginTransaction();
        try {

            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }
            $input = $request->all();
            $validator = Validator::make($input,[
                'delete_type'   =>  'required|in:E,M',
                'message_user'   =>  'required|in:S,O',
                'message_id'     => 'required|exists:messages,id',
                'chatting_id'   =>  'required|exists:chats,chat_uuid',
            ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            $message = Message::where('id', $input['message_id'])->first();
            $delete = [];

            if ($input['delete_type'] == 'E') {
                if ($message->user_id != $user->id) {
                    throw new Exception("Un-authorized.", 1);
                }
                $delete['delete_everyone'] = 1;
            } else {
                if (isset($input['chat_type']) && $input['chat_type'] == 'G') {
                    $delete_array = explode(',',$message->group_delete_message);
                    $delete_array = array_filter($delete_array);
                    array_push($delete_array, $user->id);
                    $delete['group_delete_message'] = implode(',', array_unique($delete_array));

                } else {
                    if ($input['message_user'] == 'S') {
                        if ($message->user_id != $user->id) {
                            throw new Exception("Un-authorized.", 1);
                        }
                        $delete['delete_one'] = $user->id;
                    } else {
                        $delete['delete_two'] = $user->id;
                    }
                }
            }

            if ($message->fill($delete)->save()) {
                DB::commit();
                $sentmessage  = Message::whereHas('user_conversations',function($query) use($input){
                    $query->where('chat_uuid',$input['chatting_id']);
                })->with(['user_conversations.one_users.user_details','user_conversations.two_users.user_details','senders','user_conversations.group_users'])->where('id',$input['message_id'])->first();

                $sentmessage['deleted_message_flag'] = 1;
                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Message Deleted successfully';
                $this->WebApiArray['data'] = $sentmessage;
                return response()->json($this->WebApiArray);
            }
            throw new Exception('Error Processing Request', 1);
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function deleteMedia(Request $request) {
        DB::beginTransaction();
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }
            $input = $request->all();
            $validator = Validator::make($input,[
                'media_id'   =>  'required|exists:messages,id',
            ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            $message = Message::where('id', $input['media_id'])->whereIn('message_type', [2,3])->first();

            if (!empty($message)) {
                if ($message->user_id == $user->id) {
                    $message->delete_one = $user->id;
                } else {
                    $message->delete_two = $user->id;
                }
                $message->save();
                DB::commit();
                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Media Deleted Successfully';
                return response()->json($this->WebApiArray);
            }
            throw new Exception('Media Id is not valid', 1);
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function rejectSession(Request $request) {
        DB::beginTransaction();
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }
            $input = $request->all();
            $validator = Validator::make($input,[
                'chatting_id'   =>  'required|exists:chats,chat_uuid',
                'session_request_id'   =>  'required|exists:session_requests,chat_session_uuid',
                'request_status'  => 'required|in:5',
            ],
                [
                    'chatting_id.required' => 'The chat id is required.',
                    'chatting_id.exists' => 'The chat id is invalid.',
                    'session_request_id.required' => 'The session request id is required.',
                    'session_request_id.exists' => 'The session request id is invalid.',
                    'request_status.required' => 'The status is required.',
                    'request_status.in' => 'The status is invalid.',
                ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            $user_request  = SessionRequest::whereHas('session_chats',function($query) use($input, $user){
                $query->whereRaw("( (one_user_id = ".$user->id." OR  two_user_id = ".$user->id.") AND chat_uuid = '".$input['chatting_id']."'  )");
            })->where(['athelete_id' => $user->id, 'chat_session_uuid' =>  $input['session_request_id'] ])->first();

            if (!empty($user_request)){

                if($user_request->athelete_user->deleted_status == 1 || $user_request->coach_user->deleted_status == 1)
                {
                    throw new Exception("This user account has been deleted.", 1);
                }
                if($user_request->status == 2) {
                    if ($user_request->fill(['status' => 5])->save()) {
                        $message_type = 8;

                        $userchat = Chat::whereRaw("( (one_user_id = ".auth()->id()." OR  two_user_id = ".auth()->id().") AND chat_uuid = '".$input['chatting_id']."'  )")->first();

                        if ($userchat->fill(['message_type' => $message_type])->save()) {
                            $chat_message_data = array();
                            $chat_message_data['user_id'] =  auth()->id();
                            $chat_message_data['message_type'] =  $message_type;
                            $chat_message_data['message_uuid'] =  Str::uuid()->toString();
                            if($createdmessage = $userchat->chat_messages()->create($chat_message_data)){
                                if($notification = $user_request->notifications()->create(['type' => 1, 'from_user_id' => $user->id, 'to_user_id' => $user_request->coach_id, 'data' => 'Your session price has been declined.', 'notification_uuid' => Str::uuid()->toString()])){
                                    DB::commit();
                                    sendFcmNotification($user_request->coach_id, 'Asportcoach', 'Your session price has been declined', "messenger", $user->id);
                                    $sentmessage  = Message::whereHas('user_conversations',function($query) use($input){
                                        $query->where('chat_uuid',$input['chatting_id']);
                                    })->with(['user_conversations.one_users.user_details','user_conversations.two_users.user_details','senders','user_conversations.group_users'])->where('id',$createdmessage->id)->first();

                                    $notification_data  = Notification::where('id',$notification->id)->first();
                                    $sentrequest  = SessionRequest::where('id',$user_request->id)->first();
                                    $this->sendFirebaseNotification($notification_data, 'user_notifications');
                                    $this->WebApiArray['status'] = true;
                                    $this->WebApiArray['message'] = 'Session price request declined successfully.';
                                    $this->WebApiArray['data']['message'] = $sentmessage;
                                    $this->WebApiArray['data']['session_request'] = $sentrequest;
                                    return response()->json($this->WebApiArray);
                                }
                                throw new Exception('Error Processing Request',1);
                            }
                            throw new Exception('Error Processing Request', 1);
                        }
                        throw new Exception('Error Processing Request', 1);
                    }
                    throw new Exception('Error Processing Request', 1);
                }
                throw new Exception("Invalid session request.", 1);
            }
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function chatReport(Request $request) {
        DB::beginTransaction();
        try {

            if (! $request->isMethod('post')) {
                throw new Exception("Method not allowed", 1);
            }

            $input = $request->all();
            $validator = Validator::make($input, [
                'title' => 'required',
                'description' => 'required',
                'from_user' => 'required|exists:users,id',
                'to_user' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $chat_user = User::find($input['to_user']);

            if ($chat_user->deleted_status) {
                throw new Exception("This user account has been deleted.", 1);
            }

            $report = new Report();
            if ($report->fill($input)->save()) {
                DB::commit();
                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Report sent successfully';
                return response()->json($this->WebApiArray);
            }
            throw new Exception("Error processing request",1);
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function sessionOrder(Request $request)
    {
        DB::beginTransaction();
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }

            $input = $request->all();

            $validator = Validator::make($input, [
                'chatting_id'   =>  'required|exists:chats,chat_uuid',
                'session_request_id'   =>  'required|exists:session_requests,chat_session_uuid',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }


            $checkdeletedChatUser = Chat::whereRaw("( (one_user_id = ".auth()->id()." OR  two_user_id = ".auth()->id().") AND chat_uuid = '".$input['chatting_id']."'  )")->first();

            if(empty($checkdeletedChatUser))
            {
                throw new Exception("Invalid chat.", 1);
            }
            if($checkdeletedChatUser->one_users->deleted_status == 1 || $checkdeletedChatUser->two_users->deleted_status == 1)
            {
                throw new Exception("This user account has been deleted.", 1);
            }

            $user = $this->getAuthenticatedUser();

            $user_requests  = SessionRequest::whereHas('session_chats',function($query) use($input, $user){
                $query->whereRaw("( (one_user_id = ".$user->id." OR  two_user_id = ".$user->id.") AND chat_uuid = '".$input['chatting_id']."'  )");
            })->where(['athelete_id' => auth()->id(), 'chat_session_uuid' =>  $input['session_request_id'] ])->first();

            if (!empty($user_requests)) {
                if ($user_requests->status == 2) {

                    $order_id = Str::uuid()->toString();

                    $platform_fees = $user_requests->session_platform_fees;
                    $service_tax =  $user_requests->session_price_vat;
                    $total_price = $user_requests->total_session_price;

                    $transaction = Order::create([
                        'order_uuid' => $order_id,
                        'user_id' => auth()->id(),
                        'session_request_id' => $user_requests->id,
                        'order_type' => 1,
                        'price' => $user_requests->session_price,
                        'service_tax' => $service_tax,
                        'total_price' => $total_price,
                        'transaction_fees' => $platform_fees,
                    ]);

                    if ($transaction) {
                        DB::commit();
                        $this->WebApiArray['status'] = true;
                        $this->WebApiArray['message'] = 'Order Created Successfully';
                        $this->WebApiArray['data'] = $transaction;
                        return response()->json($this->WebApiArray);
                    }
                    throw new Exception('Error processing request', 1);
                }
                throw new Exception('Error processing request', 1);
            }
            throw new Exception('Error processing request', 1);
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function sessionPayment(Request $request)
    {
        DB::beginTransaction();
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }

            $input = $request->all();

            $validator = Validator::make($input, [
                'card_type' => 'required|in:N,S',
                'payment_type' => 'required|in:1,2'
            ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            if ($input['card_type'] == 'N') {
                $validator = Validator::make($input, [
                    'stripe_token' => 'required',
                    'chatting_id'   =>  'required|exists:chats,chat_uuid',
                    'session_request_id'   =>  'required|exists:session_requests,chat_session_uuid',
                ]);
            } else {
                $validator = Validator::make($input, [
                    'card_id' => 'required',
                    'chatting_id'   =>  'required|exists:chats,chat_uuid',
                    'session_request_id'   =>  'required|exists:session_requests,chat_session_uuid',
                ]);
            }

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }


            $checkdeletedChatUser = Chat::whereRaw("( (one_user_id = ".auth()->id()." OR  two_user_id = ".auth()->id().") AND chat_uuid = '".$input['chatting_id']."'  )")->first();

            if(empty($checkdeletedChatUser))
            {
                throw new Exception("Invalid chat.", 1);
            }
            if($checkdeletedChatUser->one_users->deleted_status == 1 || $checkdeletedChatUser->two_users->deleted_status == 1)
            {
                throw new Exception("This user account has been deleted.", 1);
            }

            $user = $this->getAuthenticatedUser();

            $user_requests  = SessionRequest::whereHas('session_chats',function($query) use($input, $user){
                $query->whereRaw("( (one_user_id = ".$user->id." OR  two_user_id = ".$user->id.") AND chat_uuid = '".$input['chatting_id']."'  )");
            })->where(['athelete_id' => $user->id, 'chat_session_uuid' =>  $input['session_request_id'] ])->first();

            if (!empty($user_requests)) {
                if ($user_requests->status == 2) {

                    $order_id = Str::uuid()->toString();

                    $platform_fees = $user_requests->session_platform_fees;
                    $service_tax =  $user_requests->session_price_vat;
                    $total_price = $user_requests->total_session_price;

                    $transaction = Order::create([
                        'order_uuid' => $order_id,
                        'user_id' => $user->id,
                        'session_request_id' => $user_requests->id,
                        'order_type' => 1,
                        'price' => $user_requests->session_price,
                        'service_tax' => $service_tax,
                        'total_price' => $total_price,
                        'transaction_fees' => $platform_fees,

                    ]);

                    if ($transaction) {

                        $stripe = Stripe::make(env('STRIPE_SECRET'));
                        $stripe->setApiKey(env('STRIPE_SECRET'));


                        $shipping = [
                            'name' => $user->name,
                            'address' => [
                                'line1' => '510 Townsend St',
                                'postal_code' => '98140',
                                'city' => 'San Francisco',
                                'state' => 'CA',
                                'country' => 'US'
                            ]
                        ];

                        $stripe_customer_id = self::SaveStripeId();

                        if(empty($stripe_customer_id)){
                            throw new Exception("Error occured while processing payment! Please try again.", 1);
                        }

                        if ($input['card_type'] == 'N') {
                            if (isset($input['save_card']) && $input['save_card'] == 1) {
                                //Create Stripe Token
                                $StripeToken = $stripe->tokens()->find($input['stripe_token']);

                                $card = self::fetchStripeCardDetails($StripeToken);
                                if (count($card) == 0) {
                                    throw new Exception("Error occured while processing payment! Please try again.", 1);
                                }
                                $charge = $stripe->charges()->create([
                                    'currency' => 'EUR',
                                    'amount' => $total_price,
                                    'description' => 'Payment for session request.',
                                    "customer" => $stripe_customer_id,
                                    "source" => $card['id'],
                                    "metadata" => [
                                        'order_uuid' => $order_id,
                                        'type' => 'Session Request',
                                    ]
                                ]);

                            } else {
                                $charge = $stripe->charges()->create([
                                    'card' => $input['stripe_token'],
                                    'currency' => 'EUR',
                                    'amount' => $total_price,
                                    'description' => 'Payment for session request',
                                    'shipping' => $shipping,
                                    "metadata" => [
                                        'order_uuid' => $order_id,
                                        'type' => 'Session Request'
                                    ]
                                ]);
                            }
                        } else {
                            $charge = $stripe->charges()->create([
                                'currency' => 'EUR',
                                'amount' =>  $total_price,
                                'description' => 'Payment for session request',
                                'shipping' => $shipping,
                                "customer" => $stripe_customer_id,
                                "source" => $input['card_id'],
                                "metadata" => [
                                    'order_uuid' => $order_id,
                                    'type' => 'Session Request'
                                ]
                            ]);
                        }

                        if ($charge['status'] == 'succeeded') {
                            if ($user_requests->request_by == 2) {
                                $request_data['status'] = 6;
                                $request_data['start_session_time'] = Carbon::now();
                            } else {
                                $request_data['status'] = 4;
                            }

                            if ($user_requests->fill($request_data)->save()) {
                                $message_type = 7;
                                $userchat = Chat::whereRaw("( (one_user_id = " . $user->id . " OR  two_user_id = " . $user->id . ") AND chat_uuid = '" . $input['chatting_id'] . "'  )")->first();

                                if ($userchat->fill(['message_type' => $message_type])->save()) {
                                    $chat_message_data = array();
                                    $chat_message_data['user_id'] = $user->id;
                                    $chat_message_data['message_type'] = $message_type;
                                    $chat_message_data['message_uuid'] = Str::uuid()->toString();
                                    if ($createdmessage = $userchat->chat_messages()->create($chat_message_data)) {
                                        if ($notification = $user_requests->notifications()->create(['type' => 1, 'from_user_id' => $user->id, 'to_user_id' => $user_requests->coach_id, 'data' => 'Your session price has been accepted.', 'notification_uuid' => Str::uuid()->toString()])) {
                                            if ($transaction->fill(['transaction_id' => $charge['id'], 'payment_type' => $input['payment_type'], 'status' => 1])->save()) {
                                                DB::commit();
                                                $sentmessage = Message::whereHas('user_conversations', function ($query) use ($input) {
                                                    $query->where('chat_uuid', $input['chatting_id']);
                                                })->with(['user_conversations.one_users.user_details', 'user_conversations.two_users.user_details', 'senders'])->where('id', $createdmessage->id)->first();
                                                sendFcmNotification($user_requests->coach_id, 'Asportcoach', 'Your session price has been accepted', "messenger", $user->id);
                                                $notification_data = Notification::where('id', $notification->id)->first();
                                                $sentrequest = SessionRequest::where('id', $user_requests->id)->first();
                                                $this->sendFirebaseNotification($notification_data, 'user_notifications');
                                                $this->WebApiArray['status'] = true;
                                                $this->WebApiArray['message'] = 'Payment Successful, Your Transaction id is ' . $charge['id'] . ' . Please wait sometime for session.';
                                                $this->WebApiArray['data']['message'] = $sentmessage;
                                                $this->WebApiArray['data']['session_request'] = $sentrequest;
                                                return response()->json($this->WebApiArray);
                                            }
                                            throw new Exception('Error processing request', 1);
                                        }
                                        throw new Exception('Error processing request', 1);
                                    }
                                    throw new Exception('Error processing request', 1);
                                }
                                throw new Exception('Error processing request', 1);
                            }
                            throw new Exception('Error processing request', 1);
                        }
                        throw new Exception("Transaction failed! Please try after sometime.", 1);
                    }
                    throw new Exception('Error processing request', 1);
                }
                throw new Exception('Error processing request', 1);
            }
            throw new Exception('Error processing request', 1);
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        } catch (\Cartalyst\Stripe\Exception\CardErrorException $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        } catch (\Cartalyst\Stripe\Exception\MissingParameterException $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function sessionPaymentAndroid(Request $request)
    {
        DB::beginTransaction();
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }

            $input = $request->all();

            $validator = Validator::make($input, [
                'card_type' => 'required|in:N,S',
                'payment_type' => 'required|in:1,2'
            ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            if ($input['card_type'] == 'N') {
                $validator = Validator::make($input, [
                    'card_holder' => 'required',
                    'card_number' => 'required|max:20',
                    'month' => 'required|min:2|max:2',
                    'year' =>   'required|max:4|min:4',
                    'cvc' =>    'required|max:4',
                    'chatting_id'   =>  'required|exists:chats,chat_uuid',
                    'session_request_id'   =>  'required|exists:session_requests,chat_session_uuid',
                ]);
            } else {
                $validator = Validator::make($input, [
                    'card_id' => 'required',
                    'chatting_id'   =>  'required|exists:chats,chat_uuid',
                    'session_request_id'   =>  'required|exists:session_requests,chat_session_uuid',
                ]);
            }

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }


            $checkdeletedChatUser = Chat::whereRaw("( (one_user_id = ".auth()->id()." OR  two_user_id = ".auth()->id().") AND chat_uuid = '".$input['chatting_id']."'  )")->first();

            if(empty($checkdeletedChatUser))
            {
                throw new Exception("Invalid chat.", 1);
            }
            if($checkdeletedChatUser->one_users->deleted_status == 1 || $checkdeletedChatUser->two_users->deleted_status == 1)
            {
                throw new Exception("This user account has been deleted.", 1);
            }

            $user = $this->getAuthenticatedUser();

            $user_requests  = SessionRequest::whereHas('session_chats',function($query) use($input, $user){
                $query->whereRaw("( (one_user_id = ".$user->id." OR  two_user_id = ".$user->id.") AND chat_uuid = '".$input['chatting_id']."'  )");
            })->where(['athelete_id' => $user->id, 'chat_session_uuid' =>  $input['session_request_id'] ])->first();

            if (!empty($user_requests)) {
                if ($user_requests->status == 2) {

                    $order_id = Str::uuid()->toString();

                    $platform_fees = $user_requests->session_platform_fees;
                    $service_tax =  $user_requests->session_price_vat;
                    $total_price = $user_requests->total_session_price;

                    $transaction = Order::create([
                        'order_uuid' => $order_id,
                        'user_id' => $user->id,
                        'session_request_id' => $user_requests->id,
                        'order_type' => 1,
                        'price' => $user_requests->session_price,
                        'service_tax' => $service_tax,
                        'total_price' => $total_price,
                        'transaction_fees' => $platform_fees,

                    ]);

                    if ($transaction) {

                        $stripe = Stripe::make(env('STRIPE_SECRET'));
                        $stripe->setApiKey(env('STRIPE_SECRET'));


                        $shipping = [
                            'name' => $user->name,
                            'address' => [
                                'line1' => '510 Townsend St',
                                'postal_code' => '98140',
                                'city' => 'San Francisco',
                                'state' => 'CA',
                                'country' => 'US'
                            ]
                        ];

                        $stripe_customer_id = self::SaveStripeId();

                        if(empty($stripe_customer_id)){
                            throw new Exception("Error occured while processing payment! Please try again.", 1);
                        }

                        if ($input['card_type'] == 'N') {

                            //create token
                            $token = $stripe->tokens()->create([
                                'card' => [
                                    'number'    => $input['card_number'],
                                    'exp_month' => $input['month'],
                                    'exp_year'  => $input['year'],
                                    'cvc'       => $input['cvc'],
                                ],
                            ]);

                            $input['stripe_token'] = $token['id'];

                            if (isset($input['save_card']) && $input['save_card'] == 1) {
                                //Create Stripe Token
                                $StripeToken = $stripe->tokens()->find($input['stripe_token']);

                                $card = self::fetchStripeCardDetails($StripeToken);
                                if (count($card) == 0) {
                                    throw new Exception("Error occured while processing payment! Please try again.", 1);
                                }
                                $charge = $stripe->charges()->create([
                                    'currency' => 'EUR',
                                    'amount' => $total_price,
                                    'description' => 'Payment for session request.',
                                    "customer" => $stripe_customer_id,
                                    "source" => $card['id'],
                                    "metadata" => [
                                        'order_uuid' => $order_id,
                                        'type' => 'Session Request',
                                    ]
                                ]);

                            } else {
                                $charge = $stripe->charges()->create([
                                    'card' => $input['stripe_token'],
                                    'currency' => 'EUR',
                                    'amount' => $total_price,
                                    'description' => 'Payment for session request',
                                    'shipping' => $shipping,
                                    "metadata" => [
                                        'order_uuid' => $order_id,
                                        'type' => 'Session Request'
                                    ]
                                ]);
                            }
                        } else {
                            $charge = $stripe->charges()->create([
                                'currency' => 'EUR',
                                'amount' =>  $total_price,
                                'description' => 'Payment for session request',
                                'shipping' => $shipping,
                                "customer" => $stripe_customer_id,
                                "source" => $input['card_id'],
                                "metadata" => [
                                    'order_uuid' => $order_id,
                                    'type' => 'Session Request'
                                ]
                            ]);
                        }

                        if ($charge['status'] == 'succeeded') {
                            if ($user_requests->request_by == 2) {
                                $request_data['status'] = 6;
                                $request_data['start_session_time'] = Carbon::now();
                            } else {
                                $request_data['status'] = 4;
                            }

                            if ($user_requests->fill($request_data)->save()) {
                                $message_type = 7;
                                $userchat = Chat::whereRaw("( (one_user_id = " . $user->id . " OR  two_user_id = " . $user->id . ") AND chat_uuid = '" . $input['chatting_id'] . "'  )")->first();

                                if ($userchat->fill(['message_type' => $message_type])->save()) {
                                    $chat_message_data = array();
                                    $chat_message_data['user_id'] = $user->id;
                                    $chat_message_data['message_type'] = $message_type;
                                    $chat_message_data['message_uuid'] = Str::uuid()->toString();
                                    if ($createdmessage = $userchat->chat_messages()->create($chat_message_data)) {
                                        if ($notification = $user_requests->notifications()->create(['type' => 1, 'from_user_id' => $user->id, 'to_user_id' => $user_requests->coach_id, 'data' => 'Your session price has been accepted.', 'notification_uuid' => Str::uuid()->toString()])) {
                                            if ($transaction->fill(['transaction_id' => $charge['id'], 'payment_type' => $input['payment_type'], 'status' => 1])->save()) {
                                                DB::commit();
                                                $sentmessage = Message::whereHas('user_conversations', function ($query) use ($input) {
                                                    $query->where('chat_uuid', $input['chatting_id']);
                                                })->with(['user_conversations.one_users.user_details', 'user_conversations.two_users.user_details', 'senders'])->where('id', $createdmessage->id)->first();
                                                sendFcmNotification($user_requests->coach_id, 'Asportcoach', 'Your session price has been accepted', "messenger", $user->id);
                                                $notification_data = Notification::where('id', $notification->id)->first();
                                                $sentrequest = SessionRequest::where('id', $user_requests->id)->first();
                                                $this->sendFirebaseNotification($notification_data, 'user_notifications');
                                                $this->WebApiArray['status'] = true;
                                                $this->WebApiArray['message'] = 'Payment Successful, Your Transaction id is ' . $charge['id'] . ' . Please wait sometime for session.';
                                                $this->WebApiArray['data']['message'] = $sentmessage;
                                                $this->WebApiArray['data']['session_request'] = $sentrequest;
                                                return response()->json($this->WebApiArray);
                                            }
                                            throw new Exception('Error processing request', 1);
                                        }
                                        throw new Exception('Error processing request', 1);
                                    }
                                    throw new Exception('Error processing request', 1);
                                }
                                throw new Exception('Error processing request', 1);
                            }
                            throw new Exception('Error processing request', 1);
                        }
                        throw new Exception("Transaction failed! Please try after sometime.", 1);
                    }
                    throw new Exception('Error processing request', 1);
                }
                throw new Exception('Error processing request', 1);
            }
            throw new Exception('Error processing request', 1);
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        } catch (\Cartalyst\Stripe\Exception\CardErrorException $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        } catch (\Cartalyst\Stripe\Exception\MissingParameterException $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function fetchUserCards(Request $request)
    {
        try {
            $user = $this->getAuthenticatedUser();
            $secret = config('staging_live_config.STRIPE.STRIPE_SECRET');
            if (!empty(auth()->user()->stripe_id)) {
                $stripe = Stripe::make($secret);
                $stripe->setApiKey($secret);

                $cards = $stripe->cards()->all($user->stripe_id);

                $this->WebApiArray['status'] = true;
                if(count($cards['data']) > 0){
                    $this->WebApiArray['message'] = 'Record found.';
                    $this->WebApiArray['data'] = $cards['data'];
                    $this->WebApiArray['statusCode'] = 0;
                } else {
                    $this->WebApiArray['statusCode'] = 1;
                    $this->WebApiArray['message'] = 'Record not found.';
                }
                return response()->json($this->WebApiArray);
            }
            throw new Exception("No card found.", 1);
        }   catch (NotFoundException $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }  catch (BadRequestException $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        } catch (InvalidRequestException $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        } catch (CardErrorException $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }

    }

    public function SaveStripeId()
    {
        try{
            $LoggedInUser = User::where('id',auth()->id())->first();
            $customer_id  = $LoggedInUser->stripe_id;
            $stripe = Stripe::make(env('STRIPE_SECRET'));
            $stripe->setApiKey(env('STRIPE_SECRET'));
            if(empty($customer_id)){
                $customer = $stripe->customers()->create([
                    'email' => $LoggedInUser->email
                ]);
                $LoggedInUser->stripe_id = $customer['id'];
                $LoggedInUser->save();
                $customer_id = $LoggedInUser->stripe_id;
            }
            return $customer_id;
        }catch (Exception $e) {
            return "";
        }
        catch (\Cartalyst\Stripe\Exception\CardErrorException $e) {
            return "";
        }
        catch (\Cartalyst\Stripe\Exception\MissingParameterException $e) {
            return "";
        }
    }

    public function fetchStripeCardDetails($StripeToken)
    {
        $new_card = array();
        try{
            $StripeUser = User::where('id',auth()->id())->first();
            $customer_id = $StripeUser->stripe_id;
            $stripe = Stripe::make(env('STRIPE_SECRET'));
            $stripe->setApiKey(env('STRIPE_SECRET'));
            $stripe_cards = $stripe->cards()->all($StripeUser->stripe_id);
            $card_fingerprints = array();
            if(count($stripe_cards) > 0 )
            {
                $card_fingerprints = array_column($stripe_cards['data'], 'fingerprint');
            }
            if(!in_array($StripeToken['card']['fingerprint'], $card_fingerprints))
            {
                $card = $stripe->cards()->create($StripeUser->stripe_id, $StripeToken['id']);
            }
            $stripe_cards = $stripe->cards()->all($StripeUser->stripe_id);
            if(count($stripe_cards) > 0)
            {
                foreach($stripe_cards['data'] as $stripe_card_key => $stripe_card_val)
                {
                    if($StripeToken['card']['fingerprint'] == $stripe_card_val['fingerprint']){
                        $new_card = $stripe_card_val;
                    }
                }
            }
            return $new_card;
        }catch (Exception $e) {
            return $new_card;
        }
        catch (\Cartalyst\Stripe\Exception\CardErrorException $e) {
            return $new_card;
        }
        catch (\Cartalyst\Stripe\Exception\MissingParameterException $e) {
            return $new_card;
        }
    }

    public function getGroupTeam() {
        try {

            $user = $this->getAuthenticatedUser();

            $teams = (new Team())->newQuery();
            $teams = $teams->selectRaw('id, title')->doesntHave('team_group')->whereHas('team_users',function($query){
                $query->where('team_user.status', 1);
            })->where('user_id', $user->id)->orderBy('title','ASC')->get();


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

    public function fetchNewUser(Request $request)
    {
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }
            $input = $request->all();
            $validator = Validator::make($input,[
                'chatting_id'   =>  'required|exists:chats,chat_uuid',
            ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $users = Chat::where(['chat_uuid'=> $input['chatting_id'],'chat_type'=>'2'])->first();

            $newusers = array();
            $userChats = Chat::where('chat_type',1)->with('one_users:id,name', 'two_users:id,name')->where('chat_type',1)
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

            $userChats->map(function ($chat) {
                $chat->one_users->makeHidden(['remaining_balance','total_balance', 'user_details', 'roles', 'role_type']);
                $chat->two_users->makeHidden(['remaining_balance','total_balance', 'user_details', 'roles', 'role_type']);
            });

            foreach ($userChats as $userChatKey => $userChat) {
                $filter_user = $userChat->one_users->id != auth()->id() ? $userChat->one_users : $userChat->two_users;
                $check_user = GroupUsers::where(['user_id' => $filter_user->id, 'chat_id' => $users->id, 'status' => 1])->first();
                if (empty($check_user)) {
                    array_push($newusers,$userChat->one_users->id != auth()->id() ? $userChat->one_users : $userChat->two_users);
                }
            }

            if($users->group_users->count() > 0){
                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data'] = $newusers;
                return response()->json($this->WebApiArray);
            }
            throw new Exception("No record found.", 1);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }

    }

}