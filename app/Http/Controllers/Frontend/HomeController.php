<?php
namespace App\Http\Controllers\Frontend;

use App\Models\Message;
use App\Models\Video;
use Aws\S3\S3Client;
use http\Client\Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\UserFolder;
use DB, Exception, Validator;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('Frontend.index');
    }

    public function createFolder(Request $request)
    {
        DB::beginTransaction();
        try{
            $input = $request->all();
            if($request->ajax()){
                if(!$request->isMethod('post'))
                {
                    throw new Exception("Request not allowed.", 1);
                }
                $validator = Validator::make($input,[
                    'folder_type' => 'required|in:1,2',
                    'title'   =>  'required|not_regex:/^[!@#$%^’&*(),.?\":{}<>]+$/|unique:user_folders,title,NULL,id,folder_type,'.$input['folder_type'].',user_id,'.auth()->id(),
                    ],
                    [
                        'title.required' => 'The folder name is required.',
                        'title.regex' => 'The folder name is invalid.',
                        'folder_type.in' => 'The folder type is invalid.',
                        'folder_type.required' => 'The folder type is required.',
                    ]);
                if ($validator->fails()) {
                    throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
                }
                if(strtolower($input['title']) == 'photos' || strtolower($input['title']) == 'videos')
                {
                    throw new Exception("The folder name is invalid.", 1);
                }
                
                $parent_folder =UserFolder::whereNull('user_folder_id')->where('user_id',auth()->id())->first();
                if(empty($parent_folder))
                {
                    $user_slug = auth()->user()->username.'_'.auth()->user()->user_uuid;
                    $parent_folder = new UserFolder;
                    if($parent_folder->fill(['title' => $user_slug, 'user_id' => auth()->id(), 'slug' => $user_slug ])->save()){
                        $parent_directory = AwsBucketCreateUserDirectory($parent_folder->slug);
                        if(empty($parent_directory))
                        {
                            throw new Exception("Error occured! Please try again.", 1); 
                        }
                    }else{
                        throw new Exception("Error occured! Please try again.", 1);   
                    }
                }
                /*$slug = preg_replace("/-$/","",preg_replace('/[^a-z0-9]+/i', "-", strtolower($input['title'])));
                $checkSlug = UserFolder::whereRaw("( slug LIKE '%$slug%' AND user_id = ".auth()->id()." AND folder_type = ".$input['folder_type']."  )")->orderBy('slug','DESC')->first();
                if(empty($checkSlug)){
                    $input['slug'] = $slug;
                }else{
                    $slug_arr = explode('-',$checkSlug->slug);
                    $slugindex = end($slug_arr);
                    $slug_counter = 1;
                    if(is_numeric($slugindex)){
                        $slug_counter = $slugindex+1; 
                        array_pop($slug_arr);
                    }
                    $slug = implode('-', $slug_arr).'-'.$slug_counter;
                    $input['slug'] = $slug;
                }*/
                $folder = new UserFolder;
                $input['user_folder_id'] = $parent_folder->id;
                $input['user_id'] = auth()->id();
                if($folder->fill($input)->save()){
                    $child_directory = AwsBucketCreateUserChildDirectory($parent_folder->slug,$folder->slug,$input['folder_type']);
                    if(empty($child_directory))
                    {
                        throw new Exception("Error occured! Please try again.", 1); 
                    }
                    DB::commit();
                    $this->WebApiArray['error'] = false;
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Folder created successfully.';
                    $this->WebApiArray['data']['result'] = $folder;
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("Http request not allowed.", 1);  
            }
            throw new Exception("Http request not allowed.", 1);  
        }catch(Exception $e){
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function updateFolder(Request $request)
    {
        DB::beginTransaction();
        try{
            $input = $request->all();
            if($request->ajax()){
                if(!$request->isMethod('post'))
                {
                    throw new Exception("Request not allowed.", 1);
                }
                $validator = Validator::make($input,[
                    'folder_id' => 'required|exists:user_folders,id',
                    'folder_type' => 'required|in:1,2',
                    'title'   =>  'required|not_regex:/^[!@#$%^’&*(),.?\":{}<>]+$/|unique:user_folders,title,'.$input['folder_id'].',id,folder_type,'.$input['folder_type'].',user_id,'.auth()->id(),
                    ],
                    [
                        'title.required' => 'The folder name is required.',
                        'title.regex' => 'The folder name is invalid.',
                        'folder_type.in' => 'The folder type is invalid.',
                        'folder_type.required' => 'The folder type is required.',
                    ]);
                if ($validator->fails()) {
                    throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
                }
                if(strtolower($input['title']) == 'photos' || strtolower($input['title']) == 'videos')
                {
                    throw new Exception("The folder name is invalid.", 1);
                }

                $folder =UserFolder::whereNotNull('user_folder_id')->where(['user_id'=>auth()->id(), 'id'=>$input['folder_id'], 'folder_type' => $input['folder_type']])->first();

                if(!empty($folder)) {
                    if ($folder->fill($input)->save()) {

                        DB::commit();
                        $this->WebApiArray['error'] = false;
                        $this->WebApiArray['status'] = true;
                        $this->WebApiArray['message'] = 'Folder updated successfully.';
                        $this->WebApiArray['data']['result'] = $folder;
                        return response()->json($this->WebApiArray);
                    }
                    throw new Exception("Error occured! Please try again.", 1);
                }
                throw new Exception("Error occured! Please try again.", 1);
            }
            throw new Exception("Http request not allowed.", 1);
        }catch(Exception $e){
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

}
