<?php

namespace App\Services\Api\v3;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception, Validation, Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Video;
use App\Models\UserFolder;
use Illuminate\Support\Facades\File;

trait PhotoUploadTrait
{

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function photoList(Request $request)
    {
        try {
            $user = $this->getAuthenticatedUser();

            $videos = (new Video)->newQuery();
            $videos->where(['user_id' => $user->id, 'file_type' => 2]);

            $videos->whereNull('user_folder_id');

            $videos =  $videos->with('video_tags:id,title')->selectRaw('id ,title, description,privacy,user_id,file_name,file_type')->latest()->limit(6)->get();;
            $userfolders = UserFolder::selectRaw('id,title,slug')->whereNotNull('user_folder_id')->whereRaw("(folder_type = 2 and user_id = ".$user->id." )")->latest()->limit(6)->get();

            $videos->map(function ($video) {
                $video->makeHidden(['video_parent_folder','aws_video_uploaded_path','video_uploaded_path','video_folder_path','user_folders','user_id','file_name','file_type']);
                $video->video_tags->map(function ($video_tag) {
                    $video_tag->makeHidden('pivot');
                });
            });

            $this->WebApiArray['status'] = true;
            
            if ($videos->count() > 0 || $userfolders->count() > 0) {
                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data']['photos'] = $videos;
                $this->WebApiArray['data']['folders'] = $userfolders;
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

    public function fetchAllPhotos(Request $request)
    {
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request method not allowed.", 1);
            }

            $user = $this->getAuthenticatedUser();

            $input = $request->all();

            $page = isset($input['page']) ? $input['page'] : 1;

            $videos = (new Video)->newQuery();
            if(!empty($input['privacy'])){
                $privacy = $input['privacy'] == 'private' || $input['privacy'] == 'public' ? ($input['privacy'] == 'public' ? 1 : 0 ) : 2;
                $videos->where('privacy',  $privacy);
            }
            if(!empty($input['name'])){
                $videos->where('title', 'LIKE', '%'.$input['name'].'%');
            }
            $videos->where(['user_id' => $user->id, 'file_type' => 2]);

            if(empty($input['name']) && empty($input['privacy']) ){
                $videos->whereNull('user_folder_id');
            }

            $videos =  $videos->with('video_tags:id,title')->selectRaw('id, title, description, privacy, user_id, file_name,user_folder_id')->orderBy('title','ASC')->paginate(25, ['*'], 'page', $page);

            $videos->map(function ($video) {
                $video->makeHidden(['video_parent_folder','aws_video_uploaded_path','video_uploaded_path','video_folder_path','user_folders','user_id','file_name','file_type','user_folder_id']);
                $video->video_tags->map(function ($video_tag) {
                    $video_tag->makeHidden('pivot');
                });
            });

            $this->WebApiArray['status'] = true;
            
            if ($videos->count() > 0) {
                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data'] = $videos;
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

    public function fetchChatPhotos(Request $request)
    {
        try {

            if(!$request->isMethod('post'))
            {
                throw new Exception("Request method not allowed.", 1);
            }

            $user = $this->getAuthenticatedUser();

            $input = $request->all();

            $page = isset($input['page']) ? $input['page'] : 1;

            $userChats = (new Message)->newQuery();
            if(!empty($input['name'])){
                $userChats->where( function( $queryone ) use ($input){
                    $queryone->where('message', 'LIKE', '%'.$input['name'].'%' );
                    $queryone->orWhereHas('senders',function ( $subquery ) use($input){
                        $subquery->where('name', 'LIKE', '%'.$input['name'].'%' );
                    });
                });
            }
            $userChats =   $userChats->selectRaw('id, message, user_id, message_type')->whereHas('user_conversations', function ($query) use ($user){
                $query->whereRaw("(one_user_id = ".$user->id." OR two_user_id = ".$user->id." )");
                $query->orWhereHas('group_users',function ( $subquery ) use ($user){
                    $subquery->where('user_id', $user->id);
                });
            })->whereRaw("((delete_one is null or delete_one != ".$user->id.") AND (delete_two is null or  delete_two != ".$user->id.")) AND (group_delete_message is null OR NOT FIND_IN_SET(".$user->id.",group_delete_message))")->where('message_type', 2)->with('senders')->orderBy('id','DESC')
                ->paginate(25, ['*'], 'page', $page);

            $userChats->map(function ($userChat) {
                $userChat->makeHidden(['message_created_date_time','message_created_date','message_created_time','user_id','message_type']);
                $userChat->senders->makeHidden(['role_type', 'remaining_balance','total_balance', 'user_details', 'roles','user_image','user_thumb_image','username','user_uuid','product_tour','privacy','created_at','updated_at','deleted_status','notification_setting']);
            });

            $this->WebApiArray['status'] = true;
            
            if ($userChats->count() > 0) {
                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data'] = $userChats;
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

    public function fetchFolderPhotos(Request $request)
    {
        try {

            if(!$request->isMethod('post'))
            {
                throw new Exception("Request method not allowed.", 1);
            }

            $input = $request->all();

            $validator = Validator::make($input,[
                'folder_slug' => 'required|exists:user_folders,slug',
            ]);

            $user = $this->getAuthenticatedUser();

            $page = isset($input['page']) ? $input['page'] : 1;

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $user_folder = UserFolder::whereNotNull('user_folder_id')->whereRaw("(folder_type = 2 and user_id = ".$user->id." AND slug='".$input['folder_slug']."' )")->first();

            if(!empty($user_folder)) {
                $input = $request->all();
                $videos = (new Video)->newQuery();
                if (!empty($input['privacy'])) {
                    $privacy = $input['privacy'] == 'private' || $input['privacy'] == 'public' ? ($input['privacy'] == 'public' ? 1 : 0) : 2;
                    $videos->where('privacy', $privacy);
                }
                if (!empty($input['name'])) {
                    $videos->where('title', 'LIKE', '%' . $input['name'] . '%');
                }
                $videos->where(['user_id' => $user->id, 'file_type' => 2]);
                if (empty($input['name']) && empty($input['privacy'])) {
                    $videos->where('user_folder_id', $user_folder->id);
                }
                $videos = $videos->with('video_tags:id,title')->selectRaw('id, title,description,privacy,user_id,file_name,file_type,user_folder_id')->orderBy('title', 'ASC')->paginate(25, ['*'], 'page', $page);

                $videos->map(function ($video) {
                    $video->makeHidden(['video_parent_folder','aws_video_uploaded_path','video_uploaded_path','video_folder_path','user_folders','user_id','file_name','file_type','user_folder_id']);
                    $video->video_tags->map(function ($video_tag) {
                        $video_tag->makeHidden('pivot');
                    });
                });

                $this->WebApiArray['status'] = true;
                
                if ($videos->count() > 0) {
                    $this->WebApiArray['message'] = 'Record found.';
                    $this->WebApiArray['data'] = $videos;
                    $this->WebApiArray['statusCode'] = 0;
                } else {
                    $this->WebApiArray['message'] = 'Record not found.';
                    $this->WebApiArray['statusCode'] = 1;
                }
                return response()->json($this->WebApiArray);
            }
            throw new Exception("Folder not exist.", 1);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function storePhoto(Request $request) {
        DB::beginTransaction();
        try {
            if (!$request->isMethod('post')) {
                throw new Exception("Request not allowed.", 1);
            }

            $user = $this->getAuthenticatedUser();

            $input = $request->all();

            if (!empty($input['video_tag'])) {
                $input['video_tag'] = explode(',',$input['video_tag']);
            }

            $validator = Validator::make($input, [
                'title'       => 'required|max:50',
                'description' => 'nullable|max:1000',
                'file_name'   => 'mimes:jpg,png,jpeg|max:204800|required',
                'user_folder_id'   => 'nullable|exists:user_folders,id,folder_type,2,user_id,'.$user->id,
                'video_tag'   => 'nullable|array|min:1',
                'video_tag.*'   => 'nullable|exists:tags,id',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>', $validator->errors()->all()), 1);
            }

            $child_folder = "";
            if(!empty($input['user_folder_id']))
            {
                $check_folder = UserFolder::where('id',$input['user_folder_id'])->first();
                if(empty($check_folder->user_folder_id))
                {
                    throw new Exception("You can't access parent Directory.", 1);
                }
                $child_folder = $check_folder->slug;
            }
            $parent_directories = self::createParentFolder($user);
            if(empty($parent_directories['status'])){
                throw new Exception("Error occured! Directories could not be created.", 1);
            }
            $parent_folder = $parent_directories['folder_name'];

            $file_name = AwsBucketVideoImageFileUpload($input['file_name'], 2, $parent_folder, $child_folder);

            if(empty($file_name)){
                throw new Exception("Error in photo uploading.", 1);
            }

            $video = new Video;
            $input['status'] = 1;
            $input['file_name'] = $file_name['file_name'];
            $input['thumbnail'] = $file_name['file_name'];
            $input['file_type'] = 2;
            $input['user_id'] = $user->id;
            if($video->fill($input)->save()){
                if (isset($input['video_tag']) && count($input['video_tag']) > 0) {
                    $video->video_tags()->sync($input['video_tag']);
                }
                DB::commit();
                $this->WebApiArray['status'] = true;
                
                $this->WebApiArray['message'] = 'Photo Added Successfully.';
                return response()->json($this->WebApiArray);
            }
            throw new Exception("Error Processing Request", 1);

        }  catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function storeFolderPhoto(Request $request) {
        DB::beginTransaction();
        try {
            if (!$request->isMethod('post')) {
                throw new Exception("Request not allowed.", 1);
            }

            $user = $this->getAuthenticatedUser();

            $input = $request->all();

            if (!empty($input['video_tag'])) {
                $input['video_tag'] = explode(',',$input['video_tag']);
            }

            $validator = Validator::make($input, [
                'title'       => 'required|max:50',
                'description' => 'nullable|max:1000',
                'file_name'   => 'mimes:jpg,png,jpeg|max:204800|required',
                'video_tag'   => 'nullable|array|min:1',
                'video_tag.*'   => 'nullable|exists:tags,id',
                'folder_slug' => 'required|exists:user_folders,slug'
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>', $validator->errors()->all()), 1);
            }

            $photo_folders = UserFolder::with('parent_child_folders')->whereNotNull('user_folder_id')->where(['user_id' => $user->id, 'folder_type' => 2, 'slug' => $input['folder_slug']])->first();
            if(!empty($photo_folders)) {

                $file_name = AwsBucketVideoImageFileUpload($input['file_name'], 2, $photo_folders->parent_child_folders->slug, $photo_folders->slug);
                if (empty($file_name)) {
                    throw new Exception("Error in photo uploading.", 1);
                }
                $video = new Video;
                $input['status'] = 1;
                $input['file_name'] = $file_name['file_name'];
                $input['thumbnail'] = $file_name['file_name'];
                $input['file_type'] = 2;
                $input['user_id'] = $user->id;
                $input['user_folder_id'] = $photo_folders->id;
                if ($video->fill($input)->save()) {
                    if (isset($input['video_tag']) && count($input['video_tag']) > 0) {
                        $video->video_tags()->sync($input['video_tag']);
                    }
                    DB::commit();
                    $this->WebApiArray['status'] = true;
                    
                    $this->WebApiArray['message'] = 'Photo Added Successfully.';
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("Error Processing Request", 1);
            }
            throw new Exception("Error Processing Request", 1);
        }  catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function createParentFolder($user)
    {
        $parent_dir = array('status' => false);
        try {
            $parent_folder = UserFolder::whereNull('user_folder_id')->where('user_id',$user->id)->first();
            if(empty($parent_folder))
            {
                $user_slug = $user->username.'_'.$user->user_uuid;
                $parent_folder = new UserFolder;
                if($parent_folder->fill(['title' => $user_slug, 'user_id' => $user->id, 'slug' => $user_slug])->save()){
                    $parent_directory = AwsBucketCreateUserDirectory($parent_folder->slug);
                    if(empty($parent_directory))
                    {
                        throw new Exception("Error occured! Please try again.", 1); 
                    }
                }else{
                    throw new Exception("Error occured! Please try again.", 1);   
                }
            }
            $parent_dir = array('status' => true, 'folder_name' => $parent_folder->slug);
            return $parent_dir;
        } catch (Exception $e) {
            return $parent_dir;
        }  
    }

    public function updatePhoto(Request $request) {
        DB::beginTransaction();
        try {
            if (!$request->isMethod('post')) {
                throw new Exception("Request not allowed.", 1);
            }
            $user = $this->getAuthenticatedUser();

            $input = $request->all();

            if (!empty($input['video_tag'])) {
                $input['video_tag'] = explode(',',$input['video_tag']);
            }

            $validator = Validator::make($input, [
                'title'       => 'required|max:50',
                'description' => 'nullable|max:1000',
                'file_name'   => 'mimes:jpg,png,jpeg|max:204800|nullable',
                'user_folder_id'   => 'nullable|exists:user_folders,id,folder_type,2,user_id,'.$user->id,
                'video_tag'   => 'nullable|array|min:1',
                'video_tag.*'   => 'nullable|exists:tags,id',
                'video_id' => 'required|exists:videos,id'
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>', $validator->errors()->all()), 1);
            }

            $video = Video::with('video_tags:id,title')->selectRaw('id,title,description,privacy,thumbnail,user_id,file_name,user_folder_id')->where(['id' => $input['video_id'], 'user_id' => $user->id , 'file_type' => 2])->first();

            if (empty($video)) {
                throw new Exception("You are not authorized to update this photo.", 1);
            }

            $parent_folder = UserFolder::whereNull('user_folder_id')->where('user_id',$user->id)->value('slug');
            $child_folder = "";
            if(!empty($input['user_folder_id']))
            {
                $check_folder = UserFolder::where('id',$input['user_folder_id'])->first();
                if(empty($check_folder->user_folder_id))
                {
                    throw new Exception("You can't access parent Directory.", 1);
                }
                $child_folder = $check_folder->slug;
            }

            if(!empty($input['file_name'])){
                $file_name = AwsBucketVideoImageFileUpload($input['file_name'], 2, $parent_folder, $child_folder);
                //$file_name = VideoFileUpload($input['file_name']);
                if(empty($file_name)){
                    throw new Exception("Error in photo uploading.", 1);
                }
                $input['file_name'] = $file_name['file_name'];
                $input['thumbnail'] = $file_name['file_name'];
            }else{
                $file_copy = AwsBucketVideoImageFileCopy($video, 2, $parent_folder, $child_folder);
                if(empty($file_copy)){
                    throw new Exception("Error in photo uploading.", 1);
                }
                $input['file_name'] = $video->file_name;
            }
            if($video->fill($input)->save()){
                if (isset($input['video_tag']) && count($input['video_tag']) > 0) {
                    $video->video_tags()->sync($input['video_tag']);
                }else{
                    $video->video_tags()->detach();
                }

                $video->makeHidden(['video_parent_folder','aws_video_uploaded_path','video_uploaded_path','video_folder_path','user_folders','user_id','file_name','updated_at']);
                $video->video_tags->map(function ($video_tag) {
                    $video_tag->makeHidden('pivot');
                });

                DB::commit();
                $this->WebApiArray['status'] = true;
                
                $this->WebApiArray['message'] = 'Photo Updated Successfully.';
                return response()->json($this->WebApiArray);
            }
            throw new Exception("Error Processing Request", 1);
        }  catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function updateFolderPhoto(Request $request) {
        DB::beginTransaction();
        try {
            if (!$request->isMethod('post')) {
                throw new Exception("Request not allowed.", 1);
            }
            $user = $this->getAuthenticatedUser();

            $input = $request->all();

            if (!empty($input['video_tag'])) {
                $input['video_tag'] = explode(',',$input['video_tag']);
            }

            $validator = Validator::make($input, [
                'title'       => 'required|max:50',
                'description' => 'nullable|max:1000',
                'file_name'   => 'mimes:jpg,png,jpeg|max:204800|nullable',
                'user_folder_id'   => 'nullable|exists:user_folders,id,folder_type,2,user_id,'.$user->id,
                'video_tag'   => 'nullable|array|min:1',
                'video_tag.*'   => 'nullable|exists:tags,id',
                'video_id' => 'required|exists:videos,id',
                'folder_slug' => 'required|exists:user_folders,slug'
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>', $validator->errors()->all()), 1);
            }
            $photo_folder = UserFolder::with('parent_child_folders')->whereNotNull('user_folder_id')->where(['user_id' => $user->id, 'folder_type' => 2, 'slug' => $input['folder_slug']])->first();

            if (!empty($photo_folder)) {


                $video = Video::with('video_tags:id,title')->selectRaw('id,title,description,privacy,user_id,file_name')->where(['id' => $input['video_id'],'user_id' => $user->id, 'file_type' => 2, 'user_folder_id' => $photo_folder->id])->first();

                if (empty($video)) {
                    throw new Exception("You are not authorized to update this photo.", 1);
                }
                $parent_folder = $photo_folder->parent_child_folders->slug;
                $child_folder = "";
                if (!empty($input['user_folder_id'])) {
                    $check_folder = UserFolder::where('id', $input['user_folder_id'])->first();
                    if (empty($check_folder->user_folder_id)) {
                        throw new Exception("You can't access parent Directory.", 1);
                    }
                    $child_folder = $check_folder->slug;
                }
                if (!empty($input['file_name'])) {
                    $file_name = AwsBucketVideoImageFileUpload($input['file_name'], 2, $parent_folder, $child_folder);
                    if (empty($file_name)) {
                        throw new Exception("Error in photo uploading.", 1);
                    }
                    $input['file_name'] = $file_name['file_name'];
                    $input['thumbnail'] = $file_name['file_name'];
                } else {
                    $file_copy = AwsBucketVideoImageFileCopy($video, 2, $parent_folder, $child_folder);
                    if (empty($file_copy)) {
                        throw new Exception("Error in photo uploading.", 1);
                    }
                }
                if ($video->fill($input)->save()) {
                    if (isset($input['video_tag']) && count($input['video_tag']) > 0) {
                        $video->video_tags()->sync($input['video_tag']);
                    } else {
                        $video->video_tags()->detach();
                    }

                    DB::commit();
                    $this->WebApiArray['status'] = true;
                    
                    $this->WebApiArray['message'] = 'Photo Updated Successfully.';
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("Error Processing Request", 1);
            }
            throw new Exception("Error Processing Request", 1);
        }  catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function photoDetail(Request $request) {
        try {
            $input = $request->all();

            $user = $this->getAuthenticatedUser();

            $validator = Validator::make($input, [
                'photo_id' => 'required|exists:videos,id',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>', $validator->errors()->all()), 1);
            }

            $photo = Video::with('video_tags:id,title')->with('user_folders:id,title,slug')->selectRaw('id,title,description,privacy,user_id,file_name,file_type,user_folder_id')->where(['user_id' => $user->id, 'file_type' => 2, 'id' => $input['photo_id']])->first();

            if (!empty($photo)) {
                if ($photo->user_folders) {
                    $photo->user_folders->makeHidden(['user_id','user_folder_id','created_at','updated_at','user_folder_id']);
                }

                $photo->makeHidden(['video_parent_folder','aws_video_uploaded_path','video_uploaded_path','video_folder_path','user_id','file_name','file_type']);
                $photo->video_tags->map(function ($video_tag) {
                    $video_tag->makeHidden('pivot');
                });
                $this->WebApiArray['status'] = true;
                
                $this->WebApiArray['data'] = $photo;
                $this->WebApiArray['message'] = 'Photo Detail.';
                return response()->json($this->WebApiArray);
            }
            throw new Exception("Not authorized", 1);
        }  catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function uploadPhoto(Request $request) {
        try {
            if (!$request->isMethod('post')) {
                throw new Exception("Request not allowed.", 1);
            }

            $input = $request->all();

            $validator = Validator::make($input, [
                'folder'       => 'required|max:50',
                'file'   => 'mimes:jpg,png,jpeg|max:204800|required',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>', $validator->errors()->all()), 1);
            }

            $file = ApiFileUpload($input['file'], 2, $input['folder']);

            if ($file) {
                $this->WebApiArray['status'] = true;
                
                $this->WebApiArray['data'] = $file;
                $this->WebApiArray['message'] = 'Photo Uploaded Successfully.';
                return response()->json($this->WebApiArray);
            }
            throw new Exception("Error Processing Request", 1);
        }  catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function deletePhoto(Request $request)
    {
        DB::beginTransaction();
        try {
            $input = $request->all();

            $validator = Validator::make($input,[
                'video_id' => 'required|exists:videos,id',
            ]);

            $user = $this->getAuthenticatedUser();

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $photo = Video::where(['id' => $input['video_id'], 'user_id' => $user->id, 'file_type' => 2])->first();

            if (empty($photo)) {
                throw new Exception("Access Denied.", 1);
            }
            $photo->delete();

            DB::commit();

            $photo = Video::where(['id' => $input['video_id']])->first();

            if(empty($photo)){
                $this->WebApiArray['status'] = true;
                
                $this->WebApiArray['message'] = 'Video Deleted Successfully.';
                return response()->json($this->WebApiArray);
            }
            throw new Exception("Error occurred in photo deleting", 1);
        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

}
