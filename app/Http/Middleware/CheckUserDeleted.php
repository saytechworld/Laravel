<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class CheckUserDeleted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next)
    {   
        $user = User::where('id',auth()->id())->first();
        if(!$user->IsDeletedStatus() ){
            // Logout current user by guard
            auth()->guard('web')->logout();
            $request->session()->invalidate();
            return redirect()->route('frontend.auth.login')
                ->withErrors("Your account has been deleted. Please contact admin if you wish to reactivate your account at ".env('MAIL_USERNAME')." "); 
        }
        return $next($request);
    }
}
