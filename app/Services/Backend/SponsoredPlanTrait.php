<?php

namespace App\Services\Backend;

use App\Models\PlanPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception, Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Plan;
use App\Models\Order;

trait SponsoredPlanTrait
{


    public function buySponsoredPlan(Request $request)
    {   
        //exit('testing');
        DB::beginTransaction();
        try {
            exit('testing');
            $plan_id = 1;
            $plan = Plan::where(['status' => 1, 'id' => $plan_id])->first();
            $plan_price_id = 1;
            $plan_price = PlanPrice::where(['id'=> $plan_price_id, 'plan_id'=>$plan_id])->first();
            if(!empty($plan)){
                $input = $request->all();
                $orderdata = array();
                if (empty($plan_price)) {
                    throw new Exception("Invalid Plan Price.", 1);
                }
                $coaches =  User::whereHas('roles',function($query){
                    $query->where('role_id',3);
                })->whereNotIn('id',[4,5,89])->get();

                foreach ($coaches as $coach) {
                    $plan_end_date = "2020-08-07 10:16:19";
                    $orderdata['order_uuid'] = Str::uuid()->toString();
                    $orderdata['user_id'] = $coach->id;
                    $orderdata['plan_id'] = $plan->id;
                    $orderdata['price'] = $plan_price->price;
                    $service_tax = (env('SERVICE_TAX') / 100) * $plan_price->price;
                    $orderdata['service_tax'] = $service_tax;
                    $total_price = $orderdata['price'] + $orderdata['service_tax'];
                    $orderdata['total_price'] = $total_price;
                    $orderdata['status'] = 1;
                    $orderdata['transaction_id'] = 'sp_'.str_random(24);
                    $orderdata['plan_end_date'] = Carbon::parse($plan_end_date)->addMonth($plan_price->validity);
                    $order = new Order;
                    if($order->fill($orderdata)->save()){

                    }
                }

                DB::commit();
                return redirect()->route('admin.dashboard')->withSuccess('Plan purchased successfully.');
            }
            throw new Exception("Plan not found", 1);
        } catch (Exception $e) {
            DB::rollback();
           return redirect()->route('admin.dashboard')->withError($e->getMessage());
        }
    }


}
