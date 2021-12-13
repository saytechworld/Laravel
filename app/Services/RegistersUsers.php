<?php

namespace App\Services;

use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;
use App\Models\User;
use App\Services\RedirectsUsers;
use App\Http\Requests\Frontend\Auth\RegisteredRequest;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Notifications\UserConfirmation;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Role;

trait RegistersUsers
{
    use RedirectsUsers;

    /**
     * Show the application registration form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showRegistrationForm()
    {
        return view('Frontend.auth.register');
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(RegisteredRequest $request)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            $user = new User;
            $input['status'] = 1;
            $input['password'] = bcrypt($input['password']);
            $input['user_uuid'] = Str::uuid()->toString();
            $input['confirmation_code'] = str_random(40);
            if($user->fill($input)->save()){
                $user_role = Role::where('name',$input['user_type'])->first();
                if(!empty($user_role)){
                    $user->roles()->attach($user_role->id);
                    $detail = new UserDetail;
                    $detail_input['user_id'] = $user->id;
                    if($detail->fill($detail_input)->save()) {
                        $user->notify(new UserConfirmation($input['confirmation_code']));
                        DB::commit();
                        event(new Registered($user));
                        return redirect($this->redirectPath())->withSuccess("A verification link has been sent to your email account (please check the inbox as well as the spam folder).");
                    }
                    throw new Exception("Error Processing Request", 1);
                }
                throw new Exception("Role not found.", 1);
            }
            throw new Exception("Error Processing Request", 1);
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())->withError($e->getMessage());
        }
    }

    /**
     * Get the guard to be used during registration.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard();
    }

    /**
     * The user has been registered.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function registered(Request $request, $user)
    {
        //
    }


    public function UserVerification(Request $request, $confirmation_code)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();
            $user = User::where('confirmation_code',$confirmation_code)->first();
            if(!empty($user)){
                if(!$user->IsConfirmed()){
                    $input['confirmed'] = 1;
                    $input['email_verified_at'] = date('Y-m-d H:i:s');
                    if($user->fill($input)->save()){
                        DB::commit();
                        return redirect()->route('frontend.auth.login')->withSuccess('Your account has been verified successfully.');
                    }   
                    throw new Exception("Error Processing Request", 1);
                }
                throw new Exception("Your account is already confirmed.", 1);
            }
            throw new Exception("Confirmation code not valid.", 1);
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->route('frontend.auth.login')->withError($e->getMessage());
        }
    }

}
