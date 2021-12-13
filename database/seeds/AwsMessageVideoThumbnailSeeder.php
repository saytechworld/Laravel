<?php

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use App\Models\Video;
use App\Models\UserFolder;
use App\Models\Message;


class AwsMessageVideoThumbnailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $messages = Message::where('message_type', 3)->whereNull('thumbnail')->get();
        foreach ($messages as $message) {
            if (!empty($message->aws_file_url)) {
                $exists = Storage::disk('s3')->has('messages/'.$message->message);
                if ($exists) {

                    $file = $message->aws_file_url;

                    $image = explode(".",$message->message);
                    $imagename = $image[0].'.jpg';

                    $destination_thumb_path ='messages/thumb/'.$imagename;

                    $outputthumb = base_path("storage/app/public/thumbnail/".$imagename);

                    exec("ffmpeg -i $file -f image2 $outputthumb 2>&1", $output);
                    $originalImage = Image::make($outputthumb)->orientate();
                    $originalImage = $originalImage->stream();

                    Storage::disk('s3')->put($destination_thumb_path, $originalImage,'public');

                    $message->thumbnail = $imagename;
                    $message->save();
                }
            }
        }
    }
}
