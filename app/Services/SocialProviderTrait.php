<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\User;
use App\Models\SocialProvider;
use Socialite;

trait SocialProviderTrait
{

    
    public function redirectToProvider($provider)
    {
      return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback(Request $request, $provider)
    {
      try {
        $input = $request->all();
        if(empty($input['code'])){
          throw new Exception("Access denied.", 1);
        }
        $social_user = Socialite::driver($provider)->user();
        $authUser = $this->findOrCreateUser($social_user, $provider);
        if(!empty($authUser)){
          auth()->guard('web')->login($authUser, true);
          return redirect($this->redirectTo)->withSuccess("You have successfully Logged In.");
        }
        throw new Exception("You are not authorized to access this panel.", 1);
      } catch (Exception $e) {
        return redirect()->route('customer.login')->withError($e->getMessage());
      }
   }
   
   
   public function findOrCreateUser($socialuser, $provider)
   {
      DB::beginTransaction();
      try {
          $dataArr = array();
          $account = SocialProvider::with('users')->where(['provider_name' => $provider, 'provider_id' => $socialuser->id ])->first();
          if(!empty($account)){
            return $account->users;
          }
          $dataArr['email'] = !empty($socialuser->email) ? $socialuser->email : $socialuser->id."@".$provider.".com";
          $dataArr['name'] = !empty($socialuser->name) ? $socialuser->name : NULL;
          $user = User::where('email',$dataArr['email'])->first();
          if(!empty($user)){
            if($user->IsAdmin() || !$user->IsActive() ){
              throw new Exception("You are not authorized to access this panel.", 1); 
            }
            return $user;
          }
          $user = new User;
          $dataArr['status'] = 1;
          $dataArr['confirmed'] = 1;
          if($user->fill($dataArr)->save()){
            $user->roles()->attach(3);
            $user->social_providers()->create([
              'provider_id'   => $socialuser->id,
              'provider_name' => $provider,
            ]);
            DB::commit();
            return $user;
          }
          throw new Exception("Error Processing Request", 1);
      } catch (Exception $e) {
        DB::rollback();
        return "";
      }
   }
    


    

}