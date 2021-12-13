<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Order;

class CoachSubscription
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
        /*if($request->ajax())
        {
            return response()->json(['status' => false, 'error' => true, 'data' => null]);
        }*/
        $trail_days = 30;
        $today_date = Carbon::now()->format('Y-m-d');
        $user_trail = User::whereRaw("( date(DATE_ADD(created_at, INTERVAL ".$trail_days." DAY)) < '".$today_date."' AND id = ".auth()->id()." )")->first();
        if(!empty($user_trail)){
           $checkBuyPlan =  Order::whereRaw("( user_id = ".auth()->id()." AND order_type != 1 AND status = 1 )")->whereDate('plan_end_date', '>', $today_date)->orderBy('id','DESC')->first();
            if(!empty($checkBuyPlan)){
                return $next($request);
            }else{
                if($request->ajax())
                {
                    return response()->json(['status' => false, 'error' => true, 'data' => null]);
                }else{
                    return redirect()->route('coach.plan.index')->withError("Please buy subscription."); 
                }
            }
        }else{
            return $next($request);
        }
    }
}
