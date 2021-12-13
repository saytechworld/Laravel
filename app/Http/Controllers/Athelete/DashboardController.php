<?php

namespace App\Http\Controllers\Athelete;

use App\Models\Chat;
use App\Models\Event;
use App\Models\EventAttendant;
use App\Models\EventCategory;
use App\Models\EventColor;
use App\Models\Message;
use App\Models\Notification;
use App\Models\SessionRequest;
use App\Models\Skill;
use App\Models\Team;
use App\Models\UserFolder;
use App\Models\Video;
use App\Models\Zipcode;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB,Exception,Hash,Validator;
use  App\Http\Requests\Athelete\ChangePasswordRequest;
use  App\Http\Requests\Athelete\UpdateProfileRequest;
use  App\Http\Requests\Athelete\UpdateGameRequest;
use App\Models\User;
use App\Models\UserDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Kreait\Firebase;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

class DashboardController extends Controller
{
    public function index()
    {
        $earning = SessionRequest::where('athelete_id', auth()->id())->where('status', 7)->sum('session_price');
        $total_session = SessionRequest::where('athelete_id', auth()->id())->count();

        $users = array();
        $userChats = Chat::where('chat_type', 1)->where('one_user_id', auth()->id())->orWhere('two_user_id', auth()->id())
            ->with('one_users', 'two_users')
            ->whereHas('one_users',function($query){
                $query->where(['status' => 1, 'confirmed' => 1 ]);
            })
            ->whereHas('two_users',function($query){
                $query->where(['status' => 1, 'confirmed' => 1 ]);
            })->get();
        foreach ($userChats as $userChatKey => $userChat) {
            $users[$userChat->one_users->id != auth()->id() ? $userChat->one_users->id : $userChat->two_users->id] = $userChat->one_users->id != auth()->id() ? $userChat->one_users->email : $userChat->two_users->email;
        }

        $event_colors = EventColor::all();

        $yesterday_date = Carbon::now()->subDays(1)->format('Y-m-d');
        $events = Event::whereHas('event_attendants',function($query){
            $query->where('user_id',auth()->id());
        })->whereDate('event_datetime', '>',$yesterday_date)
            ->orderBy('event_datetime','ASC')
            ->limit(2)->get();

        $videos = Video::where('user_id', auth()->id())->where('file_type', 1)->orderBy('created_at', 'DESC')->limit(4)->get();
        $photos = Video::where('user_id', auth()->id())->where('file_type', 2)->orderBy('created_at', 'DESC')->limit(4)->get();

        $chats = (new Chat)->newQuery();
        $chats = $chats->withCount(['chat_messages' => function($query){
            $query->whereRaw("( user_id != ".auth()->id()." AND  read_flag != 1 )");
        }])->where(function ($query1) {
            $query1->whereRaw("(one_user_id = ".auth()->id()." OR two_user_id = ".auth()->id()." )");
            $query1->orWhereHas('group_users',function ( $subquery ){
                $subquery->where('user_id', auth()->id());
            });
        })->whereRaw("(delete_chat is null OR NOT FIND_IN_SET(".auth()->id().",delete_chat))")
            ->orderBy('updated_at','DESC')->limit(3)
            ->get();

        $sessions = (new SessionRequest())->newQuery();
        $sessions = $sessions->where('athelete_id', auth()->id())
            ->whereIn('status', [1,2,4,6])
            ->orderBy('updated_at','DESC')
            ->limit(3)
            ->get();

    	return view('Athelete.dashboard', compact('earning','total_session', 'event_colors', 'events', 'videos', 'photos', 'users', 'chats', 'sessions'));
    }

    public function fetchProfile()
    {
        $loginuser = auth()->user();
        return view('Athelete.profile',compact('loginuser'));
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        DB::beginTransaction();
        try {
            $input = $request->except(['username','user_image']);
            $input['privacy'] = isset($input['privacy']) ? $input['privacy'] : 1;

            $loginuser = User::find(auth()->id());
            if($loginuser->fill($input)->save()){
                $checkUniqueMobile = UserDetail::where(function ($query) use($input) {
                    $query->where(['mobile' => $input['user_details']['mobile'],  'mobile_code_id' => $input['user_details']['mobile_code_id'] ])->where('user_id','!=',auth()->id());
                })->first();
                if(!empty($checkUniqueMobile))
                {
                    throw new Exception("The mobile has already been taken.", 1);
                }

                /*$zip_code = Zipcode::where(['country_id' =>$input['user_details']['country_id'], 'zip_code' => $input['zip_code'] ]);
                if (!empty($input['user_details']['state_id'])) {
                    $zip_code->where(['state_id' => $input['user_details']['state_id']]);
                }

                if (!empty($input['user_details']['city_id'])) {
                    $zip_code->where(['city_id' => $input['user_details']['city_id']]);
                }
                $zip = $zip_code->first();
                if (empty($zip)) {
                    throw new Exception("The Zip code is not valid.", 1);
                }

                $input['user_details']['zipcode_id'] = $zip->id;*/

                if(!empty($input['user_details'])){
                    $input['user_details']['dob'] = Carbon::parse($input['user_details']['dob'])->format('Y-m-d');
                    $user_details = UserDetail::firstOrCreate(['user_id'=>$loginuser->id]);
                    $user_details->fill($input['user_details'])->save();
                }
                DB::commit();
                return redirect()->back()->withSuccess('Profile updated successfully.');
            }
            throw new Exception("Error Processing Request", 1);
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())->withError($e->getMessage());
        }
    }


    public function updateUserImage(Request $request)
    {
        DB::beginTransaction();
        try {
            if($request->ajax()){
                if(!$request->isMethod('post'))
                {
                    throw new Exception("Request not allowed.", 1);
                }
                $input = $request->all();
                
                $validator = Validator::make($request->all(), [
                                'image' => 'required',
                            ]);
                if ($validator->fails()) {
                    throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
                }
                $image_name = AwsBucketBase64iImageUpload($input['image'], 'users');
                if(!empty($image_name)){
                    $loginuser = User::find(auth()->id());
                    $user_details = UserDetail::firstOrCreate(['user_id'=>$loginuser->id]);
                    if($user_details->fill(['image' => $image_name ])->save()){
                        DB::commit();
                        $this->WebApiArray['error'] = false;
                        $this->WebApiArray['status'] = true;
                        $this->WebApiArray['message'] = 'Image updated successfully.';
                        $this->WebApiArray['data']['result'] = $user_details;
                        return response()->json($this->WebApiArray);
                    }
                    throw new Exception("Error occured while trying to upload image.", 1);
                } 
                throw new Exception("Error occured while trying to upload image.", 1);
            }
            throw new Exception("HTTP Request not allowed.", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }


    public function changepassword()
    {
        $loginuser = auth()->user();
        return view('Athelete.profile',compact('loginuser'));
    }


    public function updatePassword(ChangePasswordRequest $request)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            $loginuser = auth()->user();
            if( ! Hash::check($input['current_password'] , $loginuser->password  ) )
            {
                throw new Exception("Current password doesn't match with our database system.", 1);
            }
            if($loginuser->fill(['password' => bcrypt($input['password']) ])->save()){
                DB::commit();
                return redirect()->back()->withSuccess('Password updated successfully.');
            }
            throw new Exception("Error Processing Request", 1);
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())->withError($e->getMessage());
        }
    }


    public function editGameSkill()
    {
        $loginuser = auth()->user();
        return view('Athelete.profile',compact('loginuser'));
    }

    public function updateGameSkill(UpdateGameRequest $request)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            if ($input['user_experience'] > 99) {
                throw new Exception('User experience should be less then 99');
            }
            $loginuser = auth()->user();
            $loginuser->coach_games_skills()->detach();
            foreach ($input['game_skill'] as $game_skill_key => $game_skill_value) {
                foreach ($game_skill_value['skill_id'] as $skill_key => $skill_value) {
                    $skill = Skill::where(['game_id' => $game_skill_value['game_id'], 'id' => $skill_value])->first();
                    if(empty($skill)){
                        throw new Exception("Either game or skill didn't match with database.", 1);
                    }
                    $loginuser->coach_games_skills()->attach($skill->id, ['game_id' => $game_skill_value['game_id']]);
                }
            }
            $loginuser->user_spoken_languages()->sync($input['select_languages']);
            $user_details = UserDetail::firstOrCreate(['user_id'=>$loginuser->id]);
            if($user_details->fill(['experience' => $input['user_experience']])->save()){
                DB::commit();
                return redirect()->back()->withSuccess('Experience and skills updated successfully.');
            }
            throw new Exception("Error processing request.", 1);
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())->withError($e->getMessage());
        }
    }

    public function deleteUserProfile(Request $request)
    {   
        DB::beginTransaction();
        try {
            if($request->ajax()){
                if(!$request->isMethod('post'))
                {
                    throw new Exception("Request not allowed.", 1);
                }
                $loggedInUser = User::where('id', auth()->id())->first();
                if($loggedInUser->fill(['deleted_status' => 1])->save()){
                    Event::where(['user_id' => auth()->id()])->delete();
                    EventAttendant::where(['user_id' => auth()->id(), 'attendant_type' => 'A'])->delete();
                    Team::where(['user_id' => auth()->id()])->delete();
                    EventCategory::where(['user_id' => auth()->id()])->delete();
                    $loggedInUser->user_teams()->detach();
                    Notification::whereIn('type',[2,3])->where(function($query){
                        $query->where('from_user_id',auth()->id());
                        $query->orWhere('to_user_id',auth()->id());
                    })->delete();
                    $folder = UserFolder::where('user_id', auth()->id())->whereNull('user_folder_id')->first();
                    $folderSlug = '';
                    if (!empty($folder)) {
                        $folderSlug =$folder->slug;
                        $folder->delete();
                        Video::where('user_id', auth()->id())->delete();
                    }

                    DB::commit();
                    if (!empty($folderSlug)) {
                        AwsDeleteDirectory($folderSlug);
                    }

                    $user_references =  collect(['user_uuid' => $loggedInUser->user_uuid, 'deleted_status' => $loggedInUser->deleted_status ]);
                    self::sendFirebaseNotification($user_references);
                    $this->WebApiArray['error'] = false;
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Profile deleted successfully.';
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("Error occurred while deleting Profile! Please try again.", 1);
            }
            throw new Exception("Request not allowed.", 1);
        }catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function sendFirebaseNotification($user_references)
    {
        $serviceAccount = ServiceAccount::fromJsonFile(public_path(env('FIREBASE_CREDENTIALS')));
        $firebase = (new Factory)
            ->withServiceAccount($serviceAccount)
            ->withDatabaseUri(env('FIREBASE_DATABASE'))
            ->create();
        $database = $firebase->getDatabase();
        $database->getReference('user_references')->push($user_references);
        return true;
    }

}
