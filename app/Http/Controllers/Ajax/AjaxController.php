<?php

namespace App\Http\Controllers\Ajax;

use App\Models\UserDetail;
use App\Models\Zipcode;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB,Exception;
use App\Models\User;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Category;
use App\Models\StaticPage;
use App\Models\MailConfiguration;
use App\Models\Template;
use App\Models\Section;
use App\Models\SitePage;
use File;
use App\Models\Website;
use App\Models\WebsiteStoreProduct;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Tag;
use App\Models\UserFolder;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Pion\Laravel\ChunkUpload\Handler\AbstractHandler;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;

use function GuzzleHttp\Promise\all;


class AjaxController extends Controller
{	
	public function __construct()
    {
        // Invoke parent
        parent::__construct();
    }

	public function DeleteTemplateImage(Request $request, $id)
    {
    	try{
    		if($request->ajax()){
    			$template = Template::where('id',$id)->first();
    			if(!empty($template)){
    				$oldimage = $template->featured_image;
    				$template->featured_image = NULL;
    				$template->save();
    				File::delete(public_path("/images/templates/".$oldimage));
    				return response()->json(['status' => true, 'message' => "Image deleted successfully."]);
    			}
	    		throw new Exception("No item found.", 1);
	    	}
	    	throw new Exception("HTTP Error", 1);
	    	
    	}catch(Exception $e){
    		return response()->json(['status' => false, 'message' => $e->getMessage()]);
    	}
    }

    public function checkUniqueTemplate(Request $request, $id = null)
    {
    	try{
    		$input = $request->all();
    		if($request->ajax()){
    			$templates = (new Template)->newQuery();
	    		if(isset($input['title'])){
	    			$templates->where('title',$input['title']);
	    		}
	    		if(!empty($id)){
	    			$templates->where('id', '!=', $id);
	    		}
	    		$template =  $templates->first();
	    		if(empty($template))
	    		{
    				return response()->json(true);
	    		}
	    		throw new Exception("Error Processing Request", 1);
	    	}
	    	throw new Exception("Error Processing Request", 1);
	    	
    	}catch(Exception $e){
    		return response()->json(false);
    	}
    }

    public function checkUniquePermission(Request $request, $id = null)
    {
    	try{
    		$input = $request->all();
    		if($request->ajax()){
    			$permission = (new Permission)->newQuery();
	    		if(isset($input['name'])){
	    			$permission->where('name',$input['name']);
	    		}
	    		if(!empty($id)){
	    			$permission->where('id', '!=', $id);
	    		}
	    		$permissions =  $permission->first();
	    		if(empty($permissions))
	    		{
    				return response()->json(true);
	    		}
	    		throw new Exception("Error Processing Request", 1);
	    	}
	    	throw new Exception("Error Processing Request", 1);
	    	
    	}catch(Exception $e){
    		return response()->json(false);
    	}
    }


    


    public function checkUniqueRole(Request $request, $id = null)
    {
    	try{
    		$input = $request->all();
    		if($request->ajax()){
    			$role = (new Role)->newQuery();
	    		if(isset($input['name'])){
	    			$role->where('name',$input['name']);
	    		}
	    		if(!empty($id)){
	    			$role->where('id', '!=', $id);
	    		}
	    		$roles =  $role->first();
	    		if(empty($roles))
	    		{
    				return response()->json(true);
	    		}
	    		throw new Exception("Error Processing Request", 1);
	    	}
	    	throw new Exception("Error Processing Request", 1);
	    	
    	}catch(Exception $e){
    		return response()->json(false);
    	}
    }


    public function checkUniqueUser(Request $request, $id = null)
    {
    	try{
    		$input = $request->all();
    		if($request->ajax()){
    			$users = (new User)->newQuery();
	    		if(isset($input['email'])){
	    			$users->where('email',$input['email']);
	    		}
	    		if(!empty($id)){
	    			$users->where('id', '!=', $id);
	    		}
	    		$user =  $users->first();
	    		if(empty($user))
	    		{
    				return response()->json(true);
	    		}
	    		throw new Exception("Error Processing Request", 1);
	    	}
	    	throw new Exception("Error Processing Request", 1);
	    	
    	}catch(Exception $e){
    		return response()->json(false);
    	}
    }

    public function checkUniqueUserByMobile(Request $request, $id = null)
    {
        try{
            $input = $request->all();
            if($request->ajax()){
                $userDetails = (new UserDetail())->newQuery();
                if (isset($input['mobile']) && isset($input['mobile_code']) ) {
                    $userDetails->where(['mobile' => $input['mobile'], 'mobile_code_id' => $input['mobile_code'] ]);
                }else{
                    throw new Exception("Error Processing Request", 1);
                }
                if(!empty($id)){
                    $userDetails->where('user_id', '!=', $id);
                }
                $userDetail =  $userDetails->first();
                if(empty($userDetail))
                {
                    return response()->json(true);
                }
                throw new Exception("Error Processing Request", 1);
            }
            throw new Exception("Error Processing Request", 1);

        }catch(Exception $e){
            return response()->json(false);
        }
    }

    public function validateZipcode(Request $request)
    {
        try{
            $input = $request->all();
            if($request->ajax()){
                $zipCode = (new Zipcode())->newQuery();
                if (!empty($input['country_id']) && !empty($input['zip_code']) ) {
                    $zipCode->where(['country_id' =>$input['country_id'], 'zip_code' => $input['zip_code'] ]);
                }else{
                    throw new Exception("Error Processing Request", 1);
                }

                if (!empty($input['state_id'])) {
                    $zipCode->where(['state_id' => $input['state_id']]);
                }
                if (!empty($input['city_id'])) {
                    $zipCode->where(['city_id' => $input['city_id']]);
                }
                $zip =  $zipCode->first();
                if(!empty($zip))
                {
                    return response()->json(true);
                }
                throw new Exception("Error Processing Request", 1);
            }
            throw new Exception("Error Processing Request", 1);

        }catch(Exception $e){
            return response()->json(false);
        }
    }

    public function checkUniqueStaticPageTitle(Request $request, $id = null)
    {

    	try{
    		$input = $request->all();
    		if($request->ajax()){
    			$staticPages = (new StaticPage())->newQuery();
	    		if(isset($input['title'])){
                    $staticPages->where('title',$input['title']);
	    		}
	    		if(!empty($id)){
                    $staticPages->where('id','!=',$id);
	    		}

                $staticPage =  $staticPages->first();
	    		if(empty($staticPage))
	    		{
    				return response()->json(true);
	    		}
	    		throw new Exception("Error Processing Request", 1);
	    	}
	    	throw new Exception("Error Processing Request", 1);

    	}catch(Exception $e){
    		return response()->json(false);
    	}
    }

    public function checkUniqueCategory(Request $request, $id = null)
    {
    	try{
    		$input = $request->all();
    		if($request->ajax()){
    			$categories = (new Category)->newQuery();
	    		if(isset($input['name'])){
	    			$categories->where('name',$input['name']);
	    		}
	    		if(!empty($id)){
	    			$categories->where('id', '!=', $id);
	    		}
	    		$category =  $categories->first();
	    		if(empty($category))
	    		{
    				return response()->json(true);
	    		}
	    		throw new Exception("Error Processing Request", 1);
	    	}
	    	throw new Exception("Error Processing Request", 1);
	    	
    	}catch(Exception $e){
    		return response()->json(false);
    	}
    }


    public function checkUniqueStaticPage(Request $request, $id = null)
    {
    	try{
    		$input = $request->all();
    		if($request->ajax()){
    			$staticpages = (new StaticPage)->newQuery();
	    		if(isset($input['name'])){
	    			$staticpages->where('name',$input['name']);
	    		}
	    		if(!empty($id)){
	    			$staticpages->where('id', '!=', $id);
	    		}
	    		$staticpage =  $staticpages->first();
	    		if(empty($staticpage))
	    		{
    				return response()->json(true);
	    		}
	    		throw new Exception("Error Processing Request", 1);
	    	}
	    	throw new Exception("Error Processing Request", 1);
	    	
    	}catch(Exception $e){
    		return response()->json(false);
    	}
    }

    public function checkUniqueEmailConfiguration(Request $request, $id = null)
    {
    	try{
    		$input = $request->all();
    		if($request->ajax()){
    			$configurations = (new MailConfiguration)->newQuery();
	    		if(isset($input['name'])){
	    			$configurations->where('name',$input['name']);
	    		}
	    		if(!empty($id)){
	    			$configurations->where('id', '!=', $id);
	    		}
	    		$configuration =  $configurations->first();
	    		if(empty($configuration))
	    		{
    				return response()->json(true);
	    		}
	    		throw new Exception("Error Processing Request", 1);
	    	}
	    	throw new Exception("Error Processing Request", 1);
	    	
    	}catch(Exception $e){
    		return response()->json(false);
    	}
    }


    public function fetchSubCategory(Request $request, $category_id, $type)
    {
    	try{
    		$input = $request->all();
    		if($request->ajax()){
    			$ajaxcategories = (new Category)->newQuery();
		        if(!empty($category_id)){
    				$ajaxcategories->where('parent_id',$category_id);
		        }
		        $ajaxcategory = $ajaxcategories->orderBy('name','ASC')->get();
    			if(count($ajaxcategory) > 0)
	    		{
	    			if( !empty($type) &&  ($type == 'parent' ||  $type == 'child')){
						$html = view("Backend.category.ajax.child",compact('ajaxcategory','type'))->render();
    					return response()->json(['status' => true, 'html' => $html]);
					}
	    			throw new Exception("Error Processing Request", 1);
	    		}
	    		throw new Exception("Error Processing Request", 1);
	    	}
	    	throw new Exception("Error Processing Request", 1);
    	}catch(Exception $e){
    		return response()->json(['status' => false]);
    	}

    }

    public function checkUniqueSectionTitle(Request $request, $id = null)
    {
    	try{
    		$input = $request->all();
    		if($request->ajax()){
    			$sections = (new Section)->newQuery();
	    		if(isset($input['title'])){
	    			$sections->where('title',$input['title']);
	    		}
	    		if(!empty($id)){
	    			$sections->where('id', '!=', $id);
	    		}
	    		$section =  $sections->first();
	    		if(empty($section))
	    		{
    				return response()->json(true);
	    		}
	    		throw new Exception("Error Processing Request", 1);
	    	}
	    	throw new Exception("Error Processing Request", 1);
	    	
    	}catch(Exception $e){
    		return response()->json(false);
    	}
    }

    public function checkUniqueSitepageName(Request $request, $id = null)
    {
    	try{
    		$input = $request->all();
    		if($request->ajax()){
    			$sitepages = (new SitePage)->newQuery();
	    		if(isset($input['name'])){
	    			$sitepages->where('name',$input['name']);
	    		}
	    		if(!empty($id)){
	    			$sitepages->where('id', '!=', $id);
	    		}
	    		$sitepage =  $sitepages->first();
	    		if(empty($sitepage))
	    		{
    				return response()->json(true);
	    		}
	    		throw new Exception("Error Processing Request", 1);
	    	}
	    	throw new Exception("Error Processing Request", 1);
	    	
    	}catch(Exception $e){
    		return response()->json(false);
    	}
    }

    public function checkUniqueDefaultSectionTitle(Request $request, $template_id)
    {
    	try{
    		$input = $request->all();
    		if($request->ajax()){
    			$sections = (new Section)->newQuery();
	    		if(isset($input['title'])){
	    			$sections->where('title',$input['title']);
	    		}
	    		$sections->where('template_id', $template_id);
	    		$section =  $sections->first();
	    		if(empty($section))
	    		{
    				return response()->json(true);
	    		}
	    		throw new Exception("Error Processing Request", 1);
	    	}
	    	throw new Exception("Error Processing Request", 1);
	    	
    	}catch(Exception $e){
    		return response()->json(false);
    	}
    }


    public function checkUniqueProductName(Request $request, $websiteid, $storeid)
    {
    	try{
    		$input = $request->all();
    		if($request->ajax()){
    			$website = Website::with('latest_website_stores')
			                      ->whereHas('latest_website_stores',function($query) use($storeid ){
			                        $query->where('web_store_id',$storeid);
			                      })
			                      ->where('website_encoded_id',$websiteid)
			                      ->first();
                if(!empty($website)){
                	$products = (new WebsiteStoreProduct)->newQuery();
                	$products->where('website_id',$website->id)->where('website_store_id',$website->latest_website_stores->id);
                	if(isset($input['title'])){
		    			$products->where('title',$input['title']);
		    		}
		    		if(isset($input['sku'])){
		    			$products->where('sku',$input['sku']);
		    		}
                	$product =  $products->first();
                	if(empty($product))
		    		{
	    				return response()->json(true);
		    		}
		    		throw new Exception("Error Processing Request", 1);
                }
                throw new Exception("Error Processing Request", 1);
	    	}
	    	throw new Exception("Error Processing Request", 1);	
    	}catch(Exception $e){
    		return response()->json(false);
    	}
    }


    public function checkUniqueEditProductName(Request $request, $websiteid, $storeid, $productid)
    {
    	try{
    		$input = $request->all();
    		if($request->ajax()){
    			$website = Website::with('latest_website_stores')
			                      ->whereHas('latest_website_stores',function($query) use($storeid ){
			                        $query->where('web_store_id',$storeid);
			                      })
			                      ->where('website_encoded_id',$websiteid)
			                      ->first();
                if(!empty($website)){
                	$products = (new WebsiteStoreProduct)->newQuery();
                	$products->where('website_id',$website->id)->where('website_store_id',$website->latest_website_stores->id);
                	if(isset($input['title'])){
		    			$products->where('title',$input['title']);
		    		}
		    		$products->where('webproduct_id', '!=', $productid);
                	$product =  $products->first();
                	if(empty($product))
		    		{
	    				return response()->json(true);
		    		}
		    		throw new Exception("Error Processing Request", 1);
                }
                throw new Exception("Error Processing Request", 1);
	    	}
	    	throw new Exception("Error Processing Request", 1);	
    	}catch(Exception $e){
    		return response()->json(false);
    	}
    }


    public function fetchCountryState(Request $request)
    {
    	try{
    		$input = $request->all();
    		if($request->ajax()){
	    		if(isset($input['country_id'])&& !empty($input['country_id'])){
	    			$states = (new State)->newQuery();
	    			$states->where('country_id',$input['country_id']);
	    			$states = $states->get();
	    			if($states->count() > 0)
	    			{
	    				$this->WebApiArray['status'] = true;
	                    $this->WebApiArray['error'] = false;
	                    $this->WebApiArray['message'] = 'Record found.';
	                    $this->WebApiArray['data']['result'] = $states;
	                    return response()->json($this->WebApiArray);
	    			}
	    			throw new Exception("No record found.", 1);
	    		}
	    		throw new Exception("Country doesn't valid.", 1);
	    	}
	    	throw new Exception("Http Request not Allowed.", 1);
	    	
    	}catch(Exception $e){
    		$this->WebApiArray['message'] = $e->getMessage();
    		return response()->json($this->WebApiArray);
    	}
    }

    public function fetchStateCity(Request $request)
    {
    	try{
    		$input = $request->all();
    		if($request->ajax()){
	    			if(isset($input['state_id']) && !empty($input['state_id'])){
	    				$cities = (new City)->newQuery();
		    			$cities->where('state_id',$input['state_id']);
		    			$cities = $cities->get();
		    			if($cities->count() > 0)
		    			{
		    				$this->WebApiArray['status'] = true;
		                    $this->WebApiArray['error'] = false;
		                    $this->WebApiArray['message'] = 'Record found.';
		                    $this->WebApiArray['data']['result'] = $cities;
		                    return response()->json($this->WebApiArray);
		    			}
		    			throw new Exception("No record found.", 1);
	    			}
	    			throw new Exception("State doesn't valid.", 1);
	    	}
	    	throw new Exception("Http Request not Allowed.", 1);
	    	
    	}catch(Exception $e){
    		$this->WebApiArray['message'] = $e->getMessage();
    		return response()->json($this->WebApiArray);
    	}
    }


    public function checkUniqueTagTitle(Request $request, $id = null)
    {
    	try{
    		$input = $request->all();
    		if($request->ajax()){
    			$tags = (new Tag)->newQuery();
	    		if(isset($input['title'])){
	    			$tags->where('title',$input['title']);
	    		}
	    		if(!empty($id)){
	    			$tags->where('id', '!=', $id);
	    		}
	    		$tag =  $tags->first();
	    		if(empty($tag))
	    		{
    				return response()->json(true);
	    		}
	    		throw new Exception("Error Processing Request", 1);
	    	}
	    	throw new Exception("Error Processing Request", 1);
	    	
    	}catch(Exception $e){
    		return response()->json(false);
    	}
    }

    public function firstLogin(Request $request)
    {
        try{
            if($request->ajax()){
                $user = User::find(auth()->id());
                $user->product_tour = 1;
                $user->save();
                if($user) {
                    return response()->json(true);
                }
                throw new Exception("Error Processing Request", 1);
            }
            throw new Exception("Error Processing Request", 1);

        }catch(Exception $e){
            return response()->json(false);
        }
    }


    public function checkUniqueFolder(Request $request, $type)
    {
        try{
            $input = $request->all();
            if($request->ajax()){
                $folders = (new UserFolder)->newQuery();
                if(isset($input['title'])){
                    $folders->where('title',$input['title']);
                }
                if(isset($input['id'])){
                    $folders->where('id','!=',$input['id']);
                }
                $folders->where('folder_type',$type)->where('user_id', auth()->id());
                $folder =  $folders->first();
                if(empty($folder))
                {
                    if(strtolower($input['title']) == 'photos' || strtolower($input['title']) == 'videos')
                    {
                        throw new Exception("Error Processing Request", 1);
                    }
                    return response()->json(true);
                }
                throw new Exception("Error Processing Request", 1);
            }
            throw new Exception("Error Processing Request", 1);
            
        }catch(Exception $e){
            return response()->json(false);
        }
    }

    public function storeVideo(Request $request)
    {
        ini_set("memory_limit", "-1");
        ini_set ('max_execution_time', 0);
        DB::beginTransaction();
        try {

            $input = $request->all();

            // create the file receiver
            $receiver = new FileReceiver("file", $request, HandlerFactory::classFromRequest($request));
            // check if the upload is success, throw exception or return response you need
            if ($receiver->isUploaded() === false) {
                throw new Exception("Error Processing Request", 1);
            }

            // receive the file
            $save = $receiver->receive();
            // check if the upload has finished (in chunk mode it will send smaller files)
            if ($save->isFinished()) {
               $fileData =  AwsChunkVideoFileUpload($save->getFile());
                return response()->json([
                    "data" => $fileData,
                    'status' => true
                ]);
            }
            // we are in chunk mode, lets send the current progress
            /** @var AbstractHandler $handler */
            $handler = $save->handler();
            return response()->json([
                "done" => $handler->getPercentageDone(),
                'status' => true
            ]);
        } catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

}


