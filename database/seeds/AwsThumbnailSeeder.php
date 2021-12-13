<?php

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use App\Models\Video;
use App\Models\UserFolder;
use App\Models\Message;


class AwsThumbnailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::whereHas('user_details',function($queryone){
            $queryone->whereNotNull('image');
        })->get();

        foreach ($users as $user) {
            if (!empty($user->user_image)) {
                $exists = Storage::disk('s3')->has('users/'.$user->user_details->image);

                if ($exists) {
                    $thumb_img = Image::make($user->user_image)->resize(320, 160)->orientate();
                    $thumb_img = $thumb_img->stream();
                    Storage::disk('s3')->put('users/thumb/'.$user->user_details->image, $thumb_img,'public');
                }
            }
        }


        $photos = Video::where('file_type',2)->get();
        foreach ($photos as $photo) {
            if (!empty($photo->aws_video_folder_path)) {

                $user_parent_directory = UserFolder::whereNull('user_folder_id')->where('user_id',$photo->user_id)->first();

                $parent_type_folder =  $user_parent_directory->slug.'/images/';

                $child_type_folder =  !empty($photo->user_folders->slug) ? $photo->user_folders->slug.'/' : '';

                $exists = Storage::disk('s3')->has($photo->aws_video_uploaded_path);

                if ($exists) {
                    $thumb_img = Image::make($photo->aws_video_folder_path)->resize(320, 160)->orientate();
                    $thumb_img = $thumb_img->stream();
                    Storage::disk('s3')->put($parent_type_folder.$child_type_folder.'thumb/'.$photo->file_name, $thumb_img,'public');
                }
            }
        }

        $messages = Message::where('message_type', 2)->get();

        foreach ($messages as $message) {
            if (!empty($message->aws_file_url)) {
                $exists = Storage::disk('s3')->has('messages/'.$message->message);
                if ($exists) {
                    $thumb_img = Image::make($message->aws_file_url)->resize(320, 160)->orientate();
                    $thumb_img = $thumb_img->stream();
                    Storage::disk('s3')->put('messages/thumb/'.$message->message, $thumb_img,'public');
                }
            }
        }
    }
}
