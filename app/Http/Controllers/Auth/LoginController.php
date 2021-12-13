<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\UserConfirmation;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
//use App\Services\SocialProviderTrait;


class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers/*,SocialProviderTrait*/;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    public function redirectPath()
    {
        if (auth()->user()->hasRoles('admin') || auth()->user()->hasRoles('subadmin')) {
            return "/siteadmin";
            // or return route('routename');
        }
        if (auth()->user()->hasRoles('coach')) {
            return "/coach";
            // or return route('routename');
        }
        if (auth()->user()->hasRoles('athelete')) {
            return "/athlete";
            // or return route('routename');
        }
        return "/home";
    }

    //protected $redirectTo = '/dashboard';
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm()
    {
        return view('Frontend.auth.login');
    }


    protected function authenticated(Request $request, $user)
    {   
        $user_id = $user->id;
        if(!$user->IsConfirmed()){
            // Logout current user by guard
            $this->guard()->logout();
            $request->session()->invalidate();
            $user->notify(new UserConfirmation($user['confirmation_code']));
            return redirect()->back()
                ->withInput($request->only($this->username(), 'remember'))
                ->withErrors(['email' => 'Your account is not confirmed. A verification link has been sent to your email account (please check the inbox as well as the spam folder).']);
        }
        if(!$user->IsActive() ){
            // Logout current user by guard
            $this->guard()->logout();
            $request->session()->invalidate();

            return redirect()->back()
                ->withInput($request->only($this->username(), 'remember'))
                ->withErrors(['email' => 'Account has been disabled. Contact our team.']); 
        }
        if(!$user->IsDeletedStatus() ){
            // Logout current user by guard
            $this->guard()->logout();
            $request->session()->invalidate();

            return redirect()->back()
                ->withInput($request->only($this->username(), 'remember'))
                ->withErrors("Your account has been deleted. Please contact admin if you wish to reactivate your account at ".env('MAIL_USERNAME')." ");
        }
        return redirect()->intended($this->redirectPath())->withSuccess("You have successfully Logged In.");
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();
        $request->session()->invalidate();
        // After logout, redirect to login screen again
        return redirect()->route('frontend.auth.login')->withSuccess('You have successfully Logged Out.');
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return auth()->guard('web');
    }

}
