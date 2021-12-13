<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB, Exception,File;
use Carbon\Carbon;
use App\Models\Website;
use App\Models\WebsiteStore;
use App\Models\WebsiteStoreProduct;
use App\Models\ThemeColor;
use Illuminate\Support\Str;


class WebsiteStoreController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    { 
        
        /*$color_arr = array();
        $color_arr = [
            [
            "R" =>  210,
            "G" =>  47,
            "B" =>  37,
            Red 
            ],
            [
            "R" => 255,
            "G" => 215,
            "B" => 3
            ],
            [
            "R" => 76,
            "G" => 173,
            "B" => 73
            ],
            [
            "R" => 16,
            "G" => 181,
            "B" => 223
            ],
            [
            "R" => 0,
            "G" => 117,
            "B" => 193, 
            ],
            [
            "R" => 126,
            "G" => 84,
            "B" => 198, 
            ],
            [
            "R" => 241,
            "G" => 18,
            "B" => 134, 
            ],
            [
            "R" => 185,
            "G" => 132,
            "B" => 77,
            ],
            [
            "R" => 111,
            "G" => 111,
            "B" => 111,
            ],
            [
            "R" => 0,
            "G" => 0,
            "B" => 0,
            ],
        ];
        foreach ($color_arr as $key => $value) {
            // $color_code['color_code'] = $value;
            ThemeColor::create([ 'theme_color_code_id' => Str::uuid()->toString(), 'color_code' => json_encode($value)]);
        }*/
        
        /*$theme_colors = ThemeColor::pluck('color_code','theme_color_code_id')->toArray();
         echo "<pre>"; print_r($theme_colors);
         exit;
        foreach ($theme_colors as $key => $value) {
            echo "<pre>"; print_r($key);
            echo "<pre>"; print_r($value);
            //$testing = json_decode($value,true);

           echo "<pre>"; print_r($testing['color_code']['R']);
            echo "<pre>"; print_r($testing['color_code']['G']);
            echo "<pre>"; print_r($testing['color_code']['B']);
        }*/
        
        //exit;
        $websites = Website::with(['website_creator','website_categories','website_templates'])->orderBy('name','ASC')->paginate(25);
        return view('Backend.websites.index',compact('websites'))
            ->with('i', ($request->input('page', 1) - 1) * 25);
    }


    public function fetchWebsiteOnlineStore(Request $request, Website $website)
    {
        $website_stores = WebsiteStore::where('website_id',$website->id)->orderBy('title','ASC')->paginate(25);
        return view('Backend.websites.website_stores',compact('website_stores','website'))
            ->with('i', ($request->input('page', 1) - 1) * 25);
    }

    public function fetchWebsiteOnlineStoreProduct(Request $request, Website $website, WebsiteStore $store)
    {
        $onlinestore_products = WebsiteStoreProduct::with(['websites','website_product_creators','website_stores','website_store_categories'])->where(['website_id' => $website->id, 'website_store_id' => $store->id  ])->orderBy('title','ASC')->paginate(25);
        return view('Backend.websites.website_products',compact('onlinestore_products','website','store'))
            ->with('i', ($request->input('page', 1) - 1) * 25);
    }

    
    public function fetchWebsiteEcommerceStore(Request $request)
    {
        $website_stores = WebsiteStore::with(['websites','website_store_creators'])->orderBy('title','ASC')->paginate(25);
        return view('Backend.websites.website_stores',compact('website_stores'))
            ->with('i', ($request->input('page', 1) - 1) * 25);
    }

    public function fetchProduct(Request $request)
    {
        $onlinestore_products = WebsiteStoreProduct::with(['websites','website_product_creators','website_stores','website_store_categories'])->orderBy('title','ASC')->paginate(25);
        return view('Backend.websites.website_products',compact('onlinestore_products'))
            ->with('i', ($request->input('page', 1) - 1) * 25);
    }

    

    
}
