<?php

namespace App\Http\Controllers\Backend;

use App\Http\Requests\Backend\ChangePasswordRequest;
use App\Http\Requests\Backend\UpdateProfileRequest;
use App\Models\EventCategory;
use App\Models\UserDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use DB,Exception;
use Illuminate\Support\Facades\Hash;
use App\Models\EventColor;
use App\Models\Event;


class DashboardController extends Controller
{

    public function updateNonCategoryEventColor(Request $request)
    {
        $events = Event::whereNull('category_id')->get();
        $color = EventColor::where('id',2)->first();

        foreach ($events as $key => $value) {
            $value->event_color_id = $color->id;
            $value->color_code = $color->color_code;
            $value->save();
        }
        exit('test');
    }

    public function updateCategoryColor(Request $request)
    {
        $categories = EventCategory::where('color_id',2)->get();
        $events = Event::where('event_color_id',2)->whereNotNull('category_id')->get();
        $color = EventColor::where('id',1)->first();

        foreach ($categories as $key => $value) {
            $value->color_id = $color->id;
            $value->color_code = $color->color_code;
            $value->save();
        }

        foreach ($events as $key => $event) {
            $event->event_color_id = $color->id;
            $event->color_code = $color->color_code;
            $event->save();
        }
        exit('test');
    }


    public function index()
    {
    	return view('Backend.dashboard');
    }

    public function fetchProfile()
    {
    	$loginuser = auth()->user();
    	return view('Backend.profile',compact('loginuser'));
    }


    public function updateProfile(UpdateProfileRequest $request)
    {
        DB::beginTransaction();
        try {
            $input = $request->except(['username']);
            $loginuser = User::find(auth()->id());
            if($loginuser->fill($input)->save()){
                if(!empty($input['user_details'])){
                    $input['user_details']['dob'] = Carbon::parse($input['user_details']['dob'])->format('Y-m-d');
                    if(!empty($input['user_details']['image'])){
                        $image_name = AwsBucketImageCompressUpload($input['user_details']['image'], 'users');
                        if(!empty($image_name)){
                            $input['user_details']['image'] = $image_name;
                        }else{
                            unset($input['user_details']['image']);
                        }
                    }
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
    
}
