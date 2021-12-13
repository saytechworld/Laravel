<?php

namespace App\Services\Coach;

use App\Models\ChatMeeting;
use App\Models\GroupUsers;
use App\Models\SessionRequest;
use App\Models\Team;
use App\Models\UserFolder;
use App\Models\Video;
use Cartalyst\Stripe\Exception\BadRequestException;
use Cartalyst\Stripe\Exception\CardErrorException;
use Cartalyst\Stripe\Exception\InvalidRequestException;
use Cartalyst\Stripe\Exception\NotFoundException;
use Cartalyst\Stripe\Stripe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception, Validator, File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Chat;
use App\Models\Message;
use Kreait\Firebase;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use App\Models\Notification;
use function GuzzleHttp\Promise\all;

trait UserChatTrait
{
    public function index(Request $request)
    {
        $athelete_chat_uuid = !empty(session()->get('athelete_chat')) ? session()->get('athelete_chat') : "";
        $request_chat = "";
        if(!empty($athelete_chat_uuid))
        {
          $request_chat = Chat::where('chat_uuid',$athelete_chat_uuid)->first();
        }

        $teams = (new Team())->newQuery();
        $teams = $teams->doesntHave('team_group')->whereHas('team_users',function($query){
            $query->where('team_user.status', 1);
        })->where('user_id', auth()->id())->orderBy('title','ASC')->paginate(25);

        $users = array();
        $userChats = Chat::where('chat_type',1)->with('one_users', 'two_users')->where('chat_type',1)
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
            array_push($users,$userChat->one_users->id != auth()->id() ? $userChat->one_users : $userChat->two_users);
        }

        session()->forget('athelete_chat');
        return view('Coach.chat.index',compact('request_chat','users', 'teams'));
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function fetchChatUser(Request $request)
    {
        try {
            if($request->ajax()){
                $input = $request->all();
                $chatusers = (new Chat)->newQuery();
                $chatusers = $chatusers->withCount(['chat_messages' => function($query){
                                            $query->whereRaw("( user_id != ".auth()->id()." AND  read_flag != 1 )")
                                                ->whereRaw("(group_read_message is null OR NOT FIND_IN_SET(".auth()->id().",group_read_message))");
                                        }])->with(['one_users.user_details','two_users.user_details', 'group_users'])
                                        ->where(function ($query1) {
                                            $query1->whereRaw("(one_user_id = ".auth()->id()." OR two_user_id = ".auth()->id()." )");
                                            $query1->orWhereHas('group_users',function ( $subquery ){
                                                $subquery->where('user_id', auth()->id());
                                            });
                                        })
                                        ->whereRaw("(delete_chat is null OR NOT FIND_IN_SET(".auth()->id().",delete_chat))")
                                        ->orderBy('updated_at','DESC')
                                        ->get();

                $chatusers->each(function ($item , $key) {
                    if ($item->chat_type == 2) {
                        $item->group_users->each(function ($item1, $key) use($item) {
                            if ($item1->user_id == auth()->id() && $item1->status != 1) {
                                $message_count = Message::where('chat_id', $item->id)->where('created_at', '<=', $item1->updated_at)
                                    ->whereRaw("( user_id != ".auth()->id()." AND  read_flag != 1 ) && (group_read_message is null OR NOT FIND_IN_SET(".auth()->id().",group_read_message))")->count();
                                $item->chat_messages_count = $message_count;
                            }
                        });
                    }
                });

                if($chatusers->count() > 0){
                    $this->WebApiArray['error'] = false;
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Record found.';
                    $this->WebApiArray['data']['result'] = $chatusers;
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("No record found.", 1);
            }
            throw new Exception("Request not allowed.", 1);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }

    }

    public function startUserChating(Request $request, $user_uuid)
    {
        try {
            if (auth()->user()->test_user == 1) {
                $test_user = 1;
            } else {
                $test_user = 0;
            }
            $user = User::whereHas('roles',function($query){
                $query->whereIn('role_id',[3,4]);
            })->where('id','!=', auth()->id())
                ->where('test_user',$test_user)
                ->whereRaw("(user_uuid = '".$user_uuid."' AND status = 1 AND confirmed = 1 AND deleted_status != 1 )")->first();

            if(!empty($user)){
                $coach_roles = auth()->user()->roles->pluck('id')->toArray();
                if( count($coach_roles) > 0 && in_array(3, $coach_roles))
                {
                    $checkchat = Chat::whereRaw("( (one_user_id = ".auth()->id()." AND  two_user_id = ".$user->id.") OR (two_user_id = ".auth()->id()." AND  one_user_id = ".$user->id.")    )")->first();
                    if(empty($checkchat)){
                        $createdchat = new Chat;
                        $chat_data['one_user_id'] =  auth()->id();
                        $chat_data['two_user_id'] =  $user->id;
                        $chat_data['chat_uuid'] =  Str::uuid()->toString();
                        $createdchat->fill($chat_data)->save();

                        /*$title = 'Video';
                        $password = str_random(6);
                        $duration = 1;
                        $schedule = scheduledMeeting($duration, $password, '2020-08-20T12:02:00Z', $title, $title);
                        $schedule = json_decode($schedule);
                        if (!isset($schedule->id) || empty($schedule->id)) {
                            throw new Exception("Error in meeting create",1);
                        }

                        $meeting = new ChatMeeting();
                        $meeting->chat_id = $createdchat->id;
                        $meeting->meeting_id = (string)$schedule->id;
                        $meeting->meeting_password = $password;
                        $meeting->save();*/
                    }
                    $chat = Chat::whereRaw("( (one_user_id = ".auth()->id()." AND  two_user_id = ".$user->id.") OR (two_user_id = ".auth()->id()." AND  one_user_id = ".$user->id.")    )")->first();

                    if(!empty($chat)){
                        if(!empty($chat->delete_chat)) {
                            $delete_chat_array = explode(',',$chat->delete_chat);
                            $delete_chat_array = array_filter($delete_chat_array);
                            $key = array_search(auth()->id(), $delete_chat_array);
                            unset($delete_chat_array[$key]);
                            $chat->delete_chat = implode(',', array_unique($delete_chat_array));
                            $chat->save();
                        }
                        session()->put('athelete_chat',$chat->chat_uuid);
                        return redirect()->route('coach.chat.index');
                    }
                    throw new Exception("Chat could not be initiated.", 1);
                }
                throw new Exception("You can't initiated this chat! You are not belongs to athelete role.", 1);
            }
            throw new Exception("This coach doesn't belong to our database.", 1);
        } catch (Exception $e) {
            return redirect()->route('coach.dashboard')->withError($e->getMessage());
        }
    }

    public function startGroupChating(Request $request, $chat_uuid)
    {
        try {
            session()->put('athelete_chat',$chat_uuid);
            return redirect()->route('coach.chat.index');
        } catch (Exception $e) {
            return redirect()->route('coach.dashboard')->withError($e->getMessage());
        }
    }

    public function fetchUserMessage(Request $request)
    {
        try {
            if($request->ajax()){
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
                /*$unread_messages = Message::whereHas('user_conversations',function($query) use($input){
                                                    $query->whereRaw( "(chat_uuid = '".$input['chatting_id']."' AND (one_user_id = ".auth()->id()." OR two_user_id = ".auth()->id()."))");
                                                })->whereRaw("( user_id != ".auth()->id()." AND  read_flag != 1 )")->pluck('id')->toArray();
                if(count($unread_messages) > 0)
                {
                   Message::whereIn('id',$unread_messages)->update(['read_flag' => 1]);
                }*/
                $user_messages  = (new Message)->newQuery()->with('senders');
                $user_messages = $user_messages->whereNull('delete_everyone');
                $user_messages  = $user_messages->whereHas('user_conversations',function($query) use($input){
                                                    $query->where('chat_uuid',$input['chatting_id']);
                                                })->whereRaw("((delete_one is null or delete_one != ".auth()->id().") AND (delete_two is null or  delete_two != ".auth()->id()."))")->with(['senders'])->orderBy('id', 'DESC')->paginate(25);

                if($user_messages->count() > 0){
                    $this->WebApiArray['error'] = false;
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Record found.';
                    $this->WebApiArray['data']['result'] = $user_messages;
                    return response()->json($this->WebApiArray);
                } else {
                    $this->WebApiArray['error'] = true;
                    $this->WebApiArray['status'] = false;
                    $this->WebApiArray['message'] = 'No record found.';
                    $this->WebApiArray['data'] = 0;
                    return response()->json($this->WebApiArray);
                }
            }
            throw new Exception("Request not allowed.", 1);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function fetchGroupMessage(Request $request)
    {
        try {
            if($request->ajax()){
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

                $group_user = GroupUsers::whereHas('chat',function($query) use($input){
                    $query->where('chat_uuid',$input['chatting_id']);
                })->where('user_id', auth()->id())->first();

                if (!empty($group_user)) {
                    if ($group_user->status == 1) {
                        $user_messages  = (new Message)->newQuery()->with('senders');
                        $user_messages = $user_messages->whereNull('delete_everyone');
                        $user_messages  = $user_messages->whereHas('user_conversations',function($query) use($input){
                            $query->where('chat_uuid',$input['chatting_id']);
                        })->whereRaw("(group_delete_message is null OR NOT FIND_IN_SET(".auth()->id().",group_delete_message))")->with(['senders'])->orderBy('id', 'DESC')->paginate(25);
                    } else {
                        $user_messages  = (new Message)->newQuery()->with('senders');
                        $user_messages = $user_messages->whereNull('delete_everyone');
                        $user_messages  = $user_messages->whereHas('user_conversations',function($query) use($input){
                            $query->where('chat_uuid',$input['chatting_id']);
                        })->whereRaw("(group_delete_message is null OR NOT FIND_IN_SET(".auth()->id().",group_delete_message))")->with(['senders'])
                            ->where('created_at', '<=', $group_user->updated_at)->orderBy('id', 'DESC')->paginate(25);
                    }
                    if($user_messages->count() > 0){
                        $this->WebApiArray['error'] = false;
                        $this->WebApiArray['status'] = true;
                        $this->WebApiArray['message'] = 'Record found.';
                        $this->WebApiArray['data']['result'] = $user_messages;
                        return response()->json($this->WebApiArray);
                    } else {
                        $this->WebApiArray['error'] = true;
                        $this->WebApiArray['status'] = false;
                        $this->WebApiArray['message'] = 'No record found.';
                        $this->WebApiArray['data'] = 0;
                        return response()->json($this->WebApiArray);
                    }
                }
                throw new Exception("Group user not exist.", 1);
            }
            throw new Exception("Request not allowed.", 1);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function makeReadMessage(Request $request)
    {
        try {
            if($request->ajax()){
                if(!$request->isMethod('post'))
                {
                    throw new Exception("Request not allowed.", 1);
                }
                $input = $request->all();
                $validator = Validator::make($input,[
                    'chatting_id'   =>  'required|exists:chats,chat_uuid',
                    'type'      => 'required|in:R,U,A',
                    'message_id'   =>  'required_if:type,==,R|exists:messages,message_uuid',
                ],
                [
                    'chatting_id.required' => 'The chat id is required.',
                    'chatting_id.exists' => 'The chat id is invalid.',
                ]);
                if ($validator->fails()) {
                    throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
                }
                if($input['type'] == 'R')
                {
                    $unread_message = Message::whereHas('user_conversations',function($query) use($input){
                                                    $query->whereRaw( "(chat_uuid = '".$input['chatting_id']."' AND (one_user_id = ".auth()->id()." OR two_user_id = ".auth()->id()."))");
                                                })->whereRaw("( user_id != ".auth()->id()." AND  read_flag != 1 AND message_uuid = '".$input['message_id']."' )")->first();
                    if(!empty($unread_message))
                    {
                        $unread_message->read_flag = 1;
                        $unread_message->save();
                    }
                }

                if($input['type'] == 'A')
                {
                    $unread_messages = Message::whereHas('user_conversations',function($query) use($input){
                                                    $query->whereRaw( "(chat_uuid = '".$input['chatting_id']."' AND (one_user_id = ".auth()->id()." OR two_user_id = ".auth()->id()."))");
                                                })->whereRaw("( user_id != ".auth()->id()." AND  read_flag != 1 )")->pluck('id')->toArray();
                    if(count($unread_messages) > 0)
                    {
                       Message::whereIn('id',$unread_messages)->update(['read_flag' => 1]);
                    }
                }
                $chatuser = Chat::withCount(['chat_messages' => function($query){
                                        $query->whereRaw("( user_id != ".auth()->id()." AND  read_flag != 1 )");
                                    }])->whereRaw( "(chat_uuid = '".$input['chatting_id']."' AND (one_user_id = ".auth()->id()." OR two_user_id = ".auth()->id()."))")->with(['one_users.user_details','two_users.user_details'])->orderBy('updated_at','DESC')->first();
                if(!empty($chatuser)){
                    $unread_message =   Message::whereHas('user_conversations',function($query){
                                        $query->whereRaw( "((one_user_id = ".auth()->id()." OR two_user_id = ".auth()->id()."))");
                                    })->whereRaw("( user_id != ".auth()->id()." AND  read_flag != 1 )")
                        ->whereRaw("(group_read_message is null OR NOT FIND_IN_SET(".auth()->id().",group_read_message))")->count();

                    $this->WebApiArray['error'] = false;
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Record found.';
                    $this->WebApiArray['data']['result'] = $chatuser;
                    $this->WebApiArray['data']['unread_message_count'] = $unread_message;
                    return response()->json($this->WebApiArray);   
                }
                throw new Exception("No record found.", 1);
            }
            throw new Exception("Request not allowed.", 1);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }

    }

    public function makeGroupReadMessage(Request $request)
    {
        try {
            if($request->ajax()){
                if(!$request->isMethod('post'))
                {
                    throw new Exception("Request not allowed.", 1);
                }
                $input = $request->all();
                $validator = Validator::make($input,[
                    'chatting_id'   =>  'required|exists:chats,chat_uuid',
                    'type'      => 'required|in:R,U,A',
                    'message_id'   =>  'required_if:type,==,R|exists:messages,message_uuid',
                ],
                [
                    'chatting_id.required' => 'The chat id is required.',
                    'chatting_id.exists' => 'The chat id is invalid.',
                ]);
                if ($validator->fails()) {
                    throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
                }
                if($input['type'] == 'R')
                {
                    $unread_message = Message::whereHas('user_conversations',function($query) use($input){
                                                    $query->where('chat_uuid', $input['chatting_id'])->whereHas('group_users',function ( $subquery ){
                                                        $subquery->where('user_id', auth()->id());
                                                    });
                                                })->whereRaw("( user_id != ".auth()->id()." AND  read_flag != 1 AND message_uuid = '".$input['message_id']."' ) AND (group_read_message is null OR NOT FIND_IN_SET(".auth()->id().",group_read_message))")->first();
                    if(!empty($unread_message))
                    {
                        $read_array = explode(',',$unread_message->group_read_message);
                        $read_array = array_filter($read_array);
                        array_push($read_array, auth()->id());

                        $unread_message->group_read_message = implode(',', array_unique($read_array));

                        $unread_message->save();
                    }
                }

                if($input['type'] == 'A')
                {
                    $unread_messages = Message::whereHas('user_conversations',function($query) use($input){
                                                    $query->where('chat_uuid', $input['chatting_id'])->whereHas('group_users',function ( $subquery ){
                                                        $subquery->where('user_id', auth()->id());
                                                    });
                                                })->whereRaw("( user_id != ".auth()->id()." AND  read_flag != 1 ) AND (group_read_message is null OR NOT FIND_IN_SET(".auth()->id().",group_read_message))")->get();

                    if($unread_messages->count() > 0)
                    {
                        foreach ($unread_messages as $unmessage) {
                            $read_array = explode(',',$unmessage->group_read_message);
                            $read_array = array_filter($read_array);
                            array_push($read_array, auth()->id());
                            $unmessage->group_read_message = implode(',', array_unique($read_array));
                            $unmessage->save();
                        }
                    }
                }
                $chatuser = Chat::withCount(['chat_messages' => function($query) use($input) {
                                        $query->whereRaw("( user_id != ".auth()->id()." AND  read_flag != 1 )")
                                            ->whereRaw("(group_read_message is null OR NOT FIND_IN_SET(".auth()->id().",group_read_message))");
                                    }])->where('chat_uuid', $input['chatting_id'])->whereHas('group_users',function ( $subquery ){
                                        $subquery->where('user_id', auth()->id());
                                    })->with(['one_users.user_details','two_users.user_details'])->orderBy('updated_at','DESC')->first();
                if(!empty($chatuser)){
                    $unread_message =   Message::whereHas('user_conversations',function($query){
                                        $query->whereHas('group_users',function ( $subquery ){
                                            $subquery->where('user_id', auth()->id());
                                        });
                                    })->whereRaw("( user_id != ".auth()->id()." AND  read_flag != 1 )")->count();

                    $this->WebApiArray['error'] = false;
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Record found.';
                    $this->WebApiArray['data']['result'] = $chatuser;
                    $this->WebApiArray['data']['unread_message_count'] = $unread_message;
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("No record found.", 1);
            }
            throw new Exception("Request not allowed.", 1);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }

    }

    public function unreadMessageCount(Request $request)
    {
        try {
            if($request->ajax()){
                if(!$request->isMethod('post'))
                {
                    throw new Exception("Request not allowed.", 1);
                }
                $unread_message =   Message::whereHas('user_conversations',function($query){
                                        $query->whereRaw( "((one_user_id = ".auth()->id()." OR two_user_id = ".auth()->id()."))");
                                    })->whereRaw("( user_id != ".auth()->id()." AND  read_flag != 1 )")
                                    ->whereRaw("(group_read_message is null OR NOT FIND_IN_SET(".auth()->id().",group_read_message))")->count();
                $this->WebApiArray['error'] = false;
                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Total Unread Message count';
                $this->WebApiArray['data']['result'] = $unread_message;
                return response()->json($this->WebApiArray);   
            }
            throw new Exception("Request not allowed.", 1);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }

    }

    public function sendUserMessage(Request $request)
    {
        DB::beginTransaction();
        try {
            if($request->ajax()){
                if(!$request->isMethod('post'))
                {
                    throw new Exception("Request not allowed.", 1);
                }
                $input = $request->all();
                $validator = Validator::make($input,[
                    'message_type' => 'required|in:I,V,N,MI,MV,D',
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
                $input['message_type'] == 'MV' ? 'required|max:65536' : ($input['message_type'] == 'I' ? 'required|image|mimes:jpeg,jpg,png|max:204800' : 'required');

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
                $checkchat = Chat::whereRaw("( (one_user_id = ".auth()->id()." OR  two_user_id = ".auth()->id().") AND chat_uuid = '".$input['chatting_id']."'  )")->first();

                if(empty($checkchat)){
                    throw new Exception("Chat not exist.", 1);
                }
                if($checkchat->one_users->deleted_status == 1 || $checkchat->two_users->deleted_status == 1)
                {
                    throw new Exception("This user account has been deleted.", 1);
                }
                $chat_data = array();
                if($input['message_type'] == 'I' || $input['message_type'] == 'V' || $input['message_type'] == 'D' )
                {
                    if ($input['message_type'] == 'I') {
                        $file_name = AwsBucketMessageVideoImageFileUpload($input['message_file'],'messages',$input['message_type']);
                        if(empty($file_name)){
                            throw new Exception("Error in file uploading.", 1);
                        }
                        $chat_data['message'] = $file_name['file_name'];
                        $chat_data['thumbnail'] = $file_name['file_name'];
                        $chat_data['message_type'] = 2;
                    } elseif ($input['message_type'] == 'D') {
                        $file_name = AwsDocumentToS3($input['message_file'],'messages',$input['message_type']);
                        if(empty($file_name)){
                            throw new Exception("Error in file uploading.", 1);
                        }
                        $chat_data['message'] = $file_name['file_name'];
                        $chat_data['thumbnail'] = $file_name['thumb_name'];
                        $chat_data['message_type'] = 12;
                    } else {
                        $file_name = AwsBucketMessageVideoMoveLocalToS3($input['message_file'],'messages',$input['message_type']);
                        if(empty($file_name['status'])){
                            throw new Exception($file_name['message'], 1);
                        }
                        //get image name
                        $image = explode(".",$input['message_file']);
                        $imagename = $image[0].'.jpg';
                        $chat_data['message'] = $input['message_file'];
                        $chat_data['thumbnail'] = $imagename;
                        $chat_data['message_type'] = 3;
                    }

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
                    $chat_message_data['user_id'] =  auth()->id();
                    $chat_message_data['message'] =  $chat_data['message'];
                    $chat_message_data['thumbnail'] =  $chat_data['thumbnail'];
                    $chat_message_data['message_type'] =  $chat_data['message_type'];
                    $chat_message_data['message_uuid'] =  Str::uuid()->toString();
                    if($createdmessage = $checkchat->chat_messages()->create($chat_message_data)){
                        self::activeChat($checkchat->chat_uuid);
                        DB::commit();
                        $reciever_id = $checkchat->one_user_id == auth()->id() ? $checkchat->two_user_id : $checkchat->one_user_id;

                        sendFcmNotification($reciever_id,'Asportcoach', 'You receive a new message', "messenger", auth()->id());
                        $sentmessage  = Message::whereHas('user_conversations',function($query) use($input){
                            $query->where('chat_uuid',$input['chatting_id']);
                        })->with(['user_conversations.one_users.user_details','user_conversations.two_users.user_details','senders','user_conversations.group_users'])->where('id',$createdmessage->id)->first();

                        if (isset($input['message_sent_uuid']) && !empty($input['message_sent_uuid'])) {
                            $sentmessage['message_sent_uuid'] = $input['message_sent_uuid'];
                        } else {
                            $sentmessage['message_sent_uuid'] = null;
                        }

                        $this->WebApiArray['error'] = false;
                        $this->WebApiArray['status'] = true;
                        $this->WebApiArray['message'] = 'Record found.';
                        $this->WebApiArray['data']['result'] = $sentmessage;
                        return response()->json($this->WebApiArray);
                    }
                    throw new Exception("Error Processing Request", 1);
                }
                throw new Exception("Error Processing Request", 1);
            }
            throw new Exception("Request not allowed.", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function sendGroupMessage(Request $request)
    {
        DB::beginTransaction();
        try {
            if($request->ajax()){
                if(!$request->isMethod('post'))
                {
                    throw new Exception("Request not allowed.", 1);
                }
                $input = $request->all();
                $validator = Validator::make($input,[
                    'message_type' => 'required|in:I,V,N,MI,MV,D',
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
                $input['message_type'] == 'MV' ? 'required|max:65536' : ($input['message_type'] == 'I' ? 'required|image|mimes:jpeg,jpg,png|max:204800' : 'required');

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
                $checkchat = Chat::where('chat_uuid', $input['chatting_id'])->whereHas('group_users',function ( $subquery ){
                    $subquery->where('user_id', auth()->id());
                })->first();

                if(empty($checkchat)){
                    throw new Exception("Chat not exist.", 1);
                }
                if($checkchat->one_users->deleted_status == 1 || $checkchat->two_users->deleted_status == 1)
                {
                    throw new Exception("This user account has been deleted.", 1);
                }
                $chat_data = array();
                if($input['message_type'] == 'I' || $input['message_type'] == 'V' || $input['message_type'] == 'D' )
                {
                    if ($input['message_type'] == 'I') {
                        $file_name = AwsBucketMessageVideoImageFileUpload($input['message_file'],'messages',$input['message_type']);
                        if(empty($file_name)){
                            throw new Exception("Error in file uploading.", 1);
                        }
                        $chat_data['message'] = $file_name['file_name'];
                        $chat_data['thumbnail'] = $file_name['file_name'];
                        $chat_data['message_type'] = 2;
                    }  elseif ($input['message_type'] == 'D') {
                        $file_name = AwsDocumentToS3($input['message_file'],'messages',$input['message_type']);
                        if(empty($file_name)){
                            throw new Exception("Error in file uploading.", 1);
                        }
                        $chat_data['message'] = $file_name['file_name'];
                        $chat_data['thumbnail'] = $file_name['thumb_name'];
                        $chat_data['message_type'] = 12;
                    } else {
                        $file_name = AwsBucketMessageVideoMoveLocalToS3($input['message_file'],'messages',$input['message_type']);
                        if(empty($file_name['status'])){
                            throw new Exception($file_name['message'], 1);
                        }
                        $image = explode(".",$input['message_file']);
                        $imagename = $image[0].'.jpg';
                        $chat_data['message'] = $input['message_file'];
                        $chat_data['thumbnail'] = $imagename;
                        $chat_data['message_type'] = 3;
                    }

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
                    $chat_message_data['user_id'] =  auth()->id();
                    $chat_message_data['message'] =  $chat_data['message'];
                    $chat_message_data['thumbnail'] =  $chat_data['thumbnail'];
                    $chat_message_data['message_type'] =  $chat_data['message_type'];
                    $chat_message_data['message_uuid'] =  Str::uuid()->toString();
                    if($createdmessage = $checkchat->chat_messages()->create($chat_message_data)){
                        self::activeChat($checkchat->chat_uuid);
                        DB::commit();
                        $sentmessage  = Message::whereHas('user_conversations',function($query) use($input){
                            $query->where('chat_uuid',$input['chatting_id']);
                        })->with(['user_conversations.one_users.user_details','user_conversations.two_users.user_details','senders','user_conversations.group_users'])->where('id',$createdmessage->id)->first();

                        if (isset($input['message_sent_uuid']) && !empty($input['message_sent_uuid'])) {
                            $sentmessage['message_sent_uuid'] = $input['message_sent_uuid'];
                        } else {
                            $sentmessage['message_sent_uuid'] = null;
                        }

                        $this->WebApiArray['error'] = false;
                        $this->WebApiArray['status'] = true;
                        $this->WebApiArray['message'] = 'Record found.';
                        $this->WebApiArray['data']['result'] = $sentmessage;
                        return response()->json($this->WebApiArray);
                    }
                    throw new Exception("Error Processing Request", 1);
                }
                throw new Exception("Error Processing Request", 1);
            }
            throw new Exception("Request not allowed.", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function fetchChatUserDetail(Request $request)
    {
        DB::beginTransaction();
        try {
            if($request->ajax()){
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


                $check_chat = Chat::where('chat_uuid', $input['chatting_id'])->first();

                if ($check_chat->chat_type == 1) {
                    $user_chat = Chat::with(['one_users','two_users','meeting'])
                        ->has('one_users')
                        ->has('two_users')->where('chat_uuid', $input['chatting_id'])
                        ->whereRaw("( ((one_user_id = ".auth()->id()." AND  two_user_id = ".$input['user_id'].") OR (one_user_id = ".$input['user_id']." AND  two_user_id = ".auth()->id().")))")
                        ->first();

                    $user_details = User::where('id',$input['user_id'])->first();
                } else {
                    $user_chat = Chat::with(['one_users','two_users','meeting'])
                        ->has('one_users')
                        ->has('two_users')->where('chat_uuid', $input['chatting_id'])
                        ->WhereHas('group_users',function ( $subquery ){
                            $subquery->where('user_id', auth()->id());
                        })->first();

                    $user_details = GroupUsers::with('users')->where(['chat_id' =>$user_chat->id, 'user_id' => auth()->id()])->first();
                }
                if(!empty($user_chat) && !empty($user_details) )
                {
                    $this->WebApiArray['error'] = false;
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Record found.';
                    $this->WebApiArray['data']['result']['user'] = $user_details;
                    $this->WebApiArray['data']['result']['chat'] = $user_chat;
                    return response()->json($this->WebApiArray); 
                }
                throw new Exception("Invalid user.", 1);
            }
            throw new Exception("Request not allowed.", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function storeSessionRequest(Request $request)
    {
        DB::beginTransaction();
        try {
            if($request->ajax()){
                if(!$request->isMethod('post'))
                {
                    throw new Exception("Request not allowed.", 1); 
                }
                $input = $request->all();
                $validator = Validator::make($input,[
                    'chatting_id'   =>  'required|exists:chats,chat_uuid',
                    'receiver_id'     => 'required|exists:users,id',
                    'sender_id'     => 'required|exists:users,id',
                ],
                [
                    'chatting_id.required' => 'The chat id is required.',
                    'chatting_id.exists' => 'The chat id is invalid.',
                ]);
                if ($validator->fails()) {
                    throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
                }
                $sender = User::whereHas('roles',function($query){
                                    $query->where('role_id',3);
                                })->where('id',$input['sender_id'])->first();

                $receiver = User::whereHas('roles',function($query){
                                    $query->where('role_id',3);
                                })->where('id',$input['receiver_id'])->first();


                if(!empty($sender) && !empty($receiver) && auth()->id() == $input['sender_id'] &&  auth()->id() != $input['receiver_id'] )
                {
                	if($sender->deleted_status == 1 || $receiver->deleted_status == 1  )
                	{
                		throw new Exception("This user account has been deleted.", 1);
                	}
                    $checkchat = Chat::whereRaw("( (one_user_id = ".auth()->id()." OR  two_user_id = ".auth()->id().") AND (one_user_id = ".$input['receiver_id']." OR  two_user_id = ".$input['receiver_id'].") AND chat_uuid = '".$input['chatting_id']."'  )")->first();
                    if(empty($checkchat)){
                       throw new Exception("Chat not exist.", 1);
                    }
                    $checkActiveSession = SessionRequest::where(['coach_id' => $receiver->id, 'athelete_id' => $sender->id, 'chat_id' => $checkchat->id ])->whereRaw("(status = 1 OR status = 2 || status = 4 || status = 6 )")->first();
                    
                    if(!empty($checkActiveSession)){
                       throw new Exception("You have pending session with this coach.", 1); 
                    }

                    if($checkchat->fill(['message_type' => 4])->save()){
                        if($createdmessage = $checkchat->chat_messages()->create(['user_id' => auth()->id(), 'message_type' => 4, 'message_uuid' => Str::uuid()->toString() ])){
                            if($athelete_session_request = SessionRequest::create(['coach_id' => $receiver->id, 'athelete_id' => $sender->id, 'chat_session_uuid' => Str::uuid()->toString(), 'chat_id' => $checkchat->id ])){
                                if($notification = $athelete_session_request->notifications()->create(['type' => 1, 'from_user_id' => $input['sender_id'], 'to_user_id' => $input['receiver_id'], 'data' => 'You received new session request.', 'notification_uuid' => Str::uuid()->toString()])){
                                    self::activeChat($checkchat->chat_uuid);
                                    DB::commit();
                                    $sentmessage  = Message::whereHas('user_conversations',function($query) use($input){
                                                        $query->where('chat_uuid',$input['chatting_id']);
                                                    })->with(['user_conversations.one_users.user_details','user_conversations.two_users.user_details','senders','user_conversations.group_users'])->where('id',$createdmessage->id)->first();
                                    $sentrequest  = SessionRequest::where(['id' => $athelete_session_request->id, 'athelete_id' => auth()->id()])->first();
                                    $notification_data  = Notification::where('id',$notification->id)->first();
                                    self::sendFirebaseNotification($notification_data);
                                    sendFcmNotification($input['receiver_id'], 'Asportcoach', 'You received new session request.',"messenger", auth()->id());
                                    $this->WebApiArray['error'] = false;
                                    $this->WebApiArray['status'] = true;
                                    $this->WebApiArray['message'] = 'Session request sent successfully.';
                                    $this->WebApiArray['data']['result']['message'] = $sentmessage;
                                    $this->WebApiArray['data']['result']['session_request'] = $sentrequest;
                                    $this->WebApiArray['data']['result']['notification'] = $notification_data;
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
            }
            throw new Exception("Request not allowed.", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function fetchSessonRequest(Request $request)
    {
        try {
            if($request->ajax()){
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
                $user_requests  = (new SessionRequest())->newQuery();
                $user_requests  = $user_requests->whereHas('session_chats',function($query) use($input){
                                    $query->whereRaw("( (one_user_id = ".auth()->id()." OR  two_user_id = ".auth()->id().") AND chat_uuid = '".$input['chatting_id']."'  )");
                                })->whereRaw("(coach_id = ".auth()->id()." OR athelete_id = ".auth()->id()." )");

                $user_requests = $user_requests->get();
                $user_request_process_count = $user_requests->whereIn('status', [1,2,4,6])->count();

                $data = [
                    'result' => $user_requests,
                    'process_count' => $user_request_process_count
                ];

                if($user_requests->count() > 0){
                    $this->WebApiArray['error'] = false;
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Record found.';
                    $this->WebApiArray['data'] = $data;
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("No record found.", 1);
            }
            throw new Exception("Request not allowed.", 1);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }

    }

    public function updateSessonRequest(Request $request)
    {
        DB::beginTransaction();
        try {
            if($request->ajax()){
                if(!$request->isMethod('post'))
                {
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

                $checkdeletedChatUser = Chat::whereRaw("( (one_user_id = ".auth()->id()." OR  two_user_id = ".auth()->id().") AND chat_uuid = '".$input['chatting_id']."'  )")->first();
                if(empty($checkdeletedChatUser))
                {
                	throw new Exception("Invalid chat.", 1);
                }
                if($checkdeletedChatUser->one_users->deleted_status == 1 || $checkdeletedChatUser->two_users->deleted_status == 1)
                {
                	throw new Exception("This user account has been deleted.", 1);
                }

                if($input['request_status'] == 2 && $input['session_price'] <= 0)
                {
                    throw new Exception("Session price should be greater then 0.", 1);
                }

                if (isset($input['request_by']) && $input['request_by'] == 2) {
                    $chat_request = Chat::whereRaw("( (one_user_id = ".auth()->id()." OR  two_user_id = ".auth()->id().") AND chat_uuid = '".$input['chatting_id']."'  )")->first();

                    $checkActiveSession = SessionRequest::where(['chat_id' => $chat_request->id ])
                        ->whereRaw("(status = 1 OR status = 2 || status = 4 || status = 6 )")->first();

                    if(!empty($checkActiveSession)){
                        throw new Exception("You have pending session with this user.", 1);
                    }

                    $user_requests = new SessionRequest();

                    if ($chat_request->one_user_id == auth()->id()) {
                        $user_requests->athelete_id = $chat_request->two_user_id;
                    } else {
                        $user_requests->athelete_id = $chat_request->one_user_id;
                    }
                    $user_requests->coach_id = auth()->id();
                    $user_requests->chat_session_uuid = Str::uuid()->toString();
                    $user_requests->chat_id = $chat_request->id;
                    $user_requests->request_by = 2;
                } else {
                    $user_requests  = SessionRequest::whereHas('session_chats',function($query) use($input){
                        $query->whereRaw("( (one_user_id = ".auth()->id()." OR  two_user_id = ".auth()->id().") AND chat_uuid = '".$input['chatting_id']."'  )");
                    })->where(['coach_id' => auth()->id(), 'chat_session_uuid' =>  $input['session_request_id'] ])->first();
                }

                if(!empty($user_requests)){
                        if($input['request_status'] == 2){
                            $user_requests->session_price = $input['session_price'];
                        }
                        $user_requests->status = $input['request_status'];
                        if($user_requests->save())
                        {  
                            $message_type = $input['request_status'] == 2 ? 5 : 6;
                            $userchat = Chat::whereRaw("( (one_user_id = ".auth()->id()." OR  two_user_id = ".auth()->id().") AND chat_uuid = '".$input['chatting_id']."'  )")->first();
                            if($userchat->fill(['message_type' => $message_type ])->save()){
                                $chat_message_data = array();
                                $chat_message_data['user_id'] =  auth()->id();
                                $chat_message_data['message_type'] =  $message_type;
                                $chat_message_data['message_uuid'] =  Str::uuid()->toString();
                                if($createdmessage = $userchat->chat_messages()->create($chat_message_data)){

                                    $notification_message =  $input['request_status'] == 2 ? 'Your session request has been accepted.' : 'Your session request has been declined.';
                                    if ($user_requests->request_by == 2) {
                                        $notification_message = 'Coach requested for session';
                                    }
                                    if($notification = $user_requests->notifications()->create(['type' => 1, 'from_user_id' =>auth()->id(), 'to_user_id' => $user_requests->athelete_id, 'data' => $notification_message, 'notification_uuid' => Str::uuid()->toString()])){
                                        self::activeChat($userchat->chat_uuid);
                                        DB::commit();
                                        $sentmessage  = Message::whereHas('user_conversations',function($query) use($input){
                                            $query->where('chat_uuid',$input['chatting_id']);
                                        })->with(['user_conversations.one_users.user_details','user_conversations.two_users.user_details','senders','user_conversations.group_users'])->where('id',$createdmessage->id)->first();
                                        $notification_data  = Notification::where('id',$notification->id)->first();
                                        $sentrequest  = SessionRequest::where('id', $user_requests->id)->first();
                                        self::sendFirebaseNotification($notification_data);
                                        sendFcmNotification($user_requests->athelete_id, 'Asportcoach', $notification_message,"messenger", auth()->id());
                                        $this->WebApiArray['error'] = false;
                                        $this->WebApiArray['status'] = true;
                                        $this->WebApiArray['message'] =  $input['request_status'] == 2 ? 'Session request accepted successfully.' : 'Session request declined successfully.';
                                        $this->WebApiArray['data']['result']['message'] = $sentmessage;
                                        $this->WebApiArray['data']['result']['session_request'] = $sentrequest;
                                        $this->WebApiArray['data']['result']['notification'] = $notification_data;
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
            throw new Exception("Request not allowed.", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function StartUserSession(Request $request)
    {
        DB::beginTransaction();
        try {
            if($request->ajax()){
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
                $user_requests  = SessionRequest::whereHas('session_chats',function($query) use($input){
                                    $query->whereRaw("( (one_user_id = ".auth()->id()." OR  two_user_id = ".auth()->id().") AND chat_uuid = '".$input['chatting_id']."'  )");
                                })->where(['coach_id' => auth()->id(), 'chat_session_uuid' =>  $input['session_request_id'] ])->first();
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
                            $userchat = Chat::whereRaw("( (one_user_id = ".auth()->id()." OR  two_user_id = ".auth()->id().") AND chat_uuid = '".$input['chatting_id']."'  )")->first();
                            if($userchat->fill(['message_type' => $message_type ])->save()){
                                $chat_message_data = array();
                                $chat_message_data['user_id'] =  auth()->id();
                                $chat_message_data['message_type'] =  $message_type;
                                $chat_message_data['message_uuid'] =  Str::uuid()->toString();
                                if($createdmessage = $userchat->chat_messages()->create($chat_message_data)){
                                    if($notification = $user_requests->notifications()->create(['type' => 1, 'from_user_id' =>auth()->id(), 'to_user_id' => $user_requests->athelete_id, 'data' => 'Your session has been started', 'notification_uuid' => Str::uuid()->toString()])){
                                        self::activeChat($userchat->chat_uuid);
                                        DB::commit();
                                        $sentmessage  = Message::whereHas('user_conversations',function($query) use($input){
                                            $query->where('chat_uuid',$input['chatting_id']);
                                        })->with(['user_conversations.one_users.user_details','user_conversations.two_users.user_details','senders','user_conversations.group_users'])->where('id',$createdmessage->id)->first();
                                        $notification_data  = Notification::where('id',$notification->id)->first();
                                        $sentrequest  = SessionRequest::where('id', $user_requests->id)->first();
                                        self::sendFirebaseNotification($notification_data);
                                        sendFcmNotification($user_requests->athelete_id, 'Asportcoach', 'Your session has been started',"messenger", auth()->id());
                                        $this->WebApiArray['error'] = false;
                                        $this->WebApiArray['status'] = true;
                                        $this->WebApiArray['message'] =  'Session started successfully.';
                                        $this->WebApiArray['data']['result']['message'] = $sentmessage;
                                        $this->WebApiArray['data']['result']['session_request'] = $sentrequest;
                                        $this->WebApiArray['data']['result']['notification'] = $notification_data;
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
            }
            throw new Exception("Request not allowed.", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function CompletedUserSession(Request $request)
    {
        DB::beginTransaction();
        try {
            if($request->ajax()){
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
                $user_requests  = SessionRequest::whereHas('session_chats',function($query) use($input){
                                    $query->whereRaw("( (one_user_id = ".auth()->id()." OR  two_user_id = ".auth()->id().") AND chat_uuid = '".$input['chatting_id']."'  )");
                                })->where(['chat_session_uuid' =>  $input['session_request_id'] ])->whereRaw("( coach_id = ".auth()->id()." OR  athelete_id = ".auth()->id()." )")->first();
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
                            $userchat = Chat::whereRaw("( (one_user_id = ".auth()->id()." OR  two_user_id = ".auth()->id().") AND chat_uuid = '".$input['chatting_id']."'  )")->first();
                            if($userchat->fill(['message_type' => $message_type ])->save()){
                                $chat_message_data = array();
                                $chat_message_data['user_id'] =  auth()->id();
                                $chat_message_data['message_type'] =  $message_type;
                                $chat_message_data['message_uuid'] =  Str::uuid()->toString();
                                if($createdmessage = $userchat->chat_messages()->create($chat_message_data)){

                                    $to_user_id = $user_requests->athelete_id == auth()->id() ? $user_requests->coach_id : $user_requests->athelete_id;
                                    if($notification = $user_requests->notifications()->create(['type' => 1, 'from_user_id' =>auth()->id(), 'to_user_id' => $to_user_id, 'data' => 'Your session has been completed', 'notification_uuid' => Str::uuid()->toString()])){
                                        self::activeChat($userchat->chat_uuid);
                                        DB::commit();
                                        $sentmessage  = Message::whereHas('user_conversations',function($query) use($input){
                                            $query->where('chat_uuid',$input['chatting_id']);
                                        })->with(['user_conversations.one_users.user_details','user_conversations.two_users.user_details','senders','user_conversations.group_users'])->where('id',$createdmessage->id)->first();
                                        $notification_data  = Notification::where('id',$notification->id)->first();
                                        $sentrequest  = SessionRequest::where('id', $user_requests->id)->first();
                                        self::sendFirebaseNotification($notification_data);
                                        sendFcmNotification($to_user_id, 'Asportcoach', 'Your session has been completed',"messenger", auth()->id());

                                        $this->WebApiArray['error'] = false;
                                        $this->WebApiArray['status'] = true;
                                        $this->WebApiArray['message'] = 'Session completed successfully.';
                                        $this->WebApiArray['data']['result']['message'] = $sentmessage;
                                        $this->WebApiArray['data']['result']['session_request'] = $sentrequest;
                                        $this->WebApiArray['data']['result']['notification'] = $notification_data;
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
            }
            throw new Exception("Request not allowed.", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function mediaArchive(Request $request)
    {
        try {
            if($request->ajax()){
                $video = Video::where('user_id', auth()->id())->where('file_type', 1)->whereNull('user_folder_id')->orderBy('updated_at','DESC')->get();
                $photo = Video::where('user_id', auth()->id())->where('file_type', 2)->whereNull('user_folder_id')->orderBy('updated_at','DESC')->get();

                $videoFolder = UserFolder::whereNotNull('user_folder_id')->where('user_id', auth()->id())->where('folder_type', 1)->orderBy('updated_at','DESC')->get();
                $photoFolder = UserFolder::whereNotNull('user_folder_id')->where('user_id', auth()->id())->where('folder_type', 2)->orderBy('updated_at','DESC')->get();

                if($videoFolder->count() > 0 || $photoFolder->count() > 0 || $video->count() > 0 || $photo->count() > 0){
                    $this->WebApiArray['error'] = false;
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Record found.';
                    $this->WebApiArray['data']['result']['photo'] = $photo;
                    $this->WebApiArray['data']['result']['video'] = $video;
                    $this->WebApiArray['data']['result']['photo_folder'] = $photoFolder;
                    $this->WebApiArray['data']['result']['video_folder'] = $videoFolder;
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("No record found.", 1);
            }
            throw new Exception("Request not allowed.", 1);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }

    }

    public function folderMediaArchive(Request $request)
    {
        try {
            if($request->ajax()){

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

                $media = Video::where('user_id', auth()->id())->where(['user_folder_id' => $input['folder_id']])->orderBy('updated_at','DESC')->get();

                if($media->count() > 0 || $media->count() > 0){
                    $this->WebApiArray['error'] = false;
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Record found.';
                    $this->WebApiArray['data']['result'] = $media;
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("No record found.", 1);
            }
            throw new Exception("Request not allowed.", 1);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }

    }

    public function fetchGroupInfo(Request $request)
    {
        try {
            if($request->ajax()){

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

                $users = Chat::with('active_group_users.users')->where(['chat_uuid'=> $input['chatting_id'],'chat_type'=>'2'])->first();

                $check_admin = Chat::where('chat_uuid', $input['chatting_id'])->WhereHas('group_users',function ( $adminqry ){
                    $adminqry->where('user_id', auth()->id())->where('admin', 1);
                })->first();
                $admin = 0;
                if(!empty($check_admin)) {
                    $admin = 1;
                }

                if($users->group_users->count() > 0){
                    $this->WebApiArray['error'] = false;
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Record found.';
                    $this->WebApiArray['data']['result']['group_user'] = $users;
                    $this->WebApiArray['data']['result']['admin'] = $admin;
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("No record found.", 1);
            }
            throw new Exception("Request not allowed.", 1);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }

    }

    public function fetchNewUser(Request $request)
    {
        try {
            if($request->ajax()){

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
                $userChats = Chat::where('chat_type',1)->with('one_users', 'two_users')->where('chat_type',1)
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
                    $filter_user = $userChat->one_users->id != auth()->id() ? $userChat->one_users : $userChat->two_users;
                    $check_user = GroupUsers::where(['user_id' => $filter_user->id, 'chat_id' => $users->id, 'status' => 1])->first();
                    if (empty($check_user)) {
                        array_push($newusers,$userChat->one_users->id != auth()->id() ? $userChat->one_users : $userChat->two_users);
                    }
                }

                if($users->group_users->count() > 0){
                    $this->WebApiArray['error'] = false;
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Record found.';
                    $this->WebApiArray['data']['result']['new_user'] = $newusers;
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("No record found.", 1);
            }
            throw new Exception("Request not allowed.", 1);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
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

    public function sendFirebase($data, $db)
    {
        $serviceAccount = ServiceAccount::fromJsonFile(public_path(env('FIREBASE_CREDENTIALS')));
        $firebase = (new Factory)
            ->withServiceAccount($serviceAccount)
            ->withDatabaseUri(env('FIREBASE_DATABASE'))
            ->create();
        $database = $firebase->getDatabase();
        $user_notification = $database->getReference($db)->push($data);
        return true;
    }

    public function activeChat($chat_id)
    {
        $chat = Chat::where('chat_uuid', $chat_id)->first();
        $chat->delete_chat = null;
        $chat->save();
        return true;
    }

    public function showCoachSession(Request $request)
    {
        $input = $request->all();
        $sessions = (new SessionRequest)->newQuery();
        $sessions = $sessions->whereRaw("(coach_id = ".auth()->id()." OR athelete_id = ".auth()->id().")")
                            ->orderBy('updated_at','DESC')
                            ->paginate(10);
        return view('Coach.session.index',compact('sessions'))
            ->with('i', ($request->input('page', 1) - 1) * 10);
    } 

    public function RejectedUserSession(Request $request)
    {
        DB::beginTransaction();
        try {
            if($request->ajax()){
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
                $user_requests  = SessionRequest::whereHas('session_chats',function($query) use($input){
                                    $query->whereRaw("( (one_user_id = ".auth()->id()." OR  two_user_id = ".auth()->id().") AND chat_uuid = '".$input['chatting_id']."'  )");
                                })->where(['athelete_id' => auth()->id(), 'chat_session_uuid' =>  $input['session_request_id'] ])->first();
                if(!empty($user_requests)){

                    if($user_requests->athelete_user->deleted_status == 1 || $user_requests->coach_user->deleted_status == 1)
                    {
                        throw new Exception("This user account has been deleted.", 1);
                    }

                    if($user_requests->status == 2){
                        if($user_requests->fill(['status' => 5])->save()){
                            $message_type = 8;
                            $userchat = Chat::whereRaw("( (one_user_id = ".auth()->id()." OR  two_user_id = ".auth()->id().") AND chat_uuid = '".$input['chatting_id']."'  )")->first();
                            if($userchat->fill(['message_type' => $message_type ])->save()){
                                $chat_message_data = array();
                                $chat_message_data['user_id'] =  auth()->id();
                                $chat_message_data['message_type'] =  $message_type;
                                $chat_message_data['message_uuid'] =  Str::uuid()->toString();
                                if($createdmessage = $userchat->chat_messages()->create($chat_message_data)){

                                    if($notification = $user_requests->notifications()->create(['type' => 1, 'from_user_id' => auth()->id(), 'to_user_id' => $user_requests->coach_id, 'data' => 'Your session price has been declined.', 'notification_uuid' => Str::uuid()->toString()])){
                                        self::activeChat($userchat->chat_uuid);
                                        DB::commit();
                                        $sentmessage  = Message::whereHas('user_conversations',function($query) use($input){
                                            $query->where('chat_uuid',$input['chatting_id']);
                                        })->with(['user_conversations.one_users.user_details','user_conversations.two_users.user_details','senders','user_conversations.group_users'])->where('id',$createdmessage->id)->first();
                                        $notification_data  = Notification::where('id',$notification->id)->first();
                                        $sentrequest  = SessionRequest::where('id',$user_requests->id)->first();
                                        self::sendFirebaseNotification($notification_data);
                                        sendFcmNotification($user_requests->coach_id, 'Asportcoach', 'Your session price has been declined.',"messenger", auth()->id());
                                        $this->WebApiArray['error'] = false;
                                        $this->WebApiArray['status'] = true;
                                        $this->WebApiArray['message'] = 'Session price request declined successfully.';
                                        $this->WebApiArray['data']['result']['message'] = $sentmessage;
                                        $this->WebApiArray['data']['result']['session_request'] = $sentrequest;
                                        $this->WebApiArray['data']['result']['notification'] = $notification_data;
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
            }
            throw new Exception("Request not allowed.", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function fetchUserCards(Request $request)
    {
        try {
            if($request->ajax()){

                $secret = config('staging_live_config.STRIPE.STRIPE_SECRET');
                if (!empty(auth()->user()->stripe_id)) {
                    $stripe = Stripe::make($secret);
                    $stripe->setApiKey($secret);

                    $cards = $stripe->cards()->all(auth()->user()->stripe_id);

                    if(count($cards['data']) > 0){
                        $this->WebApiArray['error'] = false;
                        $this->WebApiArray['status'] = true;
                        $this->WebApiArray['message'] = 'Record found.';
                        $this->WebApiArray['data']['result'] = $cards['data'];
                        return response()->json($this->WebApiArray);
                    }
                    throw new Exception("No record found.", 1);
                }
                throw new Exception("No card found.", 1);
            }
            throw new Exception("Request not allowed.", 1);
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

    public function deleteMessage(Request $request)
    {
        DB::beginTransaction();
        try {
            if($request->ajax()){
                if(!$request->isMethod('post'))
                {
                    throw new Exception("Request not allowed.", 1);
                }
                $input = $request->all();
                $validator = Validator::make($input,[
                    'delete_type'   =>  'required',
                    'message_user'   =>  'required',
                    'message_id'     => 'required|exists:messages,id',
                ]);
                if ($validator->fails()) {
                    throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
                }

                $message = Message::where('id', $input['message_id'])->first();
                $delete = [];


                if ($input['delete_type'] == 'E') {
                    if ($message->user_id != auth()->id()) {
                        throw new Exception("Un-authorized.", 1);
                    }
                    $delete['delete_everyone'] = 1;
                } else {
                    if (isset($input['chat_type']) && $input['chat_type'] == 'G') {
                        $delete_array = explode(',',$message->group_delete_message);
                        $delete_array = array_filter($delete_array);
                        array_push($delete_array, auth()->id());
                        $delete['group_delete_message'] = implode(',', array_unique($delete_array));

                    } else {
                        if ($input['message_user'] == 'S') {
                            if ($message->user_id != auth()->id()) {
                                throw new Exception("Un-authorized.", 1);
                            }
                            $delete['delete_one'] = auth()->id();
                        } else {
                            $delete['delete_two'] = auth()->id();
                        }
                    }
                    $delete_media_array = explode(',',$message->delete_media);
                    $delete_media_array = array_filter($delete_media_array);
                    array_push($delete_media_array, auth()->id());
                    $delete['delete_media'] = implode(',', array_unique($delete_media_array));
                }

                if($message->fill($delete)->save()){
                    DB::commit();
                    $sentmessage  = Message::whereHas('user_conversations',function($query) use($input){
                        $query->where('chat_uuid',$input['chatting_id']);
                    })->with(['user_conversations.one_users.user_details','user_conversations.two_users.user_details','senders','user_conversations.group_users'])->where('id',$input['message_id'])->first();
                    $sentmessage['deleted_message_flag'] = 1;
                    $this->WebApiArray['error'] = false;
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Message Deleted Successfully.';
                    $this->WebApiArray['data']['result'] = $sentmessage;
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("Error Processing Request", 1);
            }
            throw new Exception("Request not allowed.", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function mediaFolderPopup(Request $request)
    {
        DB::beginTransaction();
        try {
            if($request->ajax()){
                if(!$request->isMethod('post'))
                {
                    throw new Exception("Request not allowed.", 1);
                }
                $input = $request->all();
                $validator = Validator::make($input,[
                    'message_id'     => 'required|exists:messages,id',
                ]);
                if ($validator->fails()) {
                    throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
                }

                $message = Message::where('id', $input['message_id'])->first();
                $file_id = $input['message_id'];
                if ($message->message_type == 2) {
                    $type = 2;
                } else {
                    $type = 1;
                }

                $folders = UserFolder::whereNotNull('user_folder_id')->where(['user_id' => auth()->id(), 'folder_type' => $type])->orderBy('title','ASC')->get();

                $html = view('Coach.chat.move_media',compact('folders', 'file_id'))->render();
                $this->WebApiArray['status'] = true;
                $this->WebApiArray['error'] = false;
                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data']['result'] = $html;
                return response()->json($this->WebApiArray);
            }
            throw new Exception("Request not allowed.", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function moveFileToFolder(Request $request)
    {
        DB::beginTransaction();
        try {
            if($request->ajax()){
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
                $parent_directories = self::createParentFolder();
                if(empty($parent_directories['status'])){
                    throw new Exception("Error occured! Directories could not be created.", 1);
                }
                $parent_folder = $parent_directories['folder_name'];



                $media = Message::where('id', $input['file_id'])->first();

                if ($media->message_type == 2) {
                    $type = 2;
                    $file_type = 'I';
                    $folder = 'images';
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
                    $input['user_id'] = auth()->id();
                    if($video->fill($input)->save()){
                        if(!empty($input['video_tag']) && count($input['video_tag']) > 0)
                        {
                            $video->video_tags()->sync($input['video_tag']);
                        }
                        DB::commit();
                        $this->WebApiArray['status'] = true;
                        $this->WebApiArray['error'] = false;
                        $this->WebApiArray['message'] = 'Saved Successfully.';
                        return response()->json($this->WebApiArray);
                    }
                    throw new Exception("Error Processing Request", 1);
                }
                throw new Exception("Error Processing Request", 1);


            }
            throw new Exception("Request not allowed.", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function createParentFolder()
    {
        $parent_dir = array('status' => false);
        try {
            $parent_folder = UserFolder::whereNull('user_folder_id')->where('user_id',auth()->id())->first();
            if(empty($parent_folder))
            {
                $user_slug = auth()->user()->username.'_'.auth()->user()->user_uuid;
                $parent_folder = new UserFolder;
                if($parent_folder->fill(['title' => $user_slug, 'user_id' => auth()->id(), 'slug' => $user_slug])->save()){
                    $parent_directory = AwsBucketCreateUserDirectory($parent_folder->slug);
                    if(empty($parent_directory))
                    {
                        throw new Exception("Error occured! Please try again.", 1);
                    }
                }else{
                    throw new Exception("Error occured! Please try again.", 1);
                }
            }
            $parent_dir = array('status' => true, 'folder_name' => $parent_folder->slug);
            return $parent_dir;
        } catch (Exception $e) {
            return $parent_dir;
        }
    }

    public function deleteMedia(Request $request, $media_id) {
        DB::beginTransaction();
        try {
            if($request->ajax()){
                if(!empty($media_id) && is_numeric($media_id))
                {
                    $message = Message::where('id', $media_id)->first();
                    if(!empty($message)) {
                        if ($message->user_id == auth()->id()) {
                            $message->delete_one = auth()->id();
                        } else {
                            $message->delete_two = auth()->id();
                        }

                        $delete_media_array = explode(',',$message->delete_media);
                        $delete_media_array = array_filter($delete_media_array);
                        array_push($delete_media_array, auth()->id());
                        $message->delete_media = implode(',', array_unique($delete_media_array));

                        $message->save();
                        DB::commit();
                        $this->WebApiArray['status'] = true;
                        $this->WebApiArray['error'] = false;
                        $this->WebApiArray['message'] = 'Media Deleted successfully.';
                        return response()->json($this->WebApiArray);

                    }
                    throw new Exception("Media does not exists", 1);
                }
                throw new Exception("Media does not exists.", 1);
            }
            throw new Exception("HTTP request not allow", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function createGroup(Request $request)
    {
        DB::beginTransaction();
        try {
            if($request->ajax()) {
                if (!$request->isMethod('post')) {
                    throw new Exception("Request not allowed.", 1);
                }
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

                        if (in_array(auth()->id(), $input['member'])) {
                            throw new Exception('You can not add yourself as member', 1);
                        }

                        $userChats = Chat::has('chat_messages')
                            ->where(function ($queryone) {
                                $queryone->where('one_user_id', auth()->id())->orWhere('two_user_id', auth()->id());
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

                        if (in_array(auth()->id(), $team_users)) {
                            unset($team_users[array_search(auth()->id(), $team_users)]);
                        }
                        $filtered_teams = array_values($team_users);
                        $input['member'] = $filtered_teams;
                    }

                    if (isset($input['member']) && count($input['member']) > 0) {
                        $chat = new Chat();
                        $chat_data['chat_uuid'] = Str::uuid()->toString();
                        $chat_data['one_user_id'] = auth()->id();
                        $chat_data['two_user_id'] = auth()->id();
                        $chat_data['chat_type'] = 2;
                        $chat_data['group_name'] = $input['title'];

                        if ($chat->fill($chat_data)->save()) {

                            $group_user_admin = new GroupUsers();
                            $group_user_admin->chat_id = $chat->id;
                            $group_user_admin->user_id = auth()->id();
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
                        }
                    }
                }
                if (!empty($chat)) {
                    DB::commit();

                    $chatuser = Chat::where('chat_uuid', $chat->chat_uuid)->with(['one_users.user_details','two_users.user_details','group_users'])->first();

                    $this->WebApiArray['error'] = false;
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Group Created Successfully.';
                    $this->WebApiArray['data']['result'] = $chatuser;
                    self::sendFirebase($chatuser, 'chat_users');
                    return response()->json($this->WebApiArray);
                }
            }
            throw new Exception("Request not allowed.", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function addParticipant(Request $request)
    {
        DB::beginTransaction();
        try {
            if ($request->ajax()) {
                if (!$request->isMethod('post')) {
                    throw new Exception("Request not allowed.", 1);
                }
                $input = $request->all();
                $validator = Validator::make($input, [
                    'member' => 'required|array',
                    'member.*' => 'required|exists:users,id,status,1,confirmed,1,deleted_status,0',
                ]);
                if ($validator->fails()) {
                    throw new Exception(implode('<br/>', $validator->errors()->all()), 1);
                }

                $chat = Chat::where(['chat_uuid' => $input['chatting_id'], 'chat_type' => 2])->WhereHas('group_users',function ( $subquery ){
                    $subquery->where(['user_id' => auth()->id(), 'admin' => 1]);
                })->first();

                if (empty($chat)) {
                    throw new Exception('Chat not exist', 1);
                }
                if (isset($input['member']) && count($input['member']) > 0) {

                    if (in_array(auth()->id(), $input['member'])) {
                        throw new Exception('You can not add yourself as member', 1);
                    }

                    $userChats = Chat::has('chat_messages')
                        ->where(function ($queryone) {
                            $queryone->where('one_user_id', auth()->id())->orWhere('two_user_id', auth()->id());
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

                    if (in_array(auth()->id(), $team_users)) {
                        unset($team_users[array_search(auth()->id(), $team_users)]);
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
                        self::sendFirebase($chatuser, 'chat_users');

                    } else {
                        $group_user_check->status = 1;
                        $group_user_check->save();
                        self::sendFirebase($group_user_check, 'group_chat');
                    }
                }
            }
            if (!empty($chat)) {
                DB::commit();

                $this->WebApiArray['error'] = false;
                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Participant Added Successfully.';
                $this->WebApiArray['data']['result'] = $chat;
                return response()->json($this->WebApiArray);
            }
            throw new Exception("Request not allowed.", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function removeFromGroup(Request $request)
    {
        DB::beginTransaction();
        try {
            if ($request->ajax()) {
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

                $chat = Chat::where(['chat_uuid' => $input['chatting_id'], 'chat_type' => 2])->WhereHas('group_users',function ( $subquery ){
                    $subquery->where(['user_id' => auth()->id(), 'admin' => 1]);
                })->first();

                if (empty($chat)) {
                    throw new Exception('Chat not exist', 1);
                }

                $group_user = GroupUsers::where(['chat_id'=> $chat->id, 'user_id' => $input['user_id']])->first();
                if (!empty($group_user)) {
                    $group_user->status = 2;
                    $group_user->save();

                    DB::commit();
                    $this->WebApiArray['error'] = false;
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Participant Removed Successfully.';
                    $this->WebApiArray['data']['result'] = $group_user;
                    self::sendFirebase($group_user, 'group_chat');
                    return response()->json($this->WebApiArray);
                }
                throw new Exception('User not exist in this group', 1);
            }
            throw new Exception("Request not allowed.", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function exitGroup(Request $request)
    {
        DB::beginTransaction();
        try {
            if ($request->ajax()) {
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

                $chat = Chat::where(['chat_uuid' => $input['chatting_id'], 'chat_type' => 2])->WhereHas('group_users',function ( $subquery ){
                    $subquery->where(['user_id' => auth()->id()]);
                })->first();

                if (empty($chat)) {
                    throw new Exception('Chat not exist', 1);
                }

                $group_user = GroupUsers::where(['chat_id'=> $chat->id, 'user_id' => auth()->id()])->first();
                if (!empty($group_user)) {
                    $group_user->status = 3;
                    $group_user->save();

                    DB::commit();
                    $this->WebApiArray['error'] = false;
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Exit Group Successfully.';
                    $this->WebApiArray['data']['result'] = $group_user;
                    return response()->json($this->WebApiArray);
                }
                throw new Exception('User not exist in this group', 1);
            }
            throw new Exception("Request not allowed.", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function changeGroupName(Request $request)
    {
        DB::beginTransaction();
        try {
            if ($request->ajax()) {
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

                $chat = Chat::where(['chat_uuid' => $input['chatting_id'], 'chat_type' => 2])->WhereHas('group_users',function ( $subquery ){
                    $subquery->where(['user_id' => auth()->id(), 'admin' => 1]);
                })->with('group_users')->first();

                if (empty($chat)) {
                    throw new Exception('Chat not exist', 1);
                }

                $chat->group_name = $input['name'];
                $chat->save();

                DB::commit();
                $this->WebApiArray['error'] = false;
                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Group Name Changed Successfully.';
                $this->WebApiArray['data']['result'] = $chat;
                self::sendFirebase($chat, 'group_name');
                return response()->json($this->WebApiArray);
            }
            throw new Exception("Request not allowed.", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function chatDelete(Request $request)
    {
        DB::beginTransaction();
        try {
            if($request->ajax()){
                if(!$request->isMethod('post'))
                {
                    throw new Exception("Request not allowed.", 1);
                }
                $input = $request->all();
                $validator = Validator::make($input,[
                    'media_delete'   =>  'required|in:0,1',
                    'chatting_id'   =>  'required|exists:chats,chat_uuid',
                ]);

                if ($validator->fails()) {
                    throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
                }

                $check_chat = Chat::where('chat_uuid', $input['chatting_id'])->first();

                if (!empty($check_chat)) {
                    $messages = Message::where('chat_id', $check_chat['id'])->get();
                    if ($check_chat['chat_type'] == 2) {
                        foreach ($messages as $message) {
                            $delete_array = explode(',',$message->group_delete_message);
                            $delete_array = array_filter($delete_array);
                            array_push($delete_array, auth()->id());
                            $message->group_delete_message = implode(',', array_unique($delete_array));
                            if ($input['media_delete'] == 1) {
                                $delete_media_array = explode(',',$message->delete_media);
                                $delete_media_array = array_filter($delete_media_array);
                                array_push($delete_media_array, auth()->id());
                                $message->delete_media = implode(',', array_unique($delete_media_array));
                            }
                            $message->save();
                        }
                    } else {
                        foreach ($messages as $message) {
                            if ($message->user_id == auth()->id()) {
                                $message->delete_one = auth()->id();
                            } else {
                                $message->delete_two = auth()->id();
                            }
                            if ($input['media_delete'] == 1) {
                                $delete_media_array = explode(',',$message->delete_media);
                                $delete_media_array = array_filter($delete_media_array);
                                array_push($delete_media_array, auth()->id());
                                $message->delete_media = implode(',', array_unique($delete_media_array));
                            }
                            $message->save();
                        }
                    }

                    $delete_chat_array = explode(',',$check_chat->delete_chat);
                    $delete_chat_array = array_filter($delete_chat_array);
                    array_push($delete_chat_array, auth()->id());
                    $delete_chat['delete_chat'] = implode(',', array_unique($delete_chat_array));

                    if ( $check_chat->fill($delete_chat)->save()) {
                        DB::commit();
                        $this->WebApiArray['error'] = false;
                        $this->WebApiArray['status'] = true;
                        $this->WebApiArray['message'] = 'Message Deleted Successfully.';
                        $this->WebApiArray['data']['result'] = $check_chat;
                        return response()->json($this->WebApiArray);
                    }
                    throw new Exception("Error occured! Please try again.", 1);
                }
                throw new Exception("Error occured! Please try again.", 1);
            }
            throw new Exception("Request not allowed.", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function joinMeeting(Request $request, $chat_id)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            $chat = Chat::where('chat_uuid', $chat_id)->first();
            $url = url('/coach/join_meeting/'.$chat_id);
            if (!isset($_GET['code']) || empty($_GET['code'])) {
                $url = 'https://zoom.us/oauth/authorize?response_type=code&client_id='.env('ZOOM_CLIENT_ID').'&redirect_uri='.$url;
                return redirect()->away($url);
            }

            $meeting = ChatMeeting::where('chat_id', $chat->id)->first();
            if (empty($meeting)) {
                $tokenData = getAccessToken($_GET['code'], $url);
                $token = json_decode($tokenData)->access_token;

                $title = 'Video';
                $password = str_random(6);
                $duration = 1;
                $schedule = scheduledMeeting($duration, $password, '2020-10-01T12:02:00Z', $title, $title, $token);
                $schedule = json_decode($schedule);
                if (!isset($schedule->id) || empty($schedule->id)) {
                    throw new Exception("Error in meeting create",1);
                }

                $meeting = new ChatMeeting();
                $meeting->chat_id = $chat->id;
                $meeting->meeting_id = (string)$schedule->id;
                $meeting->meeting_password = $password;
                $meeting->save();
            }

            if ($meeting->status == 0) {
                $meeting->status = 1;
                $meeting->user_id = auth()->id();
            }

            if (empty($meeting->attendants)){

                $chat->message_type = 11;
                $chat->save();

                if($createdmessage = $chat->chat_messages()->create(['user_id' => auth()->id(), 'message_type' => 11, 'message_uuid' => Str::uuid()->toString() ])) {
                    $reciever_id = $chat->one_user_id == auth()->id() ? $chat->two_user_id : $chat->one_user_id;

                    $message = auth()->user()->name.' has started Zoom meeting';
                    sendFcmNotification($reciever_id, 'Asportcoach', $message, "messenger", auth()->id());

                    $sentmessage = Message::whereHas('user_conversations', function ($query) use ($chat) {
                        $query->where('chat_uuid', $chat->chat_uuid);
                    })->with(['user_conversations.one_users.user_details', 'user_conversations.two_users.user_details', 'senders', 'user_conversations.group_users'])->where('id', $createdmessage->id)->first();

                    self::sendFirebase($sentmessage, 'chats');
                }
            }

            $attendant_array = explode(',',$meeting->attendants);
            $attendant_array = array_filter($attendant_array);
            array_push($attendant_array, auth()->id());
            $meeting->attendants = implode(',', array_unique($attendant_array));
            $meeting->save();
            DB::commit();

            $api_key = env('ZOOM_API_KEY');
            $api_secret = env('ZOOM_API_SECRET');
            $input['name'] = auth()->user()->name;
            $input['mn'] = $meeting->meeting_id;
            $input['pwd'] = $meeting->meeting_password;
            $input['role'] = '0';
            $input['email'] = '';
            $input['lang'] = 'en-US';
            $input['china'] = '0';
            $signature = generateSignature($api_key, $api_secret, $meeting->meeting_id, $input['role']);
            $input['signature'] = $signature;

            return view('Coach.chat.meeting_start', compact('input'));
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    public function leaveMeeting(Request $request, $chat_id)
    {
        DB::beginTransaction();
        try {
            $chat = Chat::where('chat_uuid', $chat_id)->first();
            $meeting = ChatMeeting::where('chat_id', $chat->id)->first();
            if (empty($meeting)) {
                throw new Exception('Meeting not available', 1);
            }

            $attendant_array = explode(',',$meeting->attendants);
            $attendant_array = array_filter($attendant_array);
            $key = array_search(auth()->id(), $attendant_array);
            unset($attendant_array[$key]);
            $meeting->attendants = implode(',', array_unique($attendant_array));
            if (count($attendant_array) == 0) {
                $meeting->status = 0;
                $meeting->user_id = null;
                $meeting->attendants = null;
            }
            $meeting->save();
            DB::commit();

            session()->put('athelete_chat',$chat->chat_uuid);
            return redirect()->route('coach.chat.index');
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors($e->getMessage());
        }
    }
}
