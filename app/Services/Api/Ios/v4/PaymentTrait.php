<?php

namespace App\Services\Api\Ios\v4;

use App\Models\Country;
use App\Models\User;
use App\Models\Withdrawal;
use Cartalyst\Stripe\Exception\BadRequestException;
use Cartalyst\Stripe\Exception\CardErrorException;
use Cartalyst\Stripe\Exception\InvalidRequestException;
use Cartalyst\Stripe\Exception\NotFoundException;
use Cartalyst\Stripe\Stripe;
use Illuminate\Http\Request;
use Exception, Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Stripe as StripePhp;
use Carbon\Carbon;
use Illuminate\Support\Str;

trait PaymentTrait
{
    public function bankAccount() {
        try {

            StripePhp\Stripe::setApiKey(env('STRIPE_SECRET'));

            $account = null;
            if (!empty(auth()->user()->stripe_account_id)) {

                $account = StripePhp\Account::retrieve(
                    auth()->user()->stripe_account_id
                );
            };

            $stripe_country_arr = Country::selectRaw('id,title,ISO_code')->whereRaw("(status = 1 AND stripe_enabled = 1 AND currency_code IS NOT NULL AND ISO_code IS NOT NULL)")->get();

            $withdrawals = Withdrawal::where('user_id', auth()->id())->orderBy('id', 'DESC')->get();

            $this->WebApiArray['status'] = true;
            $this->WebApiArray['message'] = 'Record found.';
            $this->WebApiArray['data']['account'] = $account;
            $this->WebApiArray['data']['country'] = $stripe_country_arr;
            $this->WebApiArray['data']['withdrawals'] = $withdrawals;
            $this->WebApiArray['data']['remaining_balance'] = auth()->user()->remaining_balance;
            $this->WebApiArray['data']['non_withdrawable_amount'] = auth()->user()->non_withdrawable_amount;
            return response()->json($this->WebApiArray);

        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function createBankAccount(Request $request)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();

            $validator = Validator::make($input, [
                'IBAN' => 'required',
                'url' => 'required',
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => 'required',
                'phone' => 'required',
                'gender' => 'required',
                'dob' => 'required|date_format:m/d/Y',
                'country' => 'required',
                'state' => 'required',
                'city' => 'required',
                'postal_code' => 'required',
                'line1' => 'required',
                'additional_document_front' => 'required|mimes:jpg,png,jpeg',
                'additional_document_back' => 'required|mimes:jpg,png,jpeg',
                'document_front' => 'required|mimes:jpg,png,jpeg',
                'document_back' => 'required|mimes:jpg,png,jpeg',
            ],
                [
                    'IBAN.required' => 'The IBAN is required',
                    'url.required' => 'The business profile is required',
                    'first_name.required' => 'The first name is required',
                    'last_name.required' => 'The last name is required',
                    'email.required' => 'The email is required',
                    'phone.required' => 'The phone is required',
                    'gender.required' => 'The Gender is required',
                    'dob.required' => 'The DOB is required',
                    'country.required' => 'The country is required',
                    'state.required' => 'The state is required',
                    'city.required' => 'The city is required',
                    'postal_code.required' => 'The postal code is required',
                    'line1.required' => 'The address Line1 is required',
                    'additional_document_front.required' => 'The additional document front is required',
                    'additional_document_back.required' => 'The additional document back is required',
                    'document_front.required' => 'The document front is required',
                    'document_back.required' => 'The document back is required',
                ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $input['business_profile']['url'] = $input['url'];
            $input['individual']['first_name'] = $input['first_name'];
            $input['individual']['last_name'] = $input['last_name'];
            $input['individual']['email'] = $input['email'];
            $input['individual']['phone'] = $input['phone'];
            $input['individual']['gender'] = $input['gender'];
            $input['individual']['dob'] = $input['dob'];
            $input['individual']['address']['country'] = $input['country'];
            $input['individual']['address']['state'] = $input['state'];
            $input['individual']['address']['city'] = $input['city'];
            $input['individual']['address']['postal_code'] = $input['postal_code'];
            $input['individual']['address']['line1'] = $input['line1'];
            if ($input['line2']) {
                $input['individual']['address']['line2'] = $input['line2'];
            }

            $input['business_profile']['mcc'] = '5561';
            $dob = Carbon::parse($input['individual']['dob']);

            $input['individual']['dob'] = [
                'day' => $dob->day,
                'month' => $dob->month,
                'year' => $dob->year
            ];

            StripePhp\Stripe::setApiKey(env('STRIPE_SECRET'));

            $account = [];
            if (!empty(auth()->user()->stripe_account_id)) {

                $account = StripePhp\Account::retrieve(
                    auth()->user()->stripe_account_id
                );
            };

            if (count($account) > 0) {
                throw new Exception('Account already added',1);
            }

            if(!empty($input['additional_document_front'])){
                $additional_document_front = imagecompressupload($input['additional_document_front'], 'stripe_document');

                $path = public_path("/images/stripe_document/".$additional_document_front);

                $fp = fopen($path, 'r');
                $file_id = StripePhp\File::create([
                    'purpose' => 'identity_document',
                    'file' => $fp
                ]);
                fclose($fp);
                unlink($path);
                if(!empty($file_id)){
                    $input['individual']['verification']['additional_document']['front'] = $file_id['id'];
                }else{
                    unset($input['individual']['verification']['additional_document']['front']);
                }
            }

            if(!empty($input['additional_document_back'])){
                $additional_document_back = imagecompressupload($input['additional_document_back'], 'stripe_document');

                $path = public_path("/images/stripe_document/".$additional_document_back);
                $fp = fopen($path, 'r');
                $file_id = StripePhp\File::create([
                    'purpose' => 'identity_document',
                    'file' => $fp
                ]);
                fclose($fp);
                unlink($path);
                if(!empty($file_id)){
                    $input['individual']['verification']['additional_document']['back'] = $file_id['id'];
                }else{
                    unset($input['individual']['verification']['additional_document']['back']);
                }
            }

            if(!empty($input['document_front'])){
                $document_front = imagecompressupload($input['document_front'], 'stripe_document');

                $path = public_path("/images/stripe_document/".$document_front);
                $fp = fopen($path, 'r');
                $file_id = StripePhp\File::create([
                    'purpose' => 'identity_document',
                    'file' => $fp
                ]);
                fclose($fp);
                unlink($path);
                if(!empty($file_id)){
                    $input['individual']['verification']['document']['front'] = $file_id['id'];
                }else{
                    unset($input['individual']['verification']['document']['front']);
                }
            }

            if(!empty($input['document_back'])){
                $document_back = imagecompressupload($input['document_back'], 'stripe_document');

                $path = public_path("/images/stripe_document/".$document_back);
                $fp = fopen($path, 'r');
                $file_id = StripePhp\File::create([
                    'purpose' => 'identity_document',
                    'file' => $fp
                ]);
                fclose($fp);
                unlink($path);
                if(!empty($file_id)){
                    $input['individual']['verification']['document']['back'] = $file_id['id'];
                }else{
                    unset($input['individual']['verification']['document']['back']);
                }
            }

            $account = StripePhp\Account::create([
                'type' => 'custom',
                'country' => $input['individual']['address']['country'],
                'email' => auth()->user()->email,
                'requested_capabilities' => [
                    'card_payments',
                    'transfers'
                ],
                'external_account' => [
                    "country" => $input['individual']['address']['country'],
                    "currency" => "EUR",
                    "account_number" => $input['IBAN'],
                    "object" => "bank_account"
                ],
                'tos_acceptance' => [
                    'date' => time(),
                    'ip' => $_SERVER['REMOTE_ADDR'], // Assumes you're not using a proxy
                ],
                'business_profile' => $input['business_profile'],
                'business_type' => 'individual',
                'default_currency' => 'EUR',
                'individual' =>  $input['individual'],
            ]);

            if (!empty($account)) {

                $LoggedInUser = User::where('id',auth()->id())->first();
                $LoggedInUser->stripe_account_id = $account->id;
                $LoggedInUser->save();
                DB::commit();

                $status = $account['individual']['verification']['status'];
                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] ='Your account status is '.$status;
                return response()->json($this->WebApiArray);
            }


        } catch (NotFoundException $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }  catch (BadRequestException $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        } catch (InvalidRequestException $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        } catch (CardErrorException $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function updateBankAccount(Request $request)
    {
        try {
            $input = $request->all();

            $validator = Validator::make($input, [
                'additional_document_front' => 'required|mimes:jpg,png,jpeg',
                'additional_document_back' => 'required|mimes:jpg,png,jpeg',
                'document_front' => 'required|mimes:jpg,png,jpeg',
                'document_back' => 'required|mimes:jpg,png,jpeg',
            ],
                [
                    'additional_document_front.required' => 'The additional document front is required',
                    'additional_document_back.required' => 'The additional document back is required',
                    'document_front.required' => 'The document front is required',
                    'document_back.required' => 'The document back is required',
                ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            StripePhp\Stripe::setApiKey(env('STRIPE_SECRET'));

            if(!empty($input['additional_document_front'])){
                $additional_document_front = imagecompressupload($input['additional_document_front'], 'stripe_document');

                $path = public_path("/images/stripe_document/".$additional_document_front);

                $fp = fopen($path, 'r');
                $file_id = StripePhp\File::create([
                    'purpose' => 'identity_document',
                    'file' => $fp
                ]);
                if(!empty($file_id)){
                    $input['individual']['verification']['additional_document']['front'] = $file_id['id'];
                }else{
                    unset($input['individual']['verification']['additional_document']['front']);
                }
            }

            if(!empty($input['additional_document_back'])){
                $additional_document_back = imagecompressupload($input['additional_document_back'], 'stripe_document');

                $path = public_path("/images/stripe_document/".$additional_document_back);
                $fp = fopen($path, 'r');
                $file_id = StripePhp\File::create([
                    'purpose' => 'identity_document',
                    'file' => $fp
                ]);

                if(!empty($file_id)){
                    $input['individual']['verification']['additional_document']['back'] = $file_id['id'];
                }else{
                    unset($input['individual']['verification']['additional_document']['back']);
                }
            }

            if(!empty($input['document_front'])){
                $document_front = imagecompressupload($input['document_front'], 'stripe_document');

                $path = public_path("/images/stripe_document/".$document_front);
                $fp = fopen($path, 'r');
                $file_id = StripePhp\File::create([
                    'purpose' => 'identity_document',
                    'file' => $fp
                ]);
                if(!empty($file_id)){
                    $input['individual']['verification']['document']['front'] = $file_id['id'];
                }else{
                    unset($input['individual']['verification']['document']['front']);
                }
            }

            if(!empty($input['document_back'])){
                $document_back = imagecompressupload($input['document_back'], 'stripe_document');

                $path = public_path("/images/stripe_document/".$document_back);
                $fp = fopen($path, 'r');
                $file_id = StripePhp\File::create([
                    'purpose' => 'identity_document',
                    'file' => $fp
                ]);
                if(!empty($file_id)){
                    $input['individual']['verification']['document']['back'] = $file_id['id'];
                }else{
                    unset($input['individual']['verification']['document']['back']);
                }
            }

            $account = StripePhp\Account::update(
                auth()->user()->stripe_account_id,
                ['individual' =>  $input['individual']]
            );

            if (!empty($account)) {
                $status = $account['individual']['verification']['status'];
                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] ='Your account status is '.$status;
                return response()->json($this->WebApiArray);
            }


        } catch (NotFoundException $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }  catch (BadRequestException $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        } catch (InvalidRequestException $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        } catch (CardErrorException $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function payout(Request $request) {

        DB::beginTransaction();
        try {

            $input = $request->all();

            $validator = Validator::make($input, [
                'amount' => 'required'
            ]);

            if($validator->fails())
            {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            StripePhp\Stripe::setApiKey(env('STRIPE_SECRET'));

            $account = [];
            if (!empty(auth()->user()->stripe_account_id)) {

                $account = StripePhp\Account::retrieve(
                    auth()->user()->stripe_account_id
                );
            };

            if (count($account) == 0) {
                throw new Exception('Account not added yet',1);
            }

            if ($input['amount'] == 0) {
                throw new Exception("Amount is Not Valid", 1);
            }

            if ($input['amount'] > auth()->user()->remaining_balance) {
                throw new Exception("You have insufficient funds", 1);
            }


            $withdrawal = new Withdrawal();
            $withdrawal['withdrawal_uuid'] =  Str::uuid()->toString();
            $withdrawal['amount'] =  $input['amount'];
            $withdrawal['user_id'] =  auth()->id();

            $transfer = StripePhp\Transfer::create([
                'amount' => $input['amount']*100,
                'currency' => 'eur',
                'destination' => auth()->user()->stripe_account_id,
                'transfer_group' => $withdrawal['withdrawal_uuid'],
            ]);

            if (!empty($transfer)) {
                $withdrawal['transfer_id'] =  $transfer['id'];
                $withdrawal['transaction_id'] =  $transfer['balance_transaction'];
                $withdrawal['status'] =  1;
                $withdrawal->save();
                DB::commit();
                $amount = $input['amount'];

                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] ='Withdrawal Successfully for amount '.$amount;
                return response()->json($this->WebApiArray);
            }

        } catch (NotFoundException $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }  catch (BadRequestException $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        } catch (InvalidRequestException $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        } catch (CardErrorException $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function addCard(Request $request)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'card_holder' => 'required',
                'card_number' => 'required|max:20',
                'month' => 'required|min:2|max:2',
                'year' =>   'required|max:4|min:4',
                'cvc' =>    'required|max:4',
            ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }
            $input = $request->all();
            $stripe = Stripe::make(env('STRIPE_SECRET'));
            $stripe->setApiKey(env('STRIPE_SECRET'));
            if (!auth()->user()->stripe_id) {

                $customer = $stripe->customers()->create([
                    'email' => auth()->user()->email
                ]);

                $customer_id = $customer['id'];
                auth()->user()->stripe_id = $customer_id;
                auth()->user()->save();
                DB::commit();
            }
            //Create Stripe Token
            $token = $stripe->tokens()->create([
                'card' => [
                    'number'    => $input['card_number'],
                    'exp_month' => $input['month'],
                    'exp_year'  => $input['year'],
                    'cvc'       => $input['cvc'],
                ],
            ]);
            $stripe_cards = $stripe->cards()->all(auth()->user()->stripe_id);
            $card_fingerprints = array();
            if(count($stripe_cards) > 0 )
            {
                $card_fingerprints = array_column($stripe_cards['data'], 'fingerprint');
            }
            if(in_array($token['card']['fingerprint'], $card_fingerprints))
            {
                throw new Exception('This card is already added.', 1);
            }else{
                $card = $stripe->cards()->create(auth()->user()->stripe_id, $token['id']);
                if ($card) {
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] ='Card Save Successfully';
                    return response()->json($this->WebApiArray);
                }
                throw new Exception('Card could not be saved! Please try again.', 1);
            }
        } catch (NotFoundException $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }  catch (BadRequestException $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        } catch (InvalidRequestException $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        } catch (CardErrorException $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function deleteCard(Request $request) {
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }

            $validator = Validator::make($request->all(), [
                'card_id' => 'required'
            ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }
            $input = $request->all();
            $stripe = Stripe::make(env('STRIPE_SECRET'));
            $stripe->setApiKey(env('STRIPE_SECRET'));
            if(!empty(auth()->user()->stripe_id))
            {
                $card = $stripe->cards()->find(auth()->user()->stripe_id, $input['card_id']);
                if(!empty($card)){
                    $deleted_card = $stripe->cards()->delete(auth()->user()->stripe_id, $input['card_id']);
                    if(!empty($deleted_card)){
                        $this->WebApiArray['status'] = true;
                        $this->WebApiArray['message'] = 'Card deleted successfully.';
                        return response()->json($this->WebApiArray);
                    }
                    throw new Exception("Error occured while deleting card! Please try again.", 1);
                }
                throw new Exception("Card not exist.", 1);
            }
            throw new Exception("Error Processing Request", 1);
        }catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        } catch (\Cartalyst\Stripe\Exception\CardErrorException $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        } catch (\Cartalyst\Stripe\Exception\MissingParameterException $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }
}
