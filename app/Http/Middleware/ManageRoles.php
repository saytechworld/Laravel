<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class ManageRoles
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $role)
    {
        
        $user_role_access = auth()->user()->ManageRoles($role);
        if($user_role_access != false){
            return $next($request);
        }
        return redirect()->route('frontend.auth.home')->withError("You are not authorized to access this url.");
    }
}
