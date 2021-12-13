<?php

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use App\Models\Video;
use App\Models\UserFolder;
use App\Models\Message;


class AwsVideoArchiveThumbnailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $videos = Video::where('file_type',1)->whereNull('thumbnail')->get();
        foreach ($videos as $video) {
            if (!empty($video->aws_video_folder_path)) {

                $user_parent_directory = UserFolder::whereNull('user_folder_id')->where('user_id',$video->user_id)->first();

                $parent_type_folder =  $user_parent_directory->slug.'/videos/';

                $child_type_folder =  !empty($video->user_folders->slug) ? $video->user_folders->slug.'/' : '';

                $exists = Storage::disk('s3')->has($video->aws_video_uploaded_path);

                if ($exists) {
                    $file = $video->aws_video_folder_path;

                    $image = explode(".",$video->file_name);
                    $imagename = $image[0].'.jpg';

                    $destination_thumb_path = $parent_type_folder.$child_type_folder.'/thumb/'.$imagename;

                    $outputthumb = base_path("storage/app/public/thumbnail/".$imagename);

                        exec("ffmpeg -i $file -f image2 $outputthumb 2>&1", $output);

                        $originalImage = Image::make($outputthumb)->orientate();
                        $originalImage = $originalImage->stream();

                        $thumb_upload = Storage::disk('s3')->put($destination_thumb_path, $originalImage,'public');

                        if ($thumb_upload) {
                            $video->thumbnail = $imagename;
                            $video->save();
                        }
                }
            }
        }
    }
}
