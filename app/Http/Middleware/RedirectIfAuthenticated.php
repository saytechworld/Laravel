<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->check()) {
            if (auth()->user()->hasRoles('admin') || auth()->user()->hasRoles('subadmin')) {
                return redirect('siteadmin/dashboard');
            }
            if (auth()->user()->hasRoles('coach')) {
                return redirect('coach/dashboard');
            }
            if (auth()->user()->hasRoles('athelete')) {
                return redirect('athlete/dashboard');
            }
            return redirect('/home');
        }
        return $next($request);
    }
}
