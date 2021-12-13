<?php
use App\Models\Message;
use App\Models\User;
use App\Models\Order;
use App\Models\UserDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Models\UserToken;

if(!function_exists('SetRouteActive')){
    function SetRouteActive(array $routeArr)
    {
        $active = null;
        if(in_array(request()->segment(3), $routeArr)){
            $active = "menu-open active";
        }
        return $active;
    }
}

if(!function_exists('SetMenuActive')){
    function SetMenuActive($url)
    {
        $active = null;
        if(request()->segment(3) == $url){
            $active = "active";
        }
        return $active;
    }
}

if(!function_exists('CreateUserDirectory')){
    function CreateUserDirectory($user_slug) {
        try{
            $video_folder = base_path("storage/app/public/".$user_slug.'/videos/');
            $images_folder = base_path("storage/app/public/".$user_slug.'/images/');

            if (!file_exists($video_folder)) {
                File::makeDirectory($video_folder, 0777, true, true);
            }
            if (!file_exists($images_folder)) {
                File::makeDirectory($images_folder, 0777, true, true);
            }
            if (file_exists($video_folder) && file_exists($images_folder) ) {
                return true;
            }
            throw new Exception("Error Processing Request", 1);
        }catch(Exception $e){
            return "";
        }
    }
}

if(!function_exists('CreateUserChildDirectory')){
    function CreateUserChildDirectory($user_slug,$foldername,$foldertype) {
        try{
            if($foldertype == 1)
            {
                $video_folder = base_path("storage/app/public/".$user_slug.'/videos/'.$foldername);
                if (!file_exists($video_folder)) {
                    File::makeDirectory($video_folder, 0777, true, true);
                }

                if (file_exists($video_folder)) {
                    return true;
                }
                throw new Exception("Error Processing Request", 1);
            }
            if($foldertype == 2)
            {
                $images_folder = base_path("storage/app/public/".$user_slug.'/images/'.$foldername);
                if (!file_exists($images_folder)) {
                    File::makeDirectory($images_folder, 0777, true, true);
                }
                if (file_exists($images_folder)) {
                    return true;
                }
                throw new Exception("Error Processing Request", 1);
            }
        }catch(Exception $e){
            return "";
        }
    }
}

if(!function_exists('VideoImageFileUpload')){

    function VideoImageFileUpload($file, $folder_type, $parent_folder, $child_folder = null) {
        try{
            $fileNameExt = $file->getClientOriginalName();
            $fileName = pathinfo($fileNameExt, PATHINFO_FILENAME);
            $fileExt = $file->getClientOriginalExtension();
            if (strtolower($fileExt) == 'mov') {
                $fileExt = 'mp4';
            }
            $featuredfileName = uniqid().'.'.$fileExt;
            if($folder_type == 1)
            {

                $foldername = base_path("storage/app/public/".$parent_folder.'/videos/');
                if(!empty($child_folder))
                {
                    $foldername = base_path("storage/app/public/".$parent_folder.'/videos/'.$child_folder.'/');
                }
                $file->move($foldername, $featuredfileName);
            }

            if($folder_type == 2)
            {
                $foldername = base_path("storage/app/public/".$parent_folder.'/images/');
                if(!empty($child_folder))
                {
                    $foldername = base_path("storage/app/public/".$parent_folder.'/images/'.$child_folder.'/');
                }
                $originalImage = Image::make($file)->orientate();
                $originalImage->save($foldername.$featuredfileName);


            }
            if(file_exists($foldername.$featuredfileName))
            {
                $file_array = array('file_name' => $featuredfileName, 'file_extension' => $fileExt);
                return $file_array;
            }
            throw new Exception("Error Processing Request", 1);

        }catch(Exception $e){
            return "";
        }
    }
}

if(!function_exists('VideoImageFileCopy')){

    function VideoImageFileCopy($fileobject, $folder_type, $parent_folder, $child_folder = null) {
        try{

            if($folder_type == 1)
            {
                $foldername = base_path("storage/app/public/".$parent_folder.'/videos/');
                if(!empty($child_folder))
                {
                    $foldername = base_path("storage/app/public/".$parent_folder.'/videos/'.$child_folder);
                }
                $source_folder =  base_path("storage/app/public/".$parent_folder.'/videos');
            }

            if($folder_type == 2)
            {
                $foldername = base_path("storage/app/public/".$parent_folder.'/images/');
                if(!empty($child_folder))
                {
                    $foldername = base_path("storage/app/public/".$parent_folder.'/images/'.$child_folder);
                }
                $source_folder =  base_path("storage/app/public/".$parent_folder.'/images');
            }
            $source_child_folder = !empty($fileobject->user_folder_id) ? $fileobject->user_folders->slug.'/'.$fileobject->file_name : $fileobject->file_name;
            $source_path = $source_folder.'/'.$source_child_folder;
            $destination_path = $foldername.'/'.$fileobject->file_name;
            File::move($source_path,$destination_path);
            if (file_exists($destination_path)) {
                return true;
            }
            throw new Exception("Error Processing Request", 1);
        }catch(Exception $e){
            return "";
        }
    }
}

if(!function_exists('VideoFileUpload')){

    function VideoFileUpload($file, $folder = null) {
        try{
            $foldername = base_path("storage/app/public/videos");
            if (!file_exists($foldername)) {
                File::makeDirectory($foldername, 0777, true, true);
            }
            $fileNameExt = $file->getClientOriginalName();
            $fileName = pathinfo($fileNameExt, PATHINFO_FILENAME);
            $fileExt = $file->getClientOriginalExtension();

            if (strtolower($fileExt) == 'mov') {
                $fileExt = 'mp4';
            }

            $featuredfileName = uniqid().'.'.$fileExt;
            $upload_success = $file->move($foldername, $featuredfileName);
            if($upload_success){
                $file_array = array('file_name' => $featuredfileName, 'file_extension' => $fileExt);
                return $file_array;
            }
            throw new Exception("Error Processing Request", 1);
        }catch(Exception $e){
            return "";
        }
    }
}
if(!function_exists('ApiFileUpload')){

    function ApiFileUpload($file, $type, $folder = null) {
        try{
            if ($type == 1) {
                $folder = 'videos/'.$folder;
            } else {
                $folder = 'photos/'.$folder;
            }
            $foldername = base_path("storage/app/public/".$folder);
            if (!file_exists($foldername)) {
                File::makeDirectory($foldername, 0777, true, true);
            }
            $fileExt = $file->getClientOriginalExtension();

            if (strtolower($fileExt) == 'mov') {
                $fileExt = 'mp4';
            }

            $uniq = uniqid();
            $featuredfileName = $uniq.'.'.$fileExt;
            $upload_success = $file->move($foldername, $featuredfileName);

            if ($type == 1) {
                $featuredfileNameImg = $uniq.'.png';

                $outputDir = $foldername."/thumb/";


                if (!file_exists($outputDir)) {
                    File::makeDirectory($outputDir, 0777, true, true);
                }

                $inputVideo = $foldername.'/'.$featuredfileName;

                $outputImg = $outputDir.$featuredfileNameImg;

                exec("ffmpeg -i $inputVideo -ss 00:00:01.000 -vframes 1 $outputImg 2>&1", $output);
            }

            if($upload_success){
                $file_array = array('file_name' => $featuredfileName, 'file_extension' => $fileExt);
                return $file_array;
            }
            throw new Exception("Error Processing Request", 1);
        }catch(Exception $e){
            return "";
        }
    }
}

if(!function_exists('MessageVideoImageFileUpload')){

    function MessageVideoImageFileUpload($file, $folder = null, $file_type) {
        try{
            $foldername = base_path("storage/app/public/".$folder);
            if (!file_exists($foldername)) {
                File::makeDirectory($foldername, 0777, true, true);
            }
            $fileNameExt = $file->getClientOriginalName();
            $fileName = pathinfo($fileNameExt, PATHINFO_FILENAME);
            $fileExt = $file->getClientOriginalExtension();
            if (strtolower($fileExt) == 'mov') {
                $fileExt = 'mp4';
            }
            $featuredfileName = uniqid().'.'.$fileExt;
            if($file_type == 'V')
            {
                $file->move($foldername, $featuredfileName);
            }
            if($file_type == 'I')
            {
                $originalImage = Image::make($file)->orientate();
                $originalImage->save($foldername.'/'.$featuredfileName);
            }
            if(file_exists($foldername.'/'.$featuredfileName))
            {
                $file_array = array('file_name' => $featuredfileName, 'file_extension' => $fileExt);
                return $file_array;
            }
            throw new Exception("Error Processing Request", 1);
        }catch(Exception $e){
            return "";
        }
    }
}

if(!function_exists('imagecompressupload')){

    function imagecompressupload($file, $folder = null) {
        try{
            if(empty($folder)){
                $folder = "uploads";
            }
            $foldername = public_path("/images/".$folder);
            if (!file_exists($foldername)) {
                File::makeDirectory($foldername, 0777, true, true);
            }
            $fileNameExt = $file->getClientOriginalName();
            $fileName = pathinfo($fileNameExt, PATHINFO_FILENAME);
            $fileExt = $file->getClientOriginalExtension();
            $featuredfileName = uniqid().'.'.$fileExt;

            $originalImage = Image::make($file)->orientate();
            $originalImage->save($foldername.'/'.$featuredfileName);
            if(file_exists($foldername.'/'.$featuredfileName))
            {
                return $featuredfileName;
            }
            throw new Exception("Error Processing Request", 1);
        }catch(Exception $e){
            return "";
        }
    }

}

if(!function_exists('base64imageupload')){

    function base64imageupload($file, $folder = null) {
        try{
            if(empty($folder)){
                $folder = "uploads";
            }
            $foldername = public_path("/images/".$folder);
            if (!file_exists($foldername)) {
                File::makeDirectory($foldername, 0777, true, true);
            }
            $featuredfileName = uniqid().".png";
            $Image_Path = $foldername.'/'.$featuredfileName;
            $originalImage = Image::make(file_get_contents($file))->orientate();
            $originalImage->save($Image_Path);
            if(file_exists($Image_Path))
            {
                return $featuredfileName;
            }
            throw new Exception("Error Processing Request", 1);
        }catch(Exception $e){
            return "";
        }
    }

}

if(!function_exists('CheckMessageCount')){
    function CheckMessageCount()
    {
        $countUnreadMsg =  Message::whereHas('user_conversations',function($query){
            $query->whereRaw( "(one_user_id = ".auth()->id()." OR two_user_id = ".auth()->id().")");
        })->whereRaw("( user_id != ".auth()->id()." AND  read_flag != 1 ) && (group_delete_message is null OR NOT FIND_IN_SET(".auth()->id().",group_delete_message))")->get();
        return $countUnreadMsg->count();
    }
}

if(!function_exists('CheckUserTrailPeriod')){
    function CheckUserTrailPeriod()
    {
        $trail_period_arr = array('status' => true);
        $trail_days = 30;
        $today_date = Carbon::now()->format('Y-m-d');
        $user_trail_period = User::whereRaw("( date(DATE_ADD(created_at, INTERVAL ".$trail_days." DAY)) > '".$today_date."' AND id = ".auth()->id()." )")->first();
        $checkBuyPlan =  Order::whereRaw("( user_id = ".auth()->id()." AND order_type != 1 AND status = 1 )")
            ->whereDate('plan_end_date', '>', $today_date)
            ->orderBy('id','DESC')
            ->first();
        if(!empty($user_trail_period)){
            $user_trail_days = Carbon::parse($user_trail_period->created_at)->addDays($trail_days);
            $diff_in_days = $user_trail_days->diffInDays(Carbon::now());
            $trail_period_arr['trail_days'] = $diff_in_days;
        }else{
            $trail_period_arr['trail_days'] = 0;
        }
        if(!empty($checkBuyPlan)){
            $trail_period_arr['status'] = false;
        }
        return $trail_period_arr;
    }
}

if(!function_exists('CheckUserSubscriptionPeriod')){
    function CheckUserSubscriptionPeriod()
    {
        $today_date = Carbon::now()->format('Y-m-d');
        $checkBuyPlan =  Order::whereRaw("( user_id = ".auth()->id()." AND order_type != 1 AND status = 1 )")
            ->whereDate('plan_end_date', '>', $today_date)
            ->orderBy('id','DESC')
            ->first();

        $plan_period_arr = array('status' => false);
        if(!empty($checkBuyPlan)){
            if (Carbon::parse($checkBuyPlan->plan_end_date)->gt(Carbon::now()) && Carbon::parse($checkBuyPlan->plan_end_date)->lt(Carbon::now()->addDays(7)))
            {
                $plan_end_date = Carbon::parse($checkBuyPlan->plan_end_date);
                $diff_in_days = $plan_end_date->diffInDays(Carbon::now());
                $plan_period_arr = array('trail_days' => $diff_in_days, 'status' => true );
            }
        }
        return $plan_period_arr;
    }
}

if(!function_exists('CheckActiveSubscriptionTimeUser')){
    function CheckActiveSubscriptionTimeUser()
    {
        $orderdetails =  Order::where(['user_id' => auth()->id(), 'status' => 1])->where('order_type', '!=', 1)->orderBy('id','DESC')->first();
        $buy_plan = 1;
        $plan_id = '';
        if(!empty($orderdetails))  {
            $plan_id = $orderdetails->plan_id;
            if (Carbon::parse($orderdetails->plan_end_date)->gt(Carbon::now()))  {
                $to   = Carbon::createFromFormat('Y-m-d', Carbon::parse($orderdetails->plan_end_date)->format('Y-m-d'));
                $from = Carbon::createFromFormat('Y-m-d', Carbon::now()->format('Y-m-d'));
                $diff_in_days = $to->diffInDays($from);
                if($diff_in_days <= 10) {
                    $buy_plan = 1;
                }else{
                    $buy_plan = 0;
                }
            }else{
                $buy_plan = 1;
            }
        }
        $plan = [
            'buy_plan'=>$buy_plan,
            'plan_id'=>$plan_id
        ];
        return $plan;
    }
}

if (!function_exists('getUserMaxExperience')) {
    function getUserMaxExperience() {
        $exp =  UserDetail::whereHas('users',function($query){
            $query->where(['deleted_status' => 0, 'confirmed' => 1, 'status' => 1]);
        })->max('experience');
        if ($exp < 15) {
            $exp = 15;
        }
        return $exp;
    }
};

if(!function_exists('AwsBucketCreateUserDirectory')){
    function AwsBucketCreateUserDirectory($user_slug) {
        try{
            $video_folder = Storage::disk('s3')->makeDirectory($user_slug.'/videos/');
            $image_folder = Storage::disk('s3')->makeDirectory($user_slug.'/images/');

            if ($video_folder && $image_folder ) {
                return true;
            }
            throw new Exception("Error Processing Request", 1);
        }catch(Exception $e){
            return "";
        }
    }
}

if(!function_exists('AwsBucketCreateUserChildDirectory')){
    function AwsBucketCreateUserChildDirectory($parent_folder,$child_folder,$foldertype) {
        try{
            if($foldertype == 1)
            {
                $video_folder = Storage::disk('s3')->makeDirectory($parent_folder.'/videos/'.$child_folder);
                if ($video_folder) {
                    return true;
                }
                throw new Exception("Error Processing Request", 1);
            }
            if($foldertype == 2)
            {
                $image_folder = Storage::disk('s3')->makeDirectory($parent_folder.'/images/'.$child_folder);
                if ($image_folder) {
                    return true;
                }
                throw new Exception("Error Processing Request", 1);
            }
        }catch(Exception $e){
            return "";
        }
    }
}

if(!function_exists('AwsChunkVideoFileUpload')){

    function AwsChunkVideoFileUpload($file) {
        try{
            $fileExt = 'mp4';
            $mimetype = "video/mp4";

            $featuredfileName = uniqid().'.'.$fileExt;

            /*$folder = base_path("storage/app/public/compress_video");

            $file->move($folder, $featuredfileName);

            $inputVideo = $folder.'/'.$featuredfileName;

            $outputDir = base_path("storage/app/public/compress_video/compressed/");

            if (!file_exists($outputDir)) {
                File::makeDirectory($outputDir, 0777, true, true);
            }

            $outputVideo = base_path("storage/app/public/compress_video/compressed/".$featuredfileName);

            exec("C:/ffmpeg/bin/ffmpeg.exe -i $inputVideo -b 1000000 $outputVideo 2>&1", $output);

            if (file_exists($inputVideo)) {
                unlink($inputVideo);
            }*/

            $foldername ='chunk_videos/';

            ob_end_clean();
            $s3Client = new \Aws\S3\S3Client([
                'region' => env('AWS_DEFAULT_REGION'),
                'version' => '2006-03-01',
                'credentials' => [
                    'key' => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ]
            ]);

            $uploader = new \Aws\S3\MultipartUploader($s3Client, \fopen($file, 'r+'), [
                'bucket' => env('AWS_BUCKET'),
                'key' => $foldername.$featuredfileName,
                'mimetype' => $mimetype,
                'acl' => 'public-read-write',
                'before_initiate' => function (\Aws\Command $command) use($mimetype) {
                    // $command is a CreateMultipartUpload operation
                    $command['ContentType'] = $mimetype;
                },
            ]);

            $upload = $uploader->upload();

            /*if (file_exists($outputVideo)) {
                unlink($outputVideo);
            }*/

            if(!empty($upload))
            {
                $file_array = array('file_name' => $featuredfileName, 'file_extension' => $fileExt);
                return $file_array;
            }
            throw new Exception("Error Processing Request", 1);
        }catch(Exception $e){
            return "";
        }
    }
}

if(!function_exists('AwsBucketVideoImageFileUpload')){

    function AwsBucketVideoImageFileUpload($file, $folder_type, $parent_folder, $child_folder = null) {
        try{
            $fileExt = $file->getClientOriginalExtension();
            if (strtolower($fileExt) == 'mov') {
                $fileExt = 'mp4';
            }

            $mimetype = $file->getMimetype();
            if (strtolower($mimetype) == "video/quicktime") {
                $mimetype = "video/mp4";
            }

            $featuredfileName = uniqid().'.'.$fileExt;
            $upload = false;

            if($folder_type == 1)
            {
                $foldername =$parent_folder.'/videos/';
                if(!empty($child_folder))
                {
                    $foldername = $parent_folder.'/videos/'.$child_folder.'/';
                }

                ob_end_clean();
                $s3Client = new \Aws\S3\S3Client([
                    'region' => env('AWS_DEFAULT_REGION'),
                    'version' => '2006-03-01',
                    'credentials' => [
                        'key' => env('AWS_ACCESS_KEY_ID'),
                        'secret' => env('AWS_SECRET_ACCESS_KEY'),
                    ]
                ]);

                $uploader = new \Aws\S3\MultipartUploader($s3Client, \fopen($file, 'r+'), [
                    'bucket' => env('AWS_BUCKET'),
                    'key' => $foldername.$featuredfileName,
                    'mimetype' => $mimetype,
                    'acl' => 'public-read-write',
                    'before_initiate' => function (\Aws\Command $command) use($mimetype) {
                        // $command is a CreateMultipartUpload operation
                        $command['ContentType'] = $mimetype;
                    },
                ]);

                $upload = $uploader->upload();


                //get image name
                $image = explode(".",$featuredfileName);
                $imagename = $image[0].'.jpg';

                $destination_thumb_path = $foldername.'/thumb/'.$imagename;

                $outputthumb = base_path("storage/app/public/thumbnail/".$imagename);

                exec("ffmpeg -i $file -f image2 $outputthumb 2>&1", $output);

                if(file_exists($outputthumb)){
                    $originalImage = Image::make($outputthumb)->fit(320, 160)->orientate();
                    $originalImage = $originalImage->stream();

                    $thumb_upload = Storage::disk('s3')->put($destination_thumb_path, $originalImage,'public');
                }
            }

            if($folder_type == 2)
            {
                $foldername =$parent_folder.'/images/';
                if(!empty($child_folder))
                {
                    $foldername = $parent_folder.'/images/'.$child_folder.'/';
                }

                $thumb_img = Image::make($file)->fit(320, 160)->orientate();
                $thumb_img = $thumb_img->stream();

                $thumb_upload = Storage::disk('s3')->put($foldername.'thumb/'.$featuredfileName, $thumb_img,'public');

                $originalImage = Image::make($file)->orientate();
                $originalImage = $originalImage->stream();

                $upload = Storage::disk('s3')->put($foldername.$featuredfileName, $originalImage, 'public');
            }
            if(!empty($upload))
            {
                $file_array = array('file_name' => $featuredfileName, 'file_extension' => $fileExt);
                return $file_array;
            }
            throw new Exception("Error Processing Request", 1);

        }catch(Exception $e){

            return "";
        }
    }
}

if(!function_exists('AwsBucketVideoImageFileCopy')){

    function AwsBucketVideoImageFileCopy($fileobject, $folder_type, $parent_folder, $child_folder = null) {
        try{

            if($folder_type == 1)
            {
                $foldername = $parent_folder.'/videos/';
                if(!empty($child_folder))
                {
                    $foldername = $parent_folder.'/videos/'.$child_folder.'/';
                }
                $source_folder = $parent_folder.'/videos';
            }

            if($folder_type == 2)
            {
                $foldername = $parent_folder.'/images/';
                if(!empty($child_folder))
                {
                    $foldername = $parent_folder.'/images/'.$child_folder.'/';
                }
                $source_folder = $parent_folder.'/images';
            }

            $source_child_folder = !empty($fileobject->user_folder_id) ? $fileobject->user_folders->slug.'/'.$fileobject->file_name : $fileobject->file_name;

            $source_path = $source_folder.'/'.$source_child_folder;
            $destination_path = $foldername.'/'.$fileobject->file_name;

            $source_child_thumb_folder = !empty($fileobject->user_folder_id) ? $fileobject->user_folders->slug.'/thumb/'.$fileobject->thumbnail : 'thumb/'.$fileobject->thumbnail;
            $source_thumb_path = $source_folder.'/'.$source_child_thumb_folder;
            $destination_thumb_path = $foldername.'/thumb/'.$fileobject->thumbnail;

            if (Storage::disk('s3')->exists($destination_path)) {
                return true;
            }
            $move = Storage::disk('s3')->move($source_path, $destination_path);

            if (!Storage::disk('s3')->exists($destination_thumb_path)) {
                if (Storage::disk('s3')->exists($source_thumb_path)) {
                    Storage::disk('s3')->move($source_thumb_path, $destination_thumb_path);
                }
            }

            if ($move) {
                return true;
            }
            throw new Exception("Error Processing Request", 1);
        }catch(Exception $e){
            return "";
        }
    }
}


if(!function_exists('AwsBucketVideoMoveLocalToS3')){

    function AwsBucketVideoMoveLocalToS3($filename, $parent_folder, $child_folder = null) {
        try{

            $foldername = $parent_folder.'/videos/';
            if(!empty($child_folder))
            {
                $foldername = $parent_folder.'/videos/'.$child_folder;
            }


            $source_path = 'chunk_videos/'.$filename;
            $destination_path = $foldername.'/'.$filename;


            //get image name
            $image = explode(".",$filename);
            $imagename = $image[0].'.jpg';

            $destination_thumb_path = $foldername.'/thumb/'.$imagename;

            $inputVideo = config('staging_live_config.AWS_URL').'chunk_videos/'.$filename;

            $outputthumb = base_path("storage/app/public/thumbnail/".$imagename);

            exec("ffmpeg -i $inputVideo -f image2 $outputthumb 2>&1", $output);

            $exists = Storage::disk('s3')->has($source_path);
            if(empty($exists)) {
                return [
                    'status' => 0,
                    'message' => 'File not exist on our server'
                ];
            }

            if(file_exists($outputthumb)){
                $originalImage = Image::make($outputthumb)->fit(320, 160)->orientate();
                $originalImage = $originalImage->stream();
                $thumb_upload = Storage::disk('s3')->put($destination_thumb_path, $originalImage,'public');
            }

            $move = Storage::disk('s3')->move($source_path, $destination_path);


            if ($move) {
                return [
                    'status' => 1,
                    'message' => 'File uploaded'
                ];
            }
        }catch(Exception $e){
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
}

if(!function_exists('AwsBucketMessageVideoImageFileUpload')){

    function AwsBucketMessageVideoImageFileUpload($file, $folder = null, $file_type) {
        try{
            $fileExt = $file->getClientOriginalExtension();
            if (strtolower($fileExt) == 'mov') {
                $fileExt = 'mp4';
            }
            $mimetype = $file->getMimetype();

            if (strtolower($mimetype) == "video/quicktime") {
                $mimetype = "video/mp4";
            }
            $featuredfileName = uniqid().'.'.$fileExt;
            $upload = false;
            if($file_type == 'V')
            {
                //get image name
                $image = explode(".",$featuredfileName);
                $imagename = $image[0].'.jpg';

                $destination_thumb_path = $folder.'/thumb/'.$imagename;

                $outputthumb = base_path("storage/app/public/thumbnail/".$imagename);

                exec("ffmpeg -i $file -f image2 $outputthumb 2>&1", $output);

                $upload = Storage::disk('s3')->put($folder.'/'.$featuredfileName, file_get_contents($file),'public');

                if(file_exists($outputthumb)){
                    $originalImage = Image::make($outputthumb)->fit(320, 160)->orientate();
                    $originalImage = $originalImage->stream();

                    $thumb_upload = Storage::disk('s3')->put($destination_thumb_path, $originalImage,'public');
                }

            }
            if($file_type == 'I')
            {
                $thumb_img = Image::make($file)->fit(320, 160)->orientate();
                $thumb_img = $thumb_img->stream();

                $upload_thumb = Storage::disk('s3')->put($folder.'/thumb/'.$featuredfileName, $thumb_img,[
                    'visibility' => 'public',
                    'mimetype' => $mimetype
                ]);

                $originalImage = Image::make($file)->orientate();
                $originalImage = $originalImage->stream();
                $upload = Storage::disk('s3')->put($folder.'/'.$featuredfileName, $originalImage,[
                    'visibility' => 'public',
                    'mimetype' => $mimetype
                ]);
            }
            if($upload)
            {
                $file_array = array('file_name' => $featuredfileName, 'file_extension' => $fileExt);
                return $file_array;
            }
            throw new Exception("Error Processing Request", 1);
        }catch(Exception $e){
            return "";
        }
    }
}

if(!function_exists('AwsDocumentToS3')){

    function AwsDocumentToS3($file, $folder = null, $file_type) {
        try{
            $fileExt = $file->getClientOriginalExtension();
            $name = $file->getClientOriginalName();

            $featuredfileName = uniqid().'.'.$fileExt;

            $upload = Storage::disk('s3')->put($folder.'/'.$featuredfileName, file_get_contents($file),'public');

            if($upload)
            {
                $file_array = array('file_name' => $featuredfileName, 'thumb_name' => $name, 'file_extension' => $fileExt);
                return $file_array;
            }
            throw new Exception("Error Processing Request", 1);
        }catch(Exception $e){
            return "";
        }
    }
}

if(!function_exists('AwsBucketMessageVideoMoveLocalToS3')){

    function AwsBucketMessageVideoMoveLocalToS3($file, $folder = null, $file_type) {
        try{
            $source_path = 'chunk_videos/'.$file;
            $destination_path = $folder.'/'.$file;

            //get image name
            $image = explode(".",$file);
            $imagename = $image[0].'.jpg';

            $destination_thumb_path = $folder.'/thumb/'.$imagename;

            $inputVideo = config('staging_live_config.AWS_URL').'chunk_videos/'.$file;
            $outputthumb = base_path("storage/app/public/thumbnail/".$imagename);

            exec("ffmpeg -i $inputVideo -f image2 $outputthumb 2>&1", $output);

            $exists = Storage::disk('s3')->has($source_path);
            if(empty($exists)) {
                return [
                    'status' => 0,
                    'message' => 'File not exist on our server'
                ];
            }

            $move = Storage::disk('s3')->move($source_path, $destination_path);

            if(file_exists($outputthumb)){
                $originalImage = Image::make($outputthumb)->fit(320, 160)->orientate();
                $originalImage = $originalImage->stream();

                $thumb_upload = Storage::disk('s3')->put($destination_thumb_path, $originalImage,'public');
            }

            if ($move) {
                return [
                    'status' => 1,
                    'message' => 'File uploaded'
                ];
            }
        }catch(Exception $e) {
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
}

if(!function_exists('AwsBucketImageCompressUpload')){

    function AwsBucketimagecompressupload($file, $folder = null) {
        try{
            if(empty($folder)){
                $folder = "uploads";
            }

            $fileExt = $file->getClientOriginalExtension();
            $featuredfileName = uniqid().'.'.$fileExt;

            $originalImage = Image::make($file)->orientate();
            $originalImage = $originalImage->stream();
            $upload = Storage::disk('s3')->put($folder.'/'.$featuredfileName, $originalImage,'public');
            if($upload)
            {
                return $featuredfileName;
            }
            throw new Exception("Error Processing Request", 1);
        }catch(Exception $e){
            return "";
        }
    }
}

if(!function_exists('AwsBucketBase64iImageUpload')){

    function AwsBucketBase64iImageUpload($file, $folder = null) {
        try{
            if(empty($folder)){
                $folder = "uploads";
            }
            $featuredfileName = uniqid().".png";
            $originalImage = Image::make(file_get_contents($file))->orientate();
            $originalImage = $originalImage->stream();

            $thumb_img = Image::make(file_get_contents($file))->fit(200, 200)->orientate();
            $thumb_img = $thumb_img->stream();

            $thumb_upload = Storage::disk('s3')->put($folder.'/thumb/'.$featuredfileName, $thumb_img,'public');

            $upload = Storage::disk('s3')->put($folder.'/'.$featuredfileName, $originalImage,'public');
            if($upload == 1 && $thumb_upload == 1)
            {
                return $featuredfileName;
            }
            throw new Exception("Error Processing Request", 1);
        }catch(Exception $e){
            return "";
        }
    }
}

if(!function_exists('AwsCopyFileToAnotherFolder')){

    function AwsCopyFileToAnotherFolder($oldPath, $newPath, $type = null) {
        try{
            $ext = pathinfo($oldPath, PATHINFO_EXTENSION);
            if (strtolower($ext) == 'mov') {
                $ext = 'mp4';
            }
            $uniq = uniqid();
            $fileName = $uniq.".".$ext;
            $newOriginalPath = $newPath.$fileName;
            $copy = Storage::disk('s3')->copy($oldPath,$newOriginalPath);

            $path_info = pathinfo($oldPath);

            if ($type == 'V') {
                $image = explode(".",$path_info['basename']);
                $thumb_image_name = $image[0].'.jpg';
                $new_thumb_name = $uniq.'.jpg';
            } else {
                $thumb_image_name = $path_info['basename'];
                $new_thumb_name = $uniq.".".$ext;
            }

            $exist = Storage::disk('s3')->exists($path_info['dirname'].'/thumb/'.$thumb_image_name);

            if($exist) {
                $newThumbPath = $newPath.'thumb/'.$new_thumb_name;
                Storage::disk('s3')->copy($path_info['dirname'].'/thumb/'.$thumb_image_name,$newThumbPath);
            }

            if($copy)
            {
                return $fileName;
            }
            throw new Exception("Error Processing Request", 1);
        }catch(Exception $e){
            return "";
        }
    }
}

if(!function_exists('AwsDeleteDirectory')){

    function AwsDeleteDirectory($path) {
        try{
            $delete = Storage::disk('s3')->deleteDirectory($path);
            return $delete;
        }catch(Exception $e){
            return "";
        }
    }

}

if(!function_exists('sendFcmNotification')){

    function sendFcmNotification($user_id, $title, $data, $click=null, $sender_id=null) {
        try{

            $reciever = User::where('id', $user_id)->first();

            if ($reciever->notification_setting == 0) {
                return true;
            }

            $value = env('FCM_SERVER_KEY', 'AAAA4wCGaso:APA91bHR7cEPNCXCFF2qUXQ1xrqGpq862h2CIDOdiJV78Pwxtt-USzgqruP1xA6yolQ1VX5W4AcFkfabFwHunqXlax_vdfk2LEYWWi32cUSjr4GKxRiwkbf0Lf93_6FLPfZ4Lm1teNrr');

            $tokens = UserToken::where('user_id', $user_id)->get();

            if (empty($click)) {
                $click = 'notification';
            }

            $user_uuid = '';
            if (!empty($sender_id)) {
                $user = User::where('id', $sender_id)->first();
                $user_uuid = $user->user_uuid;
            }

            $result = array();
            foreach ($tokens as $token) {
                $notification_data = array("to" => $token->device_token,
                    "notification" => array(
                        "title" => $title,
                        "body" => $data,
                        "badge" => 1,
                        "sound" => "default",
                    ),
                    "data" => array(
                        "click" => $click,
                        "id" => $user_uuid
                    )
                );

                $data_string = json_encode($notification_data);

                $headers = array
                (
                    'Authorization: key=' . $value,
                    'Content-Type: application/json'
                );

                $ch = curl_init();

                curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
                curl_setopt( $ch,CURLOPT_POST, true );
                curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
                curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
                curl_setopt( $ch,CURLOPT_POSTFIELDS, $data_string);
                $result = curl_exec($ch);
                curl_close ($ch);
            }
            return $result;

        }catch(Exception $e){
            return "";
        }
    }


}

if(!function_exists('getAccessToken')) {

    function getAccessToken($code, $url)
    {
        $headers = array
        (
            'Authorization: Basic  '.base64_encode(env('ZOOM_CLIENT_ID').':'.env('ZOOM_CLIENT_SECRET')),
            'Content-Type: application/json'
        );

        $ch = curl_init();

        curl_setopt( $ch,CURLOPT_URL, 'https://zoom.us/oauth/token?grant_type=authorization_code&code='.$code.'&redirect_uri='.$url.'' );
        curl_setopt( $ch,CURLOPT_POST, true );
        curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
        curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
        $result = curl_exec($ch);
        curl_close ($ch);
        return $result;
    }
}

if(!function_exists('scheduledMeeting')) {

    function scheduledMeeting($duration, $password, $start_time, $topic, $agenda, $token)
    {

        $data =  array (
            'topic' => $topic,
            'type' => 2,
            'start_time' => $start_time,
            'duration' => $duration,
            'schedule_for' => NULL,
            'timezone' => 'Asia/Calcutta',
            'password' => $password,
            'agenda' => $agenda,
            'recurrence' => NULL,
            'settings' =>
                array (
                    'host_video' => true,
                    'participant_video' => true,
                    'cn_meeting' => false,
                    'in_meeting' => false,
                    'join_before_host' => true,
                    'mute_upon_entry' => false,
                    'watermark' => true,
                    'use_pmi' => false,
                    'approval_type' => 2,
                    'registration_type' => 3,
                    'audio' => 'both',
                    'auto_recording' => 'none',
                    'enforce_login' => false,
                    'enforce_login_domains' => NULL,
                    'alternative_hosts' => '',
                    'global_dial_in_countries' => NULL,
                    'registrants_email_notification' => false,
                    'waiting_room' => false
                ),
        );

        $data_string = json_encode($data);

        $headers = array
        (
            'Authorization: Bearer  '.$token,
            'Content-Type: application/json'
        );

        $ch = curl_init();

        curl_setopt( $ch,CURLOPT_URL, 'https://api.zoom.us/v2/users/me/meetings' );
        curl_setopt( $ch,CURLOPT_POST, true );
        curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
        curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch,CURLOPT_POSTFIELDS, $data_string);
        $result = curl_exec($ch);
        curl_close ($ch);
        return $result;
    }
}

if(!function_exists('generateSignature')) {

    function generateSignature($api_key, $api_secret, $meeting_number, $role)
    {

        $time = time() * 1000 - 30000;//time in milliseconds (or close enough)

        $data = base64_encode($api_key . $meeting_number . $time . $role);

        $hash = hash_hmac('sha256', $data, $api_secret, true);

        $_sig = $api_key . "." . $meeting_number . "." . $time . "." . $role . "." . base64_encode($hash);

        //return signature, url safe base64 encoded
        return rtrim(strtr(base64_encode($_sig), '+/', '-_'), '=');
    }
}


?>












