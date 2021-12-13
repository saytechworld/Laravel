<?php

namespace App\Services\Api\Ios\v4;

use App\Models\UserFolder;
use Illuminate\Http\Request;
use Exception, Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

trait FolderTrait
{

    public function getFolder(Request $request) {
        try {

            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }

            $input = $request->all();

            $validator = Validator::make($input,[
                'folder_type' => 'required|in:1,2',
            ]);

            $page = isset($input['page']) ? $input['page'] : 1;

            $user = $this->getAuthenticatedUser();

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $userFolders = UserFolder::selectRaw('id,title,slug')->whereNotNull('user_folder_id')->where(['folder_type' => $input['folder_type'], 'user_id' => $user->id])
                ->paginate(100, ['*'], 'page', $page);

            
            $this->WebApiArray['status'] = true;
            if ($userFolders->count() > 0) {
                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['statusCode'] = 0;
                $this->WebApiArray['data'] = $userFolders;
            } else {
                $this->WebApiArray['message'] = 'Record Not found.';
                $this->WebApiArray['statusCode'] = 1;
            }
            return response()->json($this->WebApiArray);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function createFolder(Request $request) {
        DB::beginTransaction();;
        try {

            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }

            $input = $request->all();
            $user = $this->getAuthenticatedUser();

            $validator = Validator::make($input,[
                'folder_type' => 'required|in:1,2',
                'title'   =>  'required|not_regex:/^[!@#$%^’&*(),.?\":{}<>]+$/|unique:user_folders,title,NULL,id,folder_type,'.$input['folder_type'].',user_id,'.$user->id,
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

            $parent_folder =UserFolder::whereNull('user_folder_id')->where('user_id',$user->id)->first();
            if(empty($parent_folder))
            {
                $user_slug = $user->username.'_'.$user->user_uuid;
                $parent_folder = new UserFolder;
                if($parent_folder->fill(['title' => $user_slug, 'user_id' => $user->id, 'slug' => $user_slug ])->save()){
                    $parent_directory = AwsBucketCreateUserDirectory($parent_folder->slug);
                    if(empty($parent_directory))
                    {
                        throw new Exception("Error occured! Please try again.", 1);
                    }
                }else{
                    throw new Exception("Error occured! Please try again.", 1);
                }
            }

            $folder = new UserFolder;
            $input['user_folder_id'] = $parent_folder->id;
            $input['user_id'] = $user->id;
            if($folder->fill($input)->save()){
                $child_directory = AwsBucketCreateUserChildDirectory($parent_folder->slug,$folder->slug,$input['folder_type']);
                if(empty($child_directory))
                {
                    throw new Exception("Error occured! Please try again.", 1);
                }
                DB::commit();
                
                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Folder created successfully.';
                return response()->json($this->WebApiArray);
            }
            throw new Exception("Error occured! Please try again.", 1);

        }  catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function updateFolder(Request $request) {
        DB::beginTransaction();
        try {
            if(!$request->isMethod('post'))
            {
                throw new Exception("Request not allowed.", 1);
            }

            $input = $request->all();
            $user = $this->getAuthenticatedUser();

            $validator = Validator::make($input,[
                'folder_type' => 'required|in:1,2',
                'folder_id' => 'required|exists:user_folders,id',
                'title'   =>  'required|not_regex:/^[!@#$%^’&*(),.?\":{}<>]+$/|unique:user_folders,title,'.$input['folder_id'].',id,folder_type,'.$input['folder_type'].',user_id,'.$user->id,
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

            $folder =UserFolder::whereNotNull('user_folder_id')->where(['user_id'=>$user->id, 'id'=>$input['folder_id'], 'folder_type' => $input['folder_type']])->first();

            if(!empty($folder)) {
                if($folder->fill($input)->save()){
                    DB::commit();
                    
                    $this->WebApiArray['status'] = true;
                    $this->WebApiArray['message'] = 'Folder updated successfully.';
                    $this->WebApiArray['data'] = $folder->title;
                    return response()->json($this->WebApiArray);
                }
            }
            throw new Exception("Error occured! Please try again.", 1);

        }  catch (Exception $e) {
            DB::rollback();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function deleteFolder(Request $request) {
        DB::beginTransaction();
        try {
            $input = $request->all();

            $validator = Validator::make($input,[
                'folder_id' => 'required|exists:user_folders,id',
            ]);

            $user = $this->getAuthenticatedUser();

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $folder = UserFolder::whereRaw("(id = ".$input['folder_id']." AND user_id = ".$user->id." )")->
            whereNotNull('user_folder_id')->first();

            if (!empty($folder)) {
                $folder->delete();
                DB::commit();

                $images_folder = $folder->parent_child_folders->slug.'/images/'.$folder->slug;
                AwsDeleteDirectory($images_folder);

                $checkfolder = UserFolder::whereRaw("(id = ".$input['folder_id']." )")->first();
                if(empty($checkfolder)){
                    $this->WebApiArray['status'] = true;
                    
                    $this->WebApiArray['message'] = 'Folder deleted successfully.';
                    return response()->json($this->WebApiArray);
                }
                throw new Exception("Error occurred while deleting folder! Please try again.", 1);
            }
            throw new Exception("Un-authorized", 1);

        } catch (Exception $e) {
            DB::rollBack();
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

}
