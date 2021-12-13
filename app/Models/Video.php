<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use App\Models\Tag;
use App\Models\UserFolder;

class Video extends Model
{
	use Sluggable;

    protected $fillable = [
        'user_id', 'title', 'slug', 'description', 'file_name', 'file_type', 'status', 'privacy', 'user_folder_id','thumbnail',
    ];

    public function sluggable()
    {
        return [
            'slug' => [
                'source' => ['title'],
                'separator' => '_',
                'onUpdate'  => false,
            ]
        ];
    }

    public function video_tags()
    {
        return $this->belongsToMany(Tag::class, 'video_tag', 'video_id', 'tag_id');
    }

    public function user_folders()
    {
        return $this->belongsTo(UserFolder::class,'user_folder_id', 'id');
    }


    public function getVideoParentFolderAttribute()
    {
    	$user_parent_directory = UserFolder::whereNull('user_folder_id')->where('user_id',$this->user_id)->first();
    	if(!empty($user_parent_directory))
    	{
    		return $user_parent_directory->slug;
    	}
    	return null;
    	
        /*if(!empty($this->user_details->image) && file_exists(public_path('images/users/'.$this->user_details->image)))
        {
            return asset('images/users/'.$this->user_details->image);
        }
        return null;*/
    }

    public function getVideoFolderPathAttribute()
    {
    	$user_parent_directory = UserFolder::whereNull('user_folder_id')->where('user_id',$this->user_id)->first();
    	if(!empty($user_parent_directory))
    	{	
    		if($this->file_type == 1){
    			$parent_type_folder = $user_parent_directory->slug.'/videos/';
    		}else{
    			$parent_type_folder =  $user_parent_directory->slug.'/images/';
    		}
    		$child_type_folder =  !empty($this->user_folders->slug) ? $this->user_folders->slug.'/'.$this->file_name : $this->file_name;
    		return asset('storage/'.$parent_type_folder.$child_type_folder);	
    	}
    	return null;
    }

    public function getAwsVideoFolderPathAttribute()
    {
    	$user_parent_directory = UserFolder::whereNull('user_folder_id')->where('user_id',$this->user_id)->first();
    	if(!empty($user_parent_directory))
    	{
    		if($this->file_type == 1){
    			$parent_type_folder = $user_parent_directory->slug.'/videos/';
    		}else{
    			$parent_type_folder = $user_parent_directory->slug.'/images/';
    		}
    		$child_type_folder =  !empty($this->user_folders->slug) ? $this->user_folders->slug.'/'.$this->file_name : $this->file_name;

    		return config('staging_live_config.AWS_URL').$parent_type_folder.$child_type_folder;
    	}
    	return null;
    }

    public function getAwsVideoFolderThumbPathAttribute()
    {
    	$user_parent_directory = UserFolder::whereNull('user_folder_id')->where('user_id',$this->user_id)->first();
    	if(!empty($user_parent_directory))
    	{
    		if($this->file_type == 1){

    		    if (!empty($this->thumbnail)) {
                    $parent_type_folder =  $user_parent_directory->slug.'/videos/';
                    $child_type_folder =  !empty($this->user_folders->slug) ? $this->user_folders->slug.'/thumb/'.$this->thumbnail : 'thumb/'.$this->thumbnail;

                    return config('staging_live_config.AWS_URL').$parent_type_folder.$child_type_folder;
                }

            return null;

    		}else{
    			$parent_type_folder =  $user_parent_directory->slug.'/images/';
                $child_type_folder =  !empty($this->user_folders->slug) ? $this->user_folders->slug.'/thumb/'.$this->file_name : 'thumb/'.$this->file_name;

                return config('staging_live_config.AWS_URL').$parent_type_folder.$child_type_folder;
    		}
    	}
    	return null;
    }

    public function getVideoUploadedPathAttribute()
    {
        $user_parent_directory = UserFolder::whereNull('user_folder_id')->where('user_id',$this->user_id)->first();
        if(!empty($user_parent_directory))
        {   
            if($this->file_type == 1){
                $parent_type_folder = $user_parent_directory->slug.'/videos/';
            }else{
                $parent_type_folder =  $user_parent_directory->slug.'/images/';
            }
            $child_type_folder =  !empty($this->user_folders->slug) ? $this->user_folders->slug.'/'.$this->file_name : $this->file_name;
            return public_path('storage/'.$parent_type_folder.$child_type_folder);    
        }
        return null;
    }

    public function getAwsVideoUploadedPathAttribute()
    {
        $user_parent_directory = UserFolder::whereNull('user_folder_id')->where('user_id',$this->user_id)->first();
        if(!empty($user_parent_directory))
        {
            if($this->file_type == 1){
                $parent_type_folder = $user_parent_directory->slug.'/videos/';
            }else{
                $parent_type_folder =  $user_parent_directory->slug.'/images/';
            }
            $child_type_folder =  !empty($this->user_folders->slug) ? $this->user_folders->slug.'/'.$this->file_name : $this->file_name;
            return $parent_type_folder.$child_type_folder;
        }
        return null;
    }

    protected $appends = [
        'video_parent_folder', 'video_folder_path', 'aws_video_folder_path', 'aws_video_folder_thumb_path', 'video_uploaded_path', 'aws_video_uploaded_path',
    ];




}
