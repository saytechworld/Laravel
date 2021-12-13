<?php

namespace App\Services\Api\v2;

use App\Models\AndroidVersionController;
use App\Models\Chat;
use App\Models\City;
use App\Models\Country;
use App\Models\Event;
use App\Models\EventAttendant;
use App\Models\EventCategory;
use App\Models\Game;
use App\Models\IosVersionController;
use App\Models\Language;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Order;
use App\Models\SessionRequest;
use App\Models\Skill;
use App\Models\State;
use App\Models\Tag;
use App\Models\Team;
use App\Models\UserDetail;
use App\Models\UserFolder;
use App\Models\UserToken;
use App\Models\Video;
use App\Notifications\ResetPasswordNotification;
use Cartalyst\Stripe\Api\Orders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception, Validator, File, Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Notifications\UserConfirmation;
use Illuminate\Auth\Events\Registered;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

trait UserTrait
{
    public function Login(Request $request)
    {
        try {
                if(!$request->isMethod('post'))
                {
                    throw new Exception("Request method not allowed.", 1);
                }
                $input = $request->all();
                $validator = Validator::make($input,[
                    'email'   =>  'required|email|max:150',
                    'password' => 'required|max:150',
                    'device_type' => 'required|in:I,A',
                    'device_token' => 'required'
                ]);
                if ($validator->fails()) {
                    throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
                }
                $credentials = $request->only('email', 'password');

                if (! $token = JWTAuth::attempt($credentials)) {
                    throw new Exception("We can`t find an account with this credentials.", 401);
                }
                $user = JWTAuth::user();
                if ($user->hasRoles('admin') || $user->hasRoles('subadmin')) {
                    throw new Exception("You are not authorized to access app.", 1);
                }
                if (!$user->IsActive()) {
                    throw new Exception("Account has been disabled. Contact our team.", 1);
                }
                if (!$user->IsDeletedStatus()) {
                    throw new Exception("Your account has been deleted. Please contact admin if you wish to reactivate your account at " . env('MAIL_USERNAME') . " ", 1);
                }
                if (!$user->IsConfirmed()) {
                    $user->notify(new UserConfirmation($user['confirmation_code']));
                    throw new Exception("Your account is not confirmed. A verification link has been sent to your email account (please check the inbox as well as the spam folder).", 1);
                }


                $user_token = UserToken::where(['user_id' => $user->id, 'device_type' => $input['device_type'], 'device_token' => $input['device_token']])->first();

                if (empty($user_token)) {
                    $tokens = new UserToken();
                    $tokens->user_id = $user->id;
                    $tokens->device_type = $input['device_type'];
                    $tokens->device_token = $input['device_token'];
                    $tokens->save();
                }

                $user->makeHidden(['username', 'user_uuid','product_tour','privacy','created_at','updated_at','deleted_status','total_balance','remaining_balance','roles','user_details']);

                $user->profile_link = asset('coachlist/'. $user->username);

                // All good so return the token
                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'User Logged In.';
                $this->WebApiArray['data'] = $user;
                $this->WebApiArray['data']['token'] = $token;
                return response()->json($this->WebApiArray);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }catch (JWTException $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function Logout(Request $request)
    {
        DB::beginTransaction();
        try {
                if(!$request->isMethod('post'))
                {
                    throw new Exception("Request method not allowed.", 1);
                }
                $input = $request->all();
                $validator = Validator::make($input,[
                    'device_type' => 'required|in:I,A',
                    'device_token' => 'required'
                ]);
                if ($validator->fails()) {
                    throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
                }

                $user = $this->getAuthenticatedUser();

                UserToken::where(['user_id' => $user->id, 'device_type' => $input['device_type'], 'device_token' => $input['device_token']])->delete();

                auth()->guard('api')->logout();

                DB::commit();
                
                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'User Logged Out.';
                return response()->json($this->WebApiArray);
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function Register(Request $request)
    {
        DB::beginTransaction();
        try {
                if(!$request->isMethod('post'))
                {
                    throw new Exception("Request method not allowed.", 1);
                }
                $input = $request->all();
                $validator = Validator::make($input,[
                    'role'   => 'required|in:3,4',
                    'email'   =>  'required|email|max:150|unique:users,email',
                    'name'  => 'required|max:150',
                    'password' => 'required|min:6|max:12',
                ]);
                if ($validator->fails()) {
                    throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
                }
                $user = new User;
                $input['status'] = 1;
                $input['password'] = bcrypt($input['password']);
                $input['user_uuid'] = Str::uuid()->toString();
                $input['confirmation_code'] = str_random(40);
                if($user->fill($input)->save()){
                    $user->roles()->attach($input['role']);
                    $user->user_details()->firstOrCreate(['user_id' => $user->id]);
                    $user->notify(new UserConfirmation($input['confirmation_code']));
                    DB::commit();
                    event(new Registered($user));
                    $user = User::with(['user_details'])->selectRaw("id,name,username,email,user_uuid,status,confirmed,privacy,deleted_status")->whereId($user->id)->first();
                    $user->makeVisible(["email", "status", "confirmed"]);

                    
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = "A verification link has been sent to your email account (please check the inbox as well as the spam folder).";
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("Error occured while user registered! Please try again.", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function forgotPassword(Request $request)
    {
        DB::beginTransaction();
        try {
                if(!$request->isMethod('post'))
                {
                    throw new Exception("Request method not allowed.", 1);
                }
                $input = $request->all();
                $validator = Validator::make($input,[
                    'email'   =>  'required|email|max:150|exists:users,email',
                ]);
                if ($validator->fails()) {
                    throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
                }

                $user = User::where('email', $input['email'])->first();

                if ($user->hasRoles('admin') || $user->hasRoles('subadmin')) {
                    throw new Exception("You are not authorized to access this panel.", 1);
                }
                if (!$user->IsActive()) {
                    throw new Exception("Account has been disabled. Contact our team.", 1);
                }
                if (!$user->IsDeletedStatus()) {
                    throw new Exception("Your account has been deleted. Please contact admin if you wish to reactivate your account at " . env('MAIL_USERNAME') . " ", 1);
                }
                if (!$user->IsConfirmed()) {
                    $user->notify(new UserConfirmation($user['confirmation_code']));
                    throw new Exception("Your account is not confirmed. A verification link has been sent to your email account (please check the inbox as well as the spam folder).", 1);
                }

            //$user->sendPasswordResetNotification();
            return true;
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function getCountry() {
        try {

            $countries = Country::selectRaw('id,title,phone_code')->where('status', 1)->get();
            
            $this->WebApiArray['status'] = true;
            if ($countries->count() > 0) {
                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data'] = $countries;
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

    public function getState(Request $request) {
        try {

            $input = $request->all();

            $validator = Validator::make($input,[
                'country_id' => 'required|numeric|exists:countries,id',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $states = State::selectRaw('id,title')->where(['status' => 1, 'country_id'=> $input['country_id']])->get();

            
            $this->WebApiArray['status'] = true;
            if ($states->count() > 0) {
                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data'] = $states;
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

    public function getCity(Request $request) {
        try {

            $input = $request->all();

            $validator = Validator::make($input,[
                'state_id' => 'required|numeric|exists:states,id',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $cities = City::selectRaw('id,title')->where(['status' => 1, 'state_id'=> $input['state_id']])->get();

            
            $this->WebApiArray['status'] = true;

            if ($cities->count() > 0) {
                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data'] = $cities;
                $this->WebApiArray['statusCode'] = 0;
            }else {
                $this->WebApiArray['message'] = 'Record not found.';
                $this->WebApiArray['statusCode'] = 1;
            }
            return response()->json($this->WebApiArray);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function getSports() {
        try {

            $sports = Game::selectRaw('id,title')->where('status', 1)->get();

            
            $this->WebApiArray['status'] = true;

            if ($sports->count() > 0) {
                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data'] = $sports;
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

    public function getSkills(Request $request) {
        try {

            $input = $request->all();

            $validator = Validator::make($input,[
                'sport_id' => 'required|numeric|exists:games,id',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $skills = Skill::selectRaw('id,title')->where(['status' => 1, 'game_id'=> $input['sport_id']])->get();

            
            $this->WebApiArray['status'] = true;
            if ($skills->count() > 0) {
                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data'] = $skills;
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

    public function getSportSkills() {
        try {
            $sports = Game::selectRaw('id,title')->
                with(['game_skills' => function ($query) {
                    $query->where('status', 1);
                    $query->selectRaw('id,game_id,title');
                }])->where('status', 1)->get();

            $this->WebApiArray['status'] = true;

            if ($sports->count() > 0) {
                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data'] = $sports;
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

    public function getLanguage() {
        try {

            $language = Language::selectRaw('id,title')->where('status', 1)->get();

            
            $this->WebApiArray['status'] = true;
            if ($language->count() > 0) {
                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data'] = $language;
                $this->WebApiArray['statusCode'] = 0;
            } else{
                $this->WebApiArray['message'] = 'Record not found.';
                $this->WebApiArray['statusCode'] = 1;
            }
            return response()->json($this->WebApiArray);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function getTag() {
        try {

            $tags = Tag::selectRaw('id,title')->where('status', 1)->get();

            
            $this->WebApiArray['status'] = true;
            if ($tags->count() > 0) {
                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data'] = $tags;
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

    public function getUserSkill()
    {
        try {
            $user = $this->getAuthenticatedUser();

            $user->coach_games_skills->map(function ($skill) {
                $skill->makeHidden(['slug','status','created_at','updated_at','pivot']);
            });

            $user->user_spoken_languages->map(function ($language) {
                $language->makeHidden(['slug','status','created_at','updated_at','pivot','lang_code','short_code']);
            });
            $user->coach_games->map(function ($games) {
                $games->makeHidden(['slug','status','created_at','updated_at','pivot']);
            });
            $games = $user->coach_games->unique();

            $data = [
              'experience' => $user->user_details->experience,
              'games' => $games,
              'skill' => $user->coach_games_skills,
              'language' => $user->user_spoken_languages,
            ];

            
            $this->WebApiArray['status'] = true;
            $this->WebApiArray['data'] = $data;
            $this->WebApiArray['message'] = 'Skill Data.';
            return response()->json($this->WebApiArray);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function getAthleteSkill() {
        try {
            $user = $this->getAuthenticatedUser();

            $user->athelete_games->makeHidden(['slug','status','created_at','updated_at','pivot']);
            $user->user_spoken_languages->makeHidden(['slug','status','lang_code','short_code','created_at','updated_at','pivot']);


            $data = [
              'game' => $user->athelete_games,
              'language' => $user->user_spoken_languages
            ];

            $this->WebApiArray['status'] = true;
            $this->WebApiArray['data'] = $data;
            $this->WebApiArray['message'] = 'record found';
            return response()->json($this->WebApiArray);

        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function getProfile()
    {
        try {
            $user = $this->getAuthenticatedUser();

            $user->makeVisible('email');
            $user->makeHidden(['user_uuid','product_tour','created_at','updated_at','deleted_status','notification_setting','total_balance','remaining_balance','roles']);
            $user->user_details->makeHidden(['id','user_id','image','zipcode_id','experience','created_at','updated_at','user_profile_image']);
            if ($user->user_details->country) {
                $user->user_details->country->makeHidden(['slug','phone_code','ISO_code','currency_code','stripe_enabled','created_at','updated_at','status']);
            }
            if ($user->user_details->state) {
                $user->user_details->state->makeHidden(['slug','country_id','created_at','updated_at','status']);
            }
            if ($user->user_details->city) {
                $user->user_details->city->makeHidden(['slug','country_id','state_id','created_at','updated_at','status']);
            }
            if ($user->user_details->mobile_code) {
                $user->user_details->mobile_code->makeHidden(['slug','title','ISO_code','currency_code','stripe_enabled','created_at','updated_at','status']);
            }

            if ($user->user_details->dob) {
                $user->user_details->dob = Carbon::parse($user->user_details->dob)->format('m/d/Y');
            }

            $user->profile_link = asset('coachlist/'. $user->username);

            $this->WebApiArray['data'] = $user;
            $this->WebApiArray['status'] = true;
            $this->WebApiArray['message'] = 'Profile Data.';
            return response()->json($this->WebApiArray);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function updateProfile(Request $request)
    {
        DB::beginTransaction();
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request method not allowed.", 1);
            }
            $input = $request->all();

            $user = $this->getAuthenticatedUser();

            $validator = Validator::make($input,[
                'name' => 'required|max:50',
                'email' => 'required|email|max:50|unique:users,email,'.$user->id,
                'gender' => 'required|in:M,F',
                'dob' => 'required|date_format:m/d/Y',
                'country_id' => 'required|exists:countries,id',
                'state_id' => 'nullable|exists:states,id,country_id,'.$request->get('country_id'),
                'city_id' => 'nullable|exists:cities,id,state_id,'.$request->get('state_id'),
                'mobile_code_id' => 'required|exists:countries,id',
                'mobile' => 'required|regex:/^\d{0,20}$/',
                'about' => 'required|max:500',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            if($user->fill(['name' => $input['name'], 'email' => $input['email']])->save()){
                $checkUniqueMobile = UserDetail::where(function ($query) use($input, $user) {
                    $query->where(['mobile' => $input['mobile'],  'mobile_code_id' => $input['mobile_code_id'] ])->where('user_id','!=',$user->id);
                })->first();
                if(!empty($checkUniqueMobile))
                {
                    throw new Exception("The mobile has already been taken.", 1);
                }
                $detail['dob'] = Carbon::parse($input['dob'])->format('Y-m-d');
                $detail['gender'] = $input['gender'];
                $detail['country_id'] = $input['country_id'];
                $detail['state_id'] = $input['state_id'];
                $detail['city_id'] = $input['city_id'];
                $detail['address_line_1'] = $input['address_line_1'];
                $detail['address_line_2'] = $input['address_line_2'];
                $detail['mobile_code_id'] = $input['mobile_code_id'];
                $detail['mobile'] = $input['mobile'];
                $detail['about'] = $input['about'];

                $user_details = UserDetail::firstOrCreate(['user_id'=>$user->id]);
                $user_details->fill($detail)->save();
                DB::commit();
                $this->WebApiArray['data'] = $user;
                
                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Profile updated successfully.';
                return response()->json($this->WebApiArray);
            }
            throw new Exception("Error Processing Request", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function updateProfileSetting(Request $request)
    {
        DB::beginTransaction();
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request method not allowed.", 1);
            }
            $input = $request->all();

            $user = $this->getAuthenticatedUser();

            $validator = Validator::make($input,[
                'name' => 'required|max:50',
                'gender' => 'required|in:M,F',
                'dob' => 'required|date_format:m/d/Y',
                'about' => 'required|max:500',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            if($user->fill(['name' => $input['name']])->save()){
                $detail['dob'] = Carbon::parse($input['dob'])->format('Y-m-d');
                $detail['gender'] = $input['gender'];
                $detail['about'] = $input['about'];

                $user_details = UserDetail::firstOrCreate(['user_id'=>$user->id]);
                $user_details->fill($detail)->save();
                DB::commit();

                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Profile Setting updated successfully.';
                return response()->json($this->WebApiArray);
            }
            throw new Exception("Error Processing Request", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function updateAddressInfo(Request $request)
    {
        DB::beginTransaction();
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request method not allowed.", 1);
            }
            $input = $request->all();

            $user = $this->getAuthenticatedUser();

            $validator = Validator::make($input,[
                'country_id' => 'required|exists:countries,id',
                'state_id' => 'nullable|exists:states,id,country_id,'.$request->get('country_id'),
                'city_id' => 'nullable|exists:cities,id,state_id,'.$request->get('state_id'),
                'address_line_1' => 'nullable|max:500',
                'address_line_2' => 'nullable|max:500',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $detail['country_id'] = $input['country_id'];
            $detail['state_id'] = $input['state_id'];
            $detail['city_id'] = $input['city_id'];
            $detail['address_line_1'] = $input['address_line_1'];
            $detail['address_line_2'] = $input['address_line_2'];

            $user_details = UserDetail::firstOrCreate(['user_id'=>$user->id]);
            if($user_details->fill($detail)->save()){
                DB::commit();
                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Profile Address Info updated successfully.';
                return response()->json($this->WebApiArray);
            }
            throw new Exception("Error Processing Request", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function updateContactInfo(Request $request)
    {
        DB::beginTransaction();
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request method not allowed.", 1);
            }
            $input = $request->all();

            $user = $this->getAuthenticatedUser();

            $validator = Validator::make($input,[
                'email' => 'required|email|max:50|unique:users,email,'.$user->id,
                'mobile_code_id' => 'required|exists:countries,id',
                'mobile' => 'required|regex:/^\d{0,20}$/',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            if($user->fill(['email' => $input['email']])->save()){
                $checkUniqueMobile = UserDetail::where(function ($query) use($input, $user) {
                    $query->where(['mobile' => $input['mobile'],  'mobile_code_id' => $input['mobile_code_id'] ])->where('user_id','!=',$user->id);
                })->first();
                if(!empty($checkUniqueMobile))
                {
                    throw new Exception("The mobile has already been taken.", 1);
                }
                $detail['mobile_code_id'] = $input['mobile_code_id'];
                $detail['mobile'] = $input['mobile'];

                $user_details = UserDetail::firstOrCreate(['user_id'=>$user->id]);
                $user_details->fill($detail)->save();
                DB::commit();

                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Profile Contact Info Updated successfully.';
                return response()->json($this->WebApiArray);
            }
            throw new Exception("Error Processing Request", 1);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function updateCoachSkill(Request $request)
    {
        DB::beginTransaction();
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request method not allowed.", 1);
            }
            $user = $this->getAuthenticatedUser();
            $input = $request->all();

            $validator = Validator::make($input,[
                'user_experience'   => 'required|regex:/^[0-9]\d*(\d{1,2})?$/',
                'select_games' => 'required|array|min:1',
                'select_games.*' => 'required|exists:games,id',
                'game_skill' => 'required|array|min:1',
                'game_skill.*.game_id' => 'required|exists:games,id',
                'game_skill.*.skill_id' => 'required|array|min:1',
                'game_skill.*.skill_id.*' => 'required|exists:skills,id',
                'select_languages' => 'required|array|min:1',
                'select_languages.*' => 'required|exists:languages,id',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            if ($input['user_experience'] > 99) {
                throw new Exception('User experience should be less then 99');
            }

            $user->coach_games_skills()->detach();
            foreach ($input['game_skill'] as $game_skill_key => $game_skill_value) {
                foreach ($game_skill_value['skill_id'] as $skill_key => $skill_value) {
                    $skill = Skill::where(['game_id' => $game_skill_value['game_id'], 'id' => $skill_value])->first();
                    if(empty($skill)){
                        throw new Exception("Either game or skill didn't match with database.", 1);
                    }
                    $user->coach_games_skills()->attach($skill->id, ['game_id' => $game_skill_value['game_id']]);
                }
            }
            $user->user_spoken_languages()->sync($input['select_languages']);
            $user_details = UserDetail::firstOrCreate(['user_id'=>$user->id]);
            if($user_details->fill(['experience' => $input['user_experience']])->save()){
                DB::commit();
                
                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Experience and skills updated successfully.';
                return response()->json($this->WebApiArray);
            }
            throw new Exception("Error processing request.", 1);

        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function androidUpdateCoachSkill(Request $request)
    {
        DB::beginTransaction();
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request method not allowed.", 1);
            }
            $user = $this->getAuthenticatedUser();
            $input = $request->all();

            $validator = Validator::make($input,[
                'user_experience'   => 'required|regex:/^[0-9]\d*(\d{1,2})?$/',
                'game_id' => 'required|array|min:1',
                'game_id.*' => 'required|exists:games,id',
                'skill_id' => 'required|array|min:1',
                'skill_id.*' => 'required|exists:skills,id',
                'select_languages' => 'required|array|min:1',
                'select_languages.*' => 'required|exists:languages,id',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            if ($input['user_experience'] > 99) {
                throw new Exception('User experience should be less then 99');
            }

            $user->coach_games_skills()->detach();
            foreach ($input['game_id'] as $game_skill_key => $game_skill_value) {
                $skill = Skill::where(['game_id' => $game_skill_value, 'id' => $input['skill_id'][$game_skill_key]])->first();
                if(empty($skill)){
                    throw new Exception("Either game or skill didn't match with database.", 1);
                }
                $user->coach_games_skills()->attach($input['skill_id'][$game_skill_key], ['game_id' => $game_skill_value]);
            }

            $user->user_spoken_languages()->sync($input['select_languages']);
            $user_details = UserDetail::firstOrCreate(['user_id'=>$user->id]);
            if($user_details->fill(['experience' => $input['user_experience']])->save()){
                DB::commit();

                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Experience and skills updated successfully.';
                return response()->json($this->WebApiArray);
            }
            throw new Exception("Error processing request.", 1);

        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function updateAthleteSkill(Request $request)
    {
        DB::beginTransaction();
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request method not allowed.", 1);
            }

            $input = $request->all();

            $validator = Validator::make($input, [
                'select_games' => 'required|array|min:1',
                'select_games.*' => 'required|exists:games,id',
                'select_languages' => 'required|array|min:1',
                'select_languages.*' => 'required|exists:languages,id',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            $user->athelete_games()->sync($input['select_games']);
            $user->user_spoken_languages()->sync($input['select_languages']);
            DB::commit();

            $this->WebApiArray['status'] = true;
            $this->WebApiArray['message'] = 'Skill updated successfully';
            return response()->json($this->WebApiArray);

        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function deleteAccount(Request $request) {
        DB::beginTransaction();
        try {

            $user = $this->getAuthenticatedUser();
            if($user->fill(['deleted_status' => 1])->save()){
                Event::where(['user_id' => $user->id])->delete();
                EventAttendant::where(['user_id' => $user->id, 'attendant_type' => 'A'])->delete();
                Team::where(['user_id' => $user->id])->delete();
                EventCategory::where(['user_id' => $user->id])->delete();
                $user->user_teams()->detach();
                Notification::whereIn('type',[2,3])->where(function($query) use($user){
                    $query->where('from_user_id',$user->id);
                    $query->orWhere('to_user_id',$user->id);
                })->delete();
                $user_parent_folder = UserFolder::whereNull('user_folder_id')->where('user_id',$user->id)->first();
                $parent_folder_slug =  "";
                if(!empty($user_parent_folder))
                {
                    $parent_folder_slug = $user_parent_folder->slug;
                    $user_parent_folder->delete();
                    Video::where('user_id',$user->id)->delete();
                }
                DB::commit();
                if(!empty($parent_folder_slug))
                {
                    AwsDeleteDirectory($parent_folder_slug);
                }
                $user_references =  collect(['user_uuid' => $user->user_uuid, 'deleted_status' => $user->deleted_status ]);
                $this->sendFirebaseNotification($user_references, 'user_references');
                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Account deleted successfully.';
                return response()->json($this->WebApiArray);
            }
            throw new Exception("Error occurred while deleting Profile! Please try again.", 1);
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function updateProfilePrivacy(Request $request)
    {
        DB::beginTransaction();
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request method not allowed.", 1);
            }
            $user = $this->getAuthenticatedUser();
            $input = $request->all();
            $validator = Validator::make($input,[
                'privacy' => 'required|in:1,2',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $user->privacy = $input['privacy'];
            $user->save();
            DB::commit();

            
            $this->WebApiArray['status'] = true;
            $this->WebApiArray['message'] = 'Privacy updated successfully.';
            $this->WebApiArray['privacy'] = $input['privacy'];
            return response()->json($this->WebApiArray);
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function updateProfileImage(Request $request)
    {
        DB::beginTransaction();
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request method not allowed.", 1);
            }
            $user = $this->getAuthenticatedUser();
            $input = $request->all();
            $validator = Validator::make($input,[
                'image' => 'required|mimes:jpg,png,jpeg',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $image_name = AwsBucketBase64iImageUpload($input['image'], 'users');
            if(!empty($image_name)){
                $user_details = UserDetail::firstOrCreate(['user_id'=>$user->id]);
                if($user_details->fill(['image' => $image_name ])->save()){
                    DB::commit();
                    
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Image updated successfully.';
                    $this->WebApiArray['data'] = $user;
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("Error occured while trying to upload image.", 1);
            }
            throw new Exception("Error occured while trying to upload image.", 1);
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function homepageCoach()
    {
        try {
            $user = $this->getAuthenticatedUser();

            $earning = SessionRequest::where('coach_id', $user->id)->where('status', 7)->sum('session_price');

            $invest = SessionRequest::where('athelete_id', $user->id)->where('status', 7)->sum('session_price');

            $total_session = SessionRequest::whereRaw( "((coach_id = ".$user->id." OR athelete_id = ".$user->id."))")->count();

            $yesterday_date = Carbon::now()->subDays(1)->format('Y-m-d');

            $events = Event::selectRaw('id, title, description, color_code, event_datetime,end_datetime')
            ->whereHas('event_attendants',function($query) use ($user){
                $query->where('user_id',$user->id)->where('status','!=',2);
            })->whereDate('event_datetime', '>',$yesterday_date)
                ->orderBy('event_datetime','ASC')
                ->limit(2)->get();

            $events->map(function ($event) {
                $event->makeHidden(['event_created_date_time']);
            });

            $chats = (new Chat)->newQuery();
            $chats = $chats->withCount(['chat_messages' => function($query) use($user){
                $query->whereRaw("( user_id != ".$user->id." AND  read_flag != 1 )")
                    ->whereRaw("(group_read_message is null OR NOT FIND_IN_SET(".$user->id.",group_read_message))");
            }])->whereRaw("(one_user_id = ".$user->id." OR two_user_id = ".$user->id." )")
                ->orWhereHas('group_users',function ( $subquery ) use($user){
                    $subquery->where('user_id', $user->id);
                })
                ->with(['one_users:id,name,username','two_users:id,name,username', 'group_users'])
                ->orderBy('updated_at','DESC')->limit(3)
                ->get();


            $chats->each(function ($item , $key) use ($user) {
                if ($item->chat_type == 2) {
                    $item->group_users->each(function ($item1, $key) use($item, $user) {
                        if ($item1->user_id == $user->id && $item1->status != 1) {
                            $message_count = Message::where('chat_id', $item->id)->where('created_at', '<=', $item1->updated_at)
                                ->whereRaw("( user_id != ".$user->id." AND  read_flag != 1 ) && (group_read_message is null OR NOT FIND_IN_SET(".$user->id.",group_read_message))")->count();
                            $item->chat_messages_count = $message_count;
                        }
                    });
                }
            });

            $chats->map(function ($chat) {
                $chat->one_users->makeHidden(['remaining_balance','total_balance', 'user_details', 'roles']);
                $chat->two_users->makeHidden(['remaining_balance','total_balance', 'user_details', 'roles']);
            });

            $sessions = (new SessionRequest())->newQuery();
            $sessions = $sessions->selectRaw('id, athelete_id, coach_id')->whereRaw("(coach_id = ".$user->id." OR athelete_id = ".$user->id.")")
                ->with('athelete_user:id,name,user_uuid')
                ->with('coach_user:id,name,user_uuid')
                ->whereIn('status', [4,6])
                ->orderBy('updated_at','DESC')
                ->limit(3)
                ->get();

            $sessions->map(function ($session) {
                $session->makeHidden(['session_platform_fees', 'session_price_vat','total_session_price','parsing_session_price']);
                $session->athelete_user->makeHidden(['role_type', 'remaining_balance','total_balance', 'user_details', 'roles','user_image']);
                $session->coach_user->makeHidden(['role_type', 'remaining_balance','total_balance', 'user_details', 'roles','user_image']);
            });

            $login_user = auth()->user()->makeHidden(['username', 'user_uuid','product_tour','privacy','created_at','updated_at','deleted_status','total_balance','remaining_balance','roles','user_details']);

            $this->WebApiArray['status'] = true;
            $this->WebApiArray['message'] = 'Homepage Data';
            $this->WebApiArray['data']['earning'] = $earning;
            $this->WebApiArray['data']['invest'] = $invest;
            $this->WebApiArray['data']['total_session'] = $total_session;
            $this->WebApiArray['data']['events'] = $events;
            $this->WebApiArray['data']['chats'] = $chats;
            $this->WebApiArray['data']['sessions'] = $sessions;
            $this->WebApiArray['data']['user'] = $login_user;
            return response()->json($this->WebApiArray);

        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function subscriptionRestriction() {
       try {
           // Trial Period
           $CheckUserTrailPeriod = CheckUserTrailPeriod();
           $CheckUserSubscriptionPeriod = CheckUserSubscriptionPeriod();

           $message = '';
           $pending_days = '';
           if(!empty($CheckUserTrailPeriod['status'])) {
               $message = 'You have '.$CheckUserTrailPeriod['trail_days'].' days trail period remaining. To keep accessing all the features of Asportcoach please buy subscription plan';
               $pending_days = $CheckUserTrailPeriod['trail_days'];
           }

           if(!empty($CheckUserSubscriptionPeriod['status'])){
               $message = 'Your subscription is ending in '.$CheckUserSubscriptionPeriod['trail_days'].' days please renew your subscription plan';
               $pending_days = $CheckUserSubscriptionPeriod['trail_days'];
           }

           $trial_period = [
               'message' => $message,
               'trial_days' => $pending_days,
               'plan' => ''
           ];

           $trail_days = 14;
           $today_date = Carbon::now()->format('Y-m-d');
           $user_trail = User::whereRaw("( date(DATE_ADD(created_at, INTERVAL ".$trail_days." DAY)) < '".$today_date."' AND id = ".auth()->id()." )")->first();
           if(!empty($user_trail)){
               $checkBuyPlan =  Order::whereRaw("( user_id = ".auth()->id()." AND order_type != 1 AND status = 1 )")->whereDate('plan_end_date', '>', $today_date)->orderBy('id','DESC')->first();
               if(!empty($checkBuyPlan)){
                   if (empty($trial_period['message'])) {
                       $trial_period['access'] = 1;
                   } else {
                       $trial_period['access'] = 2;
                   }
                   $trial_period['plan'] = $checkBuyPlan->plan->title;
               } else{
                   $trial_period['access'] = 0;
               }
           }else{
               $trial_period['plan'] = 'Free';
               $trial_period['access'] = 2;
           }

           $this->WebApiArray['status'] = true;
           $this->WebApiArray['message'] = 'Subscription Restriction';
           $this->WebApiArray['data'] = $trial_period;
           return response()->json($this->WebApiArray);
       } catch (Exception $e) {
           DB::rollBack();
           $this->WebApiArray['message'] = $e->getMessage();
           return response()->json($this->WebApiArray);
       }
    }

    public function changePassword(Request $request) {
        DB::beginTransaction();
        try {
            if (!$request->isMethod('post'))
            {
                throw new Exception("Request method not allowed", 1);
            }
            $input = $request->all();
            $validator = Validator::make($input, [
                'current_password' => 'required|min:6|max:12',
                'password' =>'required|min:6|max:12|confirmed',
                'password_confirmation' => 'required|min:6|max:12',
            ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>', $validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            if (!Hash::check($input['current_password'], $user->password)) {
                throw new Exception("Current password doesn't match with our database system",1);
            }

            if ($user->fill(['password' => bcrypt($input['password'])])->save()) {
                DB::commit();
                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Password changed successfully';
                return response()->json($this->WebApiArray);
            }
            throw new Exception('Error processing request', 1);
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function userProfile(Request $request) {
        try {
            $input = $request->all();

            $validator = Validator::make($input,[
                'username'   => 'required|exists:users,username',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            $coach = User::where('id','!=',$user->id)
                ->where(['status' => 1, 'confirmed' => 1])
                ->where('deleted_status','!=',1)
                ->where('username', $input['username'])->first();


            if (!empty($coach)) {
                $coach->coach_games->map(function ($sport) {
                    $sport->makeHidden(['id','slug','status','created_at','updated_at','pivot']);
                });

                $coach->coach_games = $coach->coach_games->unique()->values()->all();

                $coach->user_spoken_languages->map(function ($language) {
                    $language->makeHidden(['id','slug','lang_code','short_code','status','created_at','updated_at','pivot']);
                });

                $coach->coach_games_skills->map(function ($skill) {
                    $skill->makeHidden(['id','game_id','slug','status','created_at','updated_at','pivot']);
                });

                $videos = (new Video)->newQuery();

                $videos->where(['user_id' => $coach->id, 'file_type' => 1]);

                $videos->whereNull('user_folder_id');

                $videos =  $videos->with('video_tags:id,title')->selectRaw('id ,title, description,privacy,user_id,file_name,thumbnail,file_type,user_folder_id')->latest()->limit(2)->get();

                $videos->map(function ($video) {
                    $video->makeHidden(['video_parent_folder','aws_video_uploaded_path','video_uploaded_path','video_folder_path','user_folders','user_id','file_name','thumbnail','file_type','user_folder_id']);
                    $video->video_tags->map(function ($video_tag) {
                        $video_tag->makeHidden('pivot');
                    });
                });

                $data = [
                    'id' =>   $coach->id,
                    'name' =>   $coach->name,
                    'user_uuid' =>   $coach->user_uuid,
                    'privacy' =>   $coach->privacy,
                    'image' =>   $coach->user_image,
                    'about' =>   $coach->user_details->about,
                    'address' => $coach->user_details->country ? $coach->user_details->country->title : '',
                    'experience' => $coach->user_details->experience,
                    'gender' => $coach->user_details->gender,
                    'sport' => $coach->coach_games,
                    'language' => $coach->user_spoken_languages,
                    'discipline' => $coach->coach_games_skills,
                    'videos' => $videos,
                ];

                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'User Profile.';
                $this->WebApiArray['data'] = $data;
                return response()->json($this->WebApiArray);
            }
            throw new Exception("User does not exist.", 1);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function androidVersionMaintain(Request $request)
    {
        DB::beginTransaction();
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request method not allowed.", 1);
            }
            $input = $request->all();
            $validator = Validator::make($input,[
                'version'   =>  'required',
            ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }
            $current_version = AndroidVersionController::where('version', $input['version'])->first();

            if (!empty($current_version)) {
                $mandatory_versions = AndroidVersionController::where('id', '>' ,$current_version->id)->where('status', 1)->count();
                if ($mandatory_versions > 0) {
                    $update = 1;
                    $message = 'Please update the new version';
                } else {
                    $update = 0;
                    $message = 'Not need to update';
                }

                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Versioning';
                $this->WebApiArray['data']['update'] = $update;
                $this->WebApiArray['data']['message'] = $message;
                return response()->json($this->WebApiArray);
            }
            throw new Exception('Current version not available on our server', 1);

        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function iosVersionMaintain(Request $request)
    {
        DB::beginTransaction();
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request method not allowed.", 1);
            }
            $input = $request->all();
            $validator = Validator::make($input,[
                'version'   =>  'required',
            ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }
            $current_version = IosVersionController::where('version', $input['version'])->first();

            if (!empty($current_version)) {
                $mandatory_versions = IosVersionController::where('id', '>' ,$current_version->id)->where('status', 1)->count();
                if ($mandatory_versions > 0) {
                    $update = 1;
                    $message = 'Please update the new version';
                } else {
                    $update = 0;
                    $message = 'Not need to update';
                }

                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Versioning';
                $this->WebApiArray['data']['update'] = $update;
                $this->WebApiArray['data']['message'] = $message;
                return response()->json($this->WebApiArray);
            }
            throw new Exception('Current version not available on our server', 1);

        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }
}
