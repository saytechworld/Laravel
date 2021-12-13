<?php

namespace App\Services\Api\v2;

use App\Models\User;
use App\Models\Video;
use function foo\func;
use Illuminate\Http\Request;
use Exception, Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

trait CoachInformationTrait
{
    public function getCoachList(Request $request) {
        try {

            if(!$request->isMethod('post'))
            {
                throw new Exception("Request method not allowed.", 1);
            }

            $user = $this->getAuthenticatedUser();

            $input = $request->all();

            $page = isset($input['page']) ? $input['page'] : 1;

            $coaches = (new User)->newQuery();
            if(!empty($input['name']))
            {
                $coaches->where('name', 'like', '%'.$input['name'].'%');
            }
            if(!empty($input['languages']) && $input['languages'] != 'all')
            {
                $coaches->whereHas('user_spoken_languages',function($queryone) use($input){
                    $queryone->where('language_id',$input['languages']);
                });
            }
            if(!empty($input['games']) && $input['games'] != 'all')
            {
                $coaches->whereHas('coach_games',function($querytwo) use($input){
                    $querytwo->where('game_id',$input['games']);
                });
            }
            if(!empty($input['skills']) && is_array($input['skills']))
            {
                $coaches->whereHas('coach_games_skills',function($querythree) use($input){
                    $querythree->whereIn('skill_id',$input['skills']);
                });
            }
            if(isset($input['min_experience']) && isset($input['max_experience']))
            {
                $coaches->whereHas('user_details',function($queryfourth) use($input){
                    $queryfourth->whereBetween('experience',[$input['min_experience'], $input['max_experience']]);
                });
            }
            $coaches =  $coaches->selectRaw('id,name,username,user_uuid')->whereHas('roles',function($query){
                $query->where('role_id',3);
            })->where('id','!=',$user->id)
                ->where(['status' => 1, 'confirmed' => 1])
                ->where('deleted_status','!=',1)
                ->orderBy('username','ASC')
                ->paginate(25, ['*'], 'page', $page);

            $coaches->map(function ($coach) {
                $coach->makeHidden(['role_type','total_balance','remaining_balance','roles']);
                $coach->user_details->makeHidden(['id','user_id','image','mobile_code_id', 'mobile','gender','dob','address_line_1','address_line_2','country_id','state_id','city_id','zipcode_id','about','created_at','updated_at','user_profile_image']);
            });

            $max_exp = getUserMaxExperience();
            
            $this->WebApiArray['status'] = true;
            if ($coaches->count() > 0) {
                $this->WebApiArray['message'] = 'Coach List.';
                $this->WebApiArray['data'] = $coaches;
                $this->WebApiArray['max_exp'] = $max_exp;
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
    public function getCoachDetail(Request $request) {
        try {
            $input = $request->all();

            $validator = Validator::make($input,[
                'username'   => 'required|exists:users,username',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $user = $this->getAuthenticatedUser();

            $coach = User::whereHas('roles',function($query){
                $query->where('role_id',3);
            })->where('id','!=',$user->id)
                ->where(['status' => 1, 'confirmed' => 1])
                ->where('deleted_status','!=',1)
                ->where('username', $input['username'])->first();


            if (!empty($coach)) {



                $coach->coach_games->map(function ($sport) {
                     $sport->makeHidden(['id','slug','status','created_at','updated_at','pivot']);
                });

                $coach->coach_games = $coach->coach_games->unique()->values()->all();

                $coach->user_spoken_languages->map(function ($language) {
                    $language->makeHidden(['id','slug','lang_code','short_code','status','created_at','updated_at','pivot']);
                });

                $coach->coach_games_skills->map(function ($skill) {
                    $skill->makeHidden(['id','game_id','slug','status','created_at','updated_at','pivot']);
                });

                $videos = (new Video)->newQuery();

                $videos->where(['user_id' => $coach->id, 'file_type' => 1, 'privacy' => 1]);

                $videos =  $videos->with('video_tags:id,title')->selectRaw('id ,title, description,privacy,user_id,file_name,thumbnail,file_type,user_folder_id')->latest()->limit(2)->get();

                $videos->map(function ($video) {
                    $video->makeHidden(['video_parent_folder','aws_video_uploaded_path','video_uploaded_path','video_folder_path','user_folders','user_id','file_name','thumbnail','file_type','user_folder_id']);
                    $video->video_tags->map(function ($video_tag) {
                        $video_tag->makeHidden('pivot');
                    });
                });

                $photos = (new Video)->newQuery();

                $photos->where(['user_id' => $coach->id, 'file_type' => 2, 'privacy' => 1]);

                $photos =  $photos->with('video_tags:id,title')->selectRaw('id ,title, description,privacy,user_id,file_name,thumbnail,file_type,user_folder_id')->latest()->limit(2)->get();

                $photos->map(function ($photo) {
                    $photo->makeHidden(['video_parent_folder','aws_video_uploaded_path','video_uploaded_path','video_folder_path','user_folders','user_id','file_name','thumbnail','file_type','user_folder_id']);
                    $photo->video_tags->map(function ($video_tag) {
                        $video_tag->makeHidden('pivot');
                    });
                });

                $data = [
                  'id' =>   $coach->id,
                  'name' =>   $coach->name,
                  'user_uuid' =>   $coach->user_uuid,
                  'privacy' =>   $coach->privacy,
                  'image' =>   $coach->user_image,
                  'about' =>   $coach->user_details->about,
                  'address' => $coach->user_details->country ? $coach->user_details->country->title : '',
                  'experience' => $coach->user_details->experience,
                  'gender' => $coach->user_details->gender,
                  'sport' => $coach->coach_games,
                  'language' => $coach->user_spoken_languages,
                  'discipline' => $coach->coach_games_skills,
                  'videos' => $videos,
                  'photos' => $photos,
                ];

                $this->WebApiArray['status'] = true;
                $this->WebApiArray['message'] = 'Coach Detail.';
                $this->WebApiArray['data'] = $data;
                return response()->json($this->WebApiArray);
            }
            throw new Exception("Coach does not exist.", 1);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    public function getCoachVideo(Request $request) {
        try {

            $input = $request->all();

            $validator = Validator::make($input,[
                'id'   => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $page = isset($input['page']) ? $input['page'] : 1;

            $user = $this->getAuthenticatedUser();

            $videos = Video::with('video_tags:id,title')
                ->selectRaw('id ,title, description,privacy,user_id,file_name,thumbnail,file_type,user_folder_id')
                ->where(['user_id' => $input['id'], 'file_type' => 1, 'privacy' => 1])->orderBy('id', 'DESC')->paginate(25, ['*'], 'page', $page);

            $videos->map(function ($video) {
                $video->makeHidden(['video_parent_folder','aws_video_uploaded_path','video_uploaded_path','video_folder_path','user_folders','user_id','file_name','thumbnail','file_type','user_folder_id']);
                $video->video_tags->map(function ($video_tag) {
                    $video_tag->makeHidden('pivot');
                });
            });

            $this->WebApiArray['status'] = true;
            if ($videos->count() > 0) {
                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data']['videos'] = $videos;
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

    public function getCoachPhoto(Request $request) {
        try {

            $input = $request->all();

            $validator = Validator::make($input,[
                'id'   => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $page = isset($input['page']) ? $input['page'] : 1;

            $user = $this->getAuthenticatedUser();

            $photos = Video::with('video_tags:id,title')
                ->selectRaw('id ,title, description,privacy,user_id,file_name,thumbnail,file_type,user_folder_id')
                ->where(['user_id' => $input['id'], 'file_type' => 2, 'privacy' => 1])->orderBy('id', 'DESC')   ->paginate(25, ['*'], 'page', $page);

            $photos->map(function ($photo) {
                $photo->makeHidden(['video_parent_folder','aws_video_uploaded_path','video_uploaded_path','video_folder_path','user_folders','user_id','file_name','thumbnail','file_type','user_folder_id']);
                $photo->video_tags->map(function ($video_tag) {
                    $video_tag->makeHidden('pivot');
                });
            });

            $this->WebApiArray['status'] = true;
            if ($photos->count() > 0) {
                $this->WebApiArray['message'] = 'Record found.';
                $this->WebApiArray['data']['videos'] = $photos;
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
}
