<?php

namespace App\Http\Controllers\Html;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Illuminate\Support\Facades\DB;
use Exception;
use Carbon\Carbon;

class Htmlcontroller extends Controller
{

	public function blankpage()
    {
    	return view('Html.blankpage');
    }

    
    public function dashbaord()
    {
    	return view('Html.dashboard');
    }

    public function subscription()
    {
    	return view('Html.subscription');
    }
    
     public function video_archive()
    {
    	return view('Html.video_archive');
    }
    
      public function payment()
    {
    	return view('Html.payment');
    }

      public function my_orders()
    {
    	return view('Html.my_orders');
    }
    
       public function invoice_details()
    {
    	return view('Html.invoice_details');
    }
    
      public function testimonials()
    {
    	return view('Html.testimonials');
    }
    
      public function messenger()
    {
    	return view('Html.messenger');
    }
    
      public function agenda_calender()
    {
    	return view('Html.agenda_calender');
    } 
    
       public function coaches()
    {
    	return view('Html.coaches');
    }
    
      public function user_list()
    {
    	return view('Html.user_list');
    } 
    
      public function coach_detail()
    {
    	return view('Html.coach_detail');
    } 


    public function storeCountry(Request $request)
    {
        DB::beginTransaction();
        try {
            $jsonString = file_get_contents(base_path('Countries-States-Cities-database-master/countries.json'));
            $country_data = json_decode($jsonString, true);
            foreach ($country_data['countries'] as $country_data_key => $country_data_value) {
                $country_data_value['title'] = $country_data_value['name'];
                $country_data_value['phone_code'] = $country_data_value['phoneCode'];
                $country_data_value['ISO_code'] = $country_data_value['sortname'];
                $country_data_value['status'] = 1;
                $country = new Country;
                if($country->fill($country_data_value)->save()){
                }else{
                    throw new Exception("Error processing request.", 1);
                }
            }
            DB::commit();
            exit('insert country db.');
        } catch (Exception $e) {
            DB::rollback();
            echo "<pre>"; print_r($e->getMessage()); exit;
        }  
    }



}
