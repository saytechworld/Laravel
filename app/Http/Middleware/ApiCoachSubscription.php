<?php

namespace App\Http\Middleware;

use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;

class ApiCoachSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(auth()->user()->role_type != 'coach') {
            return $next($request);
        }
        $trail_days = 30;
        $today_date = Carbon::now()->format('Y-m-d');
        $user_trail = User::whereRaw("( date(DATE_ADD(created_at, INTERVAL ".$trail_days." DAY)) < '".$today_date."' AND id = ".auth()->id()." )")->first();
        if(!empty($user_trail)){
            $checkBuyPlan =  Order::whereRaw("( user_id = ".auth()->id()." AND order_type != 1 AND status = 1 )")->whereDate('plan_end_date', '>', $today_date)->orderBy('id','DESC')->first();
            if(!empty($checkBuyPlan)){
                return $next($request);
            }else{

                $CheckTrailPeriod = CheckUserTrailPeriod();
                $CheckSubscriptionPeriod = CheckUserSubscriptionPeriod();

                $message = '';
                if(!empty($CheckTrailPeriod['status'])) {
                    $message = 'You have '.$CheckTrailPeriod['trail_days'].' days trail period remaining. To keep accessing all the features of Asportcoach please buy subscription plan';
                }
                if(!empty($CheckSubscriptionPeriod['status'])){
                    $message = 'Your subscription is ending in '.$CheckSubscriptionPeriod['trail_days'].' days please renew your subscription plan';
                }

                $this->WebApiArray['data'] = null;
                $this->WebApiArray['status'] = false;
                $this->WebApiArray['login_required'] = false;
                $this->WebApiArray['message'] = $message;
                $this->WebApiArray['subscription'] = true;
                return response()->json($this->WebApiArray);
            }
        }else{
            return $next($request);
        }
    }
}
