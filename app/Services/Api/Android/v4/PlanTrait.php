<?php

namespace App\Services\Api\Android\v4;

use App\Models\Order;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\SessionRequest;
use Carbon\Carbon;
use http\Env\Response;
use Illuminate\Http\Request;
use Exception, Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Cartalyst\Stripe\Stripe;
use Cartalyst\Stripe\Exception\BadRequestException;
use Cartalyst\Stripe\Exception\CardErrorException;
use Cartalyst\Stripe\Exception\InvalidRequestException;
use Cartalyst\Stripe\Exception\NotFoundException;

trait PlanTrait
{
    public function getPlan(Request $request)
    {
        try {
            $user = $this->getAuthenticatedUser();

            $plans = Plan::selectRaw('id,title,description')->with('planPrice:id,plan_id,price,validity')->whereHas('planPrice')->where('status',1)->get();

            $orderdetails =  Order::where(['user_id' => $user->id, 'status' => 1])->where('order_type', '!=', 1)->orderBy('id','DESC')->first();

            $buy_plan = 1;
            $plan_id = '';

            if (!empty($orderdetails)) {
                $plan_id = $orderdetails->plan_id;
                if (Carbon::parse($orderdetails->plan_end_date)->gt(Carbon::now())) {
                    $to = Carbon::createFromFormat('Y-m-d', Carbon::parse($orderdetails->plan_end_date)->format('Y-m-d'));
                    $from = Carbon::createFromFormat('Y-m-d', Carbon::now()->format('Y-m-d'));
                    $diff_in_days = $to->diffInDays($from);
                    if ($diff_in_days <= 10) {
                        $buy_plan = 1;
                    } else {
                        $buy_plan = 0;
                    }
                } else {
                    $buy_plan = 1;
                }
            }

            $current_plan = [
              'buy_plan' => $buy_plan,
              'plan_id'  => $plan_id
            ];

            $this->WebApiArray['status'] = true;
            if ($plans->count() > 0) {
                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data']['plans'] = $plans;
                $this->WebApiArray['data']['current_plan'] = $current_plan;
                $this->WebApiArray['statusCode'] = 0;
            } else {
                $this->WebApiArray['message'] = 'Record not found.';
                $this->WebApiArray['statusCode'] = 1;
            }
            return response()->json($this->WebApiArray);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function getPlanDetail(Request $request)
    {
        try {
            $input = $request->all();

            $user = $this->getAuthenticatedUser();

            $validator = Validator::make($input,[
                'plan_id'   =>  'required',
                'plan_price_id'   =>  'required',
            ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $plan_price = PlanPrice::where(['id' => $input['plan_price_id'], 'plan_id' => $input['plan_id']])->first();

            if (!empty($plan_price)) {
                
                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data'] = $plan_price;
                return response()->json($this->WebApiArray);
            }
            throw new Exception("Invalid Plan.", 1);

        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function orderList(Request $request)
    {
        try {
            $input = $request->all();
            $page = isset($input['page']) ? $input['page'] : 1;

            $user = $this->getAuthenticatedUser();

            $orders = Order::with('plan:id,title')
                ->selectRaw('id,order_uuid,status,plan_id,order_type,total_price,created_at')
                ->where(['user_id' => $user->id])->orderBy('id', 'desc')->paginate(25,['*'],'page', $page);

            $this->WebApiArray['status'] = true;
            if ($orders->count() > 0) {
                $this->WebApiArray['message'] = 'Record found';
                $this->WebApiArray['data'] = $orders;
                $this->WebApiArray['statusCode'] = 0;
            } else {
                $this->WebApiArray['message'] = 'Record not found';
                $this->WebApiArray['statusCode'] = 1;
            }
            return response()->json($this->WebApiArray);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function orderDetail(Request $request)
    {
        try {
            $input = $request->all();

            $validator = Validator::make($input,[
                'order_id'   =>  'required|exists:orders,id',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            $order = Order::with('plan:id,title,description')->with('user:id,name')
                ->selectRaw('id,order_uuid,user_id,status,plan_id,order_type,price,service_tax,transaction_fees,total_price,created_at')
                ->where(['id' => $input['order_id'] ,'user_id' => $user->id])->first();

            if (!empty($order)) {

                $order->user->makeHidden(['user_image','total_balance','remaining_balance','role_type']);
                $order->user->user_details->makeHidden(['user_id','image','mobile_code_id','gender','dob','zipcode_id','experience','about','created_at','updated_at','user_profile_image']);

                if ($order->user->user_details->country) {
                    $order->user->user_details->country->makeHidden(['slug','phone_code','ISO_code','currency_code','stripe_enabled','created_at','updated_at','status']);
                }
                if ($order->user->user_details->state) {
                    $order->user->user_details->state->makeHidden(['slug','country_id','created_at','updated_at','status']);
                }
                if ($order->user->user_details->city) {
                    $order->user->user_details->city->makeHidden(['slug','country_id','state_id','created_at','updated_at','status']);
                }

                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Record found';
                $this->WebApiArray['data'] = $order;
                return response()->json($this->WebApiArray);
            }
            throw new Exception('Order detail not found', 1);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function sessionList(Request $request)
    {
        try {
            $input = $request->all();
            $page = isset($input['page']) ? $input['page'] : 1;

            $user = $this->getAuthenticatedUser();

            $session_requests = SessionRequest::selectRaw('id,coach_id,chat_id,athelete_id,chat_session_uuid,status,start_session_time,request_by,session_price')->
                    with('athelete_user:id,name,user_uuid')->with('coach_user:id,name,user_uuid')->
                  whereRaw("(coach_id = ".$user->id." OR athelete_id = ".$user->id.")")
                ->orderBy('updated_at', 'DESC')->paginate(25, ['*'], 'page', $page);

            $this->WebApiArray['status'] = true;
            if ($session_requests->count() > 0) {

                $session_requests->map(function ($session_request) {
                   $session_request->makeHidden(['chat_id','session_platform_fees']);
                   $session_request->athelete_user->makeHidden(['role_type','total_balance','remaining_balance','user_details','user_image','user_thumb_image']);
                   $session_request->coach_user->makeHidden(['role_type','total_balance','remaining_balance','user_details','user_image','user_thumb_image']);
                });

                $this->WebApiArray['message'] = 'Record found';
                $this->WebApiArray['statusCode'] = 0;
                $this->WebApiArray['data'] = $session_requests;
            } else {
                $this->WebApiArray['message'] = 'Record not found';
                $this->WebApiArray['statusCode'] = 1;
            }
            return response()->json($this->WebApiArray);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function buyPlan(Request $request) {
        DB::beginTransaction();
        try {
            if (!$request->isMethod('post')) {
                throw new Exception('Method not allowed', 1);
            }

            $input = $request->all();

            $validator = Validator::make($input,[
                'plan_id' => 'required|exists:plans,id',
                'plan_price_id' => 'required|exists:plan_prices,id',
                'card_type' => 'required|in:N,S',
                'payment_type' => 'required:in:1,2',
            ]);

            if($validator->fails())
            {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            if ($input['card_type'] == 'N') {
                $validator = Validator::make($input, [
                    'stripe_token' => 'required',
                ]);
            } else {
                $validator = Validator::make($input, [
                    'card_id' => 'required',
                ]);
            }

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            $plan = Plan::where('id', $input['plan_id'])->first();

            if (empty($plan)) {
                throw new Exception('Plan not exist', 1);
            }

            $plan_price = PlanPrice::where('id', $input['plan_price_id'])->first();

            if (empty($plan)) {
                throw new Exception('Plan price not exist', 1);
            }

            $orderdetails = Order::where(['user_id' => $user->id, 'status' => 1])->where('order_type','!=', 1)->orderBy('id', 'DESC')->first();
            $orderdata = array();
            if (!empty($orderdetails)) {

                if ($orderdetails->plan_id == $plan->id) {
                    if (Carbon::parse($orderdetails->plan_end_date)->gt(Carbon::now())) {
                        $to = Carbon::createFromFormat('Y-m-d', Carbon::parse($orderdetails->plan_end_date)->format('Y-m-d'));
                        $from = Carbon::createFromFormat('Y-m-d', Carbon::now()->format('Y-m-d'));
                        $diff_in_days = $to->diffInDays($from);

                        if ($diff_in_days <= 10) {
                            $orderdata['plan_end_date'] = Carbon::parse($orderdetails->plan_end_date)->addMonth($plan_price->validity);
                        } else {
                            throw new Exception("You have already renewed this plan within 30 days",1);
                        }
                    } else {
                        $orderdata['plan_end_date'] = Carbon::now()->addMonth($plan_price->validity);
                    }
                } else {
                    $orderdata['plan_end_date'] = Carbon::now()->addMonth($plan_price->validity);
                }

            }else{
                $orderdata['plan_end_date'] = Carbon::now()->addMonth($plan_price->validity);
            }

            $orderdata['order_uuid'] = Str::uuid()->toString();
            $orderdata['user_id'] = $user->id;
            $orderdata['plan_id'] = $plan->id;
            $orderdata['price'] = $plan_price->price;
            $orderdata['service_tax'] = (env('SERVICE_TAX') / 100) * $plan_price->price;
            $orderdata['total_price'] = $orderdata['price'] + $orderdata['service_tax'];
            $orderdata['status'] = 0;
            $orderdata['payment_type'] = $input['payment_type'];

            $order = new Order();

            if ($order->fill($orderdata)->save()) {
                $stripe_customer_id = $this->SaveStripeId();
                if(empty($stripe_customer_id)){
                    throw new Exception("Error occured while processing payment! Please try again.", 1);
                }
                $stripe = Stripe::make(env('STRIPE_SECRET'));
                $stripe->setApiKey(env('STRIPE_SECRET'));
                if ($input['card_type'] == 'N') {
                    if(isset($input['save_card']) && $input['save_card'] == 1)
                    {
                        $StripeToken = $stripe->tokens()->find($input['stripe_token']);
                        $card = $this->fetchStripeCardDetails($StripeToken);
                        if(count($card) == 0)
                        {
                            throw new Exception("Error occured while processing payment! Please try again.", 1);
                        }
                        $charge = $stripe->charges()->create([
                            'currency' => 'EUR',
                            'amount' =>  $orderdata['total_price'],
                            'description' => 'Payment for '.$plan->title.' plan.',
                            "customer" => $stripe_customer_id,
                            "source" => $card['id'],
                            "metadata" => [
                                'order_uuid' => $order->order_uuid,
                                'type'      => 'plan',
                            ]
                        ]);
                    }else{
                        $charge =   $stripe->charges()->create([
                            'card' => $input['stripe_token'],
                            'currency' => 'EUR',
                            'amount' =>  $orderdata['total_price'],
                            'description' => 'Payment for '.$plan->title.' plan.',
                            "metadata" => [
                                'order_uuid' => $order->order_uuid,
                                'type'      => 'plan',
                            ]
                        ]);
                    }
                } else {
                    $charge = $stripe->charges()->create([
                        'currency' => 'EUR',
                        'amount' =>  $orderdata['total_price'],
                        'description' => 'Payment for '.$plan->title.' plan.',
                        "customer" => $stripe_customer_id,
                        "source" => $input['card_id'],
                        "metadata" => [
                            'order_uuid' => $order->order_uuid,
                            'type'      => 'plan',
                        ]
                    ]);
                }
                if ($charge['status'] == 'succeeded') {
                    $order->status = 1;
                    $order->transaction_id = $charge['id'];
                    $order->save();
                    DB::commit();
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Transaction successful';
                    return response()->json($this->WebApiArray);
                }else{
                    $order->transaction_id = $charge['id'];
                    $order->save();
                    DB::commit();
                    throw new Exception('Transaction failed! Please try after sometime.',1);
                }
            }
            throw new Exception('Error processing request',1);
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function buyPlanAndroid(Request $request) {
        DB::beginTransaction();
        try {
            if (!$request->isMethod('post')) {
                throw new Exception('Method not allowed', 1);
            }

            $input = $request->all();

            $validator = Validator::make($input,[
                'plan_id' => 'required|exists:plans,id',
                'plan_price_id' => 'required|exists:plan_prices,id',
                'card_type' => 'required|in:N,S',
                'payment_type' => 'required:in:1,2',
            ]);

            if($validator->fails())
            {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            if ($input['card_type'] == 'N') {
                $validator = Validator::make($input, [
                    'card_holder' => 'required',
                    'card_number' => 'required|max:20',
                    'month' => 'required|min:2|max:2',
                    'year' =>   'required|max:4|min:4',
                    'cvc' =>    'required|max:4',
                ]);
            } else {
                $validator = Validator::make($input, [
                    'card_id' => 'required',
                ]);
            }

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            $plan = Plan::where('id', $input['plan_id'])->first();

            if (empty($plan)) {
                throw new Exception('Plan not exist', 1);
            }

            $plan_price = PlanPrice::where('id', $input['plan_price_id'])->first();

            if (empty($plan)) {
                throw new Exception('Plan price not exist', 1);
            }

            $orderdetails = Order::where(['user_id' => $user->id, 'status' => 1])->where('order_type','!=', 1)->orderBy('id', 'DESC')->first();
            $orderdata = array();
            if (!empty($orderdetails)) {

                if ($orderdetails->plan_id == $plan->id) {
                    if (Carbon::parse($orderdetails->plan_end_date)->gt(Carbon::now())) {
                        $to = Carbon::createFromFormat('Y-m-d', Carbon::parse($orderdetails->plan_end_date)->format('Y-m-d'));
                        $from = Carbon::createFromFormat('Y-m-d', Carbon::now()->format('Y-m-d'));
                        $diff_in_days = $to->diffInDays($from);

                        if ($diff_in_days <= 10) {
                            $orderdata['plan_end_date'] = Carbon::parse($orderdetails->plan_end_date)->addMonth($plan_price->validity);
                        } else {
                            throw new Exception("You have already renewed this plan within 30 days",1);
                        }
                    } else {
                        $orderdata['plan_end_date'] = Carbon::now()->addMonth($plan_price->validity);
                    }
                } else {
                    $orderdata['plan_end_date'] = Carbon::now()->addMonth($plan_price->validity);
                }

            }else{
                $orderdata['plan_end_date'] = Carbon::now()->addMonth($plan_price->validity);
            }

            $orderdata['order_uuid'] = Str::uuid()->toString();
            $orderdata['user_id'] = $user->id;
            $orderdata['plan_id'] = $plan->id;
            $orderdata['price'] = $plan_price->price;
            $orderdata['service_tax'] = (env('SERVICE_TAX') / 100) * $plan_price->price;
            $orderdata['total_price'] = $orderdata['price'] + $orderdata['service_tax'];
            $orderdata['status'] = 0;
            $orderdata['payment_type'] = $input['payment_type'];

            $order = new Order();

            if ($order->fill($orderdata)->save()) {
                $stripe_customer_id = self::SaveStripeId();
                if(empty($stripe_customer_id)){
                    throw new Exception("Error occured while processing payment! Please try again.", 1);
                }
                $stripe = Stripe::make(env('STRIPE_SECRET'));
                $stripe->setApiKey(env('STRIPE_SECRET'));
                if ($input['card_type'] == 'N') {


                    //create token
                    $token = $stripe->tokens()->create([
                        'card' => [
                            'number'    => $input['card_number'],
                            'exp_month' => $input['month'],
                            'exp_year'  => $input['year'],
                            'cvc'       => $input['cvc'],
                        ],
                    ]);

                    $input['stripe_token'] = $token['id'];

                    if(isset($input['save_card']) && $input['save_card'] == 1)
                    {
                        $StripeToken = $stripe->tokens()->find($input['stripe_token']);
                        $card = self::fetchStripeCardDetails($StripeToken);
                        if(count($card) == 0)
                        {
                            throw new Exception("Error occured while processing payment! Please try again.", 1);
                        }
                        $charge = $stripe->charges()->create([
                            'currency' => 'EUR',
                            'amount' =>  $orderdata['total_price'],
                            'description' => 'Payment for '.$plan->title.' plan.',
                            "customer" => $stripe_customer_id,
                            "source" => $card['id'],
                            "metadata" => [
                                'order_uuid' => $order->order_uuid,
                                'type'      => 'plan',
                            ]
                        ]);
                    }else{
                        $charge =   $stripe->charges()->create([
                            'card' => $input['stripe_token'],
                            'currency' => 'EUR',
                            'amount' =>  $orderdata['total_price'],
                            'description' => 'Payment for '.$plan->title.' plan.',
                            "metadata" => [
                                'order_uuid' => $order->order_uuid,
                                'type'      => 'plan',
                            ]
                        ]);
                    }
                } else {
                    $charge = $stripe->charges()->create([
                        'currency' => 'EUR',
                        'amount' =>  $orderdata['total_price'],
                        'description' => 'Payment for '.$plan->title.' plan.',
                        "customer" => $stripe_customer_id,
                        "source" => $input['card_id'],
                        "metadata" => [
                            'order_uuid' => $order->order_uuid,
                            'type'      => 'plan',
                        ]
                    ]);
                }
                if ($charge['status'] == 'succeeded') {
                    $order->status = 1;
                    $order->transaction_id = $charge['id'];
                    $order->save();
                    DB::commit();
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Transaction successful';
                    return response()->json($this->WebApiArray);
                }else{
                    $order->transaction_id = $charge['id'];
                    $order->save();
                    DB::commit();
                    throw new Exception('Transaction failed! Please try after sometime.',1);
                }
            }
            throw new Exception('Error processing request',1);
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }
}
