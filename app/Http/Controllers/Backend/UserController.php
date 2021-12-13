<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Exception, DB;
use App\Models\Role;
use App\Models\User;
use  App\Http\Requests\Backend\SubAdminRequest;
use  App\Http\Requests\Backend\InvitationRequest;
use App\Notifications\UserInvitation;
use App\Notifications\UserConfirmation;
use Carbon\Carbon;
use  App\Http\Requests\Backend\UserRequest;
use Kreait\Firebase;    
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use App\Models\Video;
use App\Models\Event;
use App\Models\EventAttendant;
use App\Models\Team;
use App\Models\EventCategory;
use App\Models\Notification;
use App\Models\UserFolder;



class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {   
        $input = $request->all();
        $users = (new User)->newQuery();
        if(!empty($input['q'])){
           $users->whereRaw("(name LIKE '%".$input['q']."%' OR email = '".$input['q']."' )");
        }
        $users = $users->whereHas('roles',function($query){
            $query->where('role_id','!=',1);
        })->orderBy('username','ASC')->paginate(10);
        $type = 'Users';
        return view('Backend.user.index',compact('users', 'type'))
            ->with('i', ($request->input('page', 1) - 1) * 10);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function athelete(Request $request)
    {
        $input = $request->all();
        $users = (new User)->newQuery();
        if(!empty($input['q'])){
            $users->whereRaw("(name LIKE '%".$input['q']."%' OR email = '".$input['q']."' )");
        }
        $users = $users->whereHas('roles',function($query){
            $query->where('role_id',4);
        })->orderBy('username','ASC')->paginate(10);

        $type = 'Athelete';
        return view('Backend.user.index',compact('users', 'type'))
            ->with('i', ($request->input('page', 1) - 1) * 10);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function coach(Request $request)
    {
        $input = $request->all();
        $users = (new User)->newQuery();
        if(!empty($input['q'])){
            $users->whereRaw("(name LIKE '%".$input['q']."%' OR email = '".$input['q']."' )");
        }
        $users = $users->whereHas('roles',function($query){
            $query->where('role_id',3);
        })->orderBy('username','ASC')->paginate(10);

        $type = 'Coach';
        return view('Backend.user.index',compact('users', 'type'))
            ->with('i', ($request->input('page', 1) - 1) * 10);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {    
        $role = Role::pluck('name','id')->toArray();
        return view('Backend.Access.user.create',compact('role'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserRequest $request)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            $input['confirmation_code'] = str_random(40);
            $input['status'] = isset($input['status']) ? 1 : 0;
            $input['confirmed'] = isset($input['confirmed']) ? 1 : 0;
            $input['password'] = bcrypt($input['password']);
            $input['email_verified_at'] = $input['confirmed'] == 1 ? Carbon::now()->toDateTimeString() : NULL;
            $user = new User;
            if($user->fill($input)->save()){
                $user->roles()->attach(3);
                if($user->confirmed != 1){
                    $user->notify(new UserConfirmation($input['confirmation_code'])); 
                }
                DB::commit();
                return redirect()->route('admin.access.user.index',['page' => $input['page']])->withSuccess('User created successfully.');
            }
            throw new Exception("Error Processing Request", 1);
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())->withError($e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        try {
            if(!$user->IsCustomer()){
                throw new Exception("You are not authorized to edit this user through this url.", 1);   
            }
            return view('Backend.Access.user.edit',compact('user'));
        } catch (Exception $e) {
            return redirect()->back()->withError($e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UserRequest $request, User $user)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            $input['status'] = isset($input['status']) ? 1 : 0;
            if($user->fill($input)->save()){
                DB::commit();
                return redirect()->back()->withSuccess('User updated successfully.');
            }
            throw new Exception("Error Processing Request", 1);
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())->withError($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, User $user)
    {
        DB::beginTransaction();
        try {
            if($user->hasRoles(1))
            {
               throw new Exception("You can't delete admin profile.", 1);
            }
            if($user->id == auth()->id())
            {
               throw new Exception("You can't delete your profile.", 1);
            }
            if($user->fill(['deleted_status' => 1])->save()){
                Event::where(['user_id' => $user->id])->delete();
                EventAttendant::where(['user_id' => $user->id, 'attendant_type' => 'A'])->delete();
                Team::where(['user_id' => $user->id])->delete();
                EventCategory::where(['user_id' => $user->id])->delete();
                $user->user_teams()->detach();
                Notification::whereIn('type',[2,3])->where(function($query) use($user){
                    $query->where('from_user_id', $user->id);
                    $query->orWhere('to_user_id', $user->id);
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
                self::sendFirebaseNotification($user_references);
                return redirect()->route('admin.access.user.index')->withSuccess('User profile deleted successfully.');
            }
            throw new Exception("Error occurred while deleting Profile! Please try again.", 1);
        }catch (Exception $e) {
            DB::rollback();
            return redirect()->route('admin.access.user.index')->withError($e->getMessage());
        }
        
    }


    public function restoreUser(User $user)
    {
        DB::beginTransaction();
        try {
            if($user->hasRoles(1))
            {
                throw new Exception("You can't Restore admin profile.", 1);
            }
            if($user->id == auth()->id())
            {
                throw new Exception("You can't Restore your profile.", 1);
            }

            $user->deleted_status =  0;
            if($user->save()){
                DB::commit();
                return redirect()->back()->withSuccess('User Restore successfully.');
            } 
            throw new Exception("Error Processing Request", 1);
        }catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withError($e->getMessage());
        }
    }

    public function markUser(Request $request, User $user, $status)
    {
        DB::beginTransaction();
        try {
            if(!$user->IsCustomer()){
                throw new Exception("You are not authorized to change status of this user through this url.", 1);
            }
            $user->status = $status == 1 ? 1 : 0;
            if($user->save()){
                DB::commit();
                return redirect()->back()->withSuccess('Status updated successfully.');
            }
            throw new Exception("Error Processing Request", 1);
        }catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withError($e->getMessage());
        }
    }

    public function resendUserConfirmation(Request $request, User $user)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            if(!$user->IsCustomer()){
                throw new Exception("You are not authorized to resend confirmation code to this user through this url.", 1);   
            }
            if(!$user->IsConfirmed()){
                $input['confirmation_code'] = str_random(40);
                if($user->fill($input)->save()){
                    $user->notify(new UserConfirmation($input['confirmation_code']));
                    DB::commit();
                    return redirect()->back()->withSuccess('Confirmation code has been resent.');
                }
                throw new Exception("Error Processing Request", 1);
            }
            throw new Exception("This account is already confirmed.", 1);
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withError($e->getMessage());
        }
    }


    public function fetchSubAdmin(Request $request)
    {
        $input = $request->all();
        $users = (new User)->newQuery();
        if(!empty($input['q'])){
           $users->whereRaw("(name LIKE '%".$input['q']."%' OR email = '".$input['q']."' )");
        }
        $users = $users->whereHas('roles', function($query){
            $query->where('role_id',2);
        })->orderBy('username','ASC')->paginate(25);

        // echo "<pre>"; print_r($users); exit;

        return view('Backend.Access.user.subadmin.index',compact('users'))
            ->with('i', ($request->input('page', 1) - 1) * 25);
    }


    public function createSubAdmin()
    {    
        return view('Backend.Access.user.subadmin.create');
    }


    public function storeSubAdmin(SubAdminRequest $request)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            $input['invitation_code'] = str_random(40);
            $input['inviting_time'] = date('Y-m-d H:i:s');
            $user = new User;
            if($user->fill($input)->save()){
                $user->roles()->attach(2);
                $user->notify(new UserInvitation($input['invitation_code']));
                DB::commit();
                return redirect()->route('admin.access.subadmin.index',['page' => $input['page']])->withSuccess('Sub Admin created successfully.');
            }
            throw new Exception("Error Processing Request", 1);
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())->withError($e->getMessage());
        }
    }

    public function sendinvitation(Request $request, User $subadmin)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            if(!$subadmin->IsSubAdmin()){
                throw new Exception("This account is not authorized for invitation.", 1);   
            }
            if(!$subadmin->IsConfirmed()){
                $input['invitation_code'] = str_random(40);
                $input['inviting_time'] = date('Y-m-d H:i:s');
                if($subadmin->fill($input)->save()){
                    $subadmin->notify(new UserInvitation($input['invitation_code']));
                    DB::commit();
                    return redirect()->back()->withSuccess('Invitation code has been resent.');
                }
                throw new Exception("Error Processing Request", 1);
            }
            throw new Exception("This account is already confirmed.", 1);
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withError($e->getMessage());
        }
    }



    public function InvitationSubadmin($invitation_code)
    {
        try{
            $expiry_hour = env('INVITATION_EXPIRY_HOUR');
            $end_time = Carbon::now()->toDateTimeString();
            $started_time =  Carbon::now()->subHours($expiry_hour)->toDateTimeString(); 
            $checkUser = User::where('invitation_code',$invitation_code)->whereRaw("( TIMESTAMP(inviting_time) BETWEEN '".$started_time."' AND  '".$end_time."')")->first();
            if(!empty($checkUser)){
                if(!$checkUser->IsConfirmed()){
                    return view('auth.admin.invite_password',compact('subadmin','invitation_code'));
                }
                throw new Exception("Your account is already confirmed.", 1);
            }
            throw new Exception("Invitation code has been expired or not valid.", 1);
        } catch (Exception $e) {
            return redirect()->route('admin.login')->withError($e->getMessage());
        }
    }

    public function StoreInvitationSubadmin(InvitationRequest $request, $invitation_code)
    {
        DB::beginTransaction();
        try {
            $expiry_hour = env('INVITATION_EXPIRY_HOUR');
            $end_time = Carbon::now()->toDateTimeString();
            $started_time =  Carbon::now()->subHours($expiry_hour)->toDateTimeString(); 
            $input = $request->all();
            $user = User::where('invitation_code',$invitation_code)->whereRaw("( TIMESTAMP(inviting_time) BETWEEN '".$started_time."' AND  '".$end_time."')")->first();
            if(!empty($user)){
                if(!$user->IsConfirmed()){
                    $input['password'] = bcrypt($input['password']);
                    $input['status'] = 1;
                    $input['confirmed'] = 1;
                    $input['email_verified_at'] = date('Y-m-d H:i:s');
                    if($user->fill($input)->save()){
                        DB::commit();
                        auth()->guard('web_admin')->login($user);
                        return redirect()->route('admin.dashboard')->withSuccess('Password generated successfully.');
                    }   
                    throw new Exception("Error Processing Request", 1);
                }
                throw new Exception("Your account is already confirmed.", 1);
            }
            throw new Exception("Invitation code has been expired or not valid.", 1); 
        }catch (Exception $e) {
            DB::rollback();
            if (stripos($e->getMessage(), "expired") !== false ||  stripos($e->getMessage(), "confirmed") !== false) {
               return redirect()->route('admin.login')->withError($e->getMessage());
            }
            return redirect()->back()->withInput($request->all())->withError($e->getMessage());
        }
    }


    public function markSubAdmin(Request $request, User $subadmin, $status)
    {
        DB::beginTransaction();
        try {
            if(!$subadmin->IsSubAdmin()){
                throw new Exception("You are not authorized to change status of this user through this url.", 1);   
            }
            $subadmin->status = $status == 1 ? 1 : 0;
            if($subadmin->save()){
                DB::commit();
                return redirect()->back()->withSuccess('Status updated successfully.');
            } 
            throw new Exception("Error Processing Request", 1);
        }catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withError($e->getMessage());
        }
    }


    public function editSubAdmin(User $subadmin)
    {
        try {
            if(!$subadmin->IsSubAdmin()){
                throw new Exception("You are not authorized to edit this user through this url.", 1);   
            }
            return view('Backend.Access.user.subadmin.edit',compact('subadmin'));
        } catch (Exception $e) {
            return redirect()->back()->withError($e->getMessage());
        }
    }

   
    public function updateSubAdmin(SubAdminRequest $request, User $subadmin)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            $input['status'] = isset($input['status']) ? 1 : 0;
            if($subadmin->fill($input)->save()){
                DB::commit();
                return redirect()->back()->withSuccess('Sub Admin updated successfully.');
            }
            throw new Exception("Error Processing Request", 1);
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())->withError($e->getMessage());
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
