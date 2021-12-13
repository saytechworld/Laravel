<?php

namespace App\Http\Controllers\Frontend;

use App\Models\Chat;
use App\Models\ChatMeeting;
use App\Models\Message;
use App\Models\Support;
use App\Models\UserFolder;
use App\Models\Video;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\ContactUsRequest;
use DB,Exception,Hash,Validator;
use App\Models\ContactUs;
use App\Models\StaticPage;
use App\Models\FAQ;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Image;

class FrontendController extends Controller
{
    
    public function index()
    {	
    	return view('Frontend.index');
    }

    public function fetchStaticPage(Request $request, $page_slug)
    {   
        $static_page = StaticPage::where('slug',$page_slug)->first();
        if (empty($static_page)) {
            return view('errors.404');
        }
    	return view('Frontend.staticpage.index',compact('static_page'));
    }

    public function ContactUs(Request $request)
    {
    	if($request->isMethod('post')){
    		DB::beginTransaction();
    		try {
    			$input = $request->all();
	    		$validator = Validator::make($input, [
			                    'name' => 'required|max:50',
			                    'email' => 'required|email|max:50',
			                    'mobile' => 'required|min:10|max:10',
							    'message' => 'required|max:1000', 
			                ]);

	    		if ($validator->fails()) {
	                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
	            }
	            $contact_us = new ContactUs;
	            if($contact_us->fill($input)->save())
	            {
	            	DB::commit();
                	return redirect()->back()->withSuccess("Thank you for contact us."); 
	            }
	            throw new Exception("Error Processing Request", 1);
	        } catch (Exception $e) {
	            DB::rollback();
	            return redirect()->back()->withInput($request->all())->withError($e->getMessage());
	        }
    	}
    	return view('Frontend.contact_us');	
    }

    public function fetchFAQ(Request $request)
    {   
    	$faqs = FAQ::where('status',1)->get();
    	return view('Frontend.staticpage.faq',compact('faqs'));
    }

    public function support(Request $request)
    {
    	return view('Frontend.staticpage.support');
    }

    public function supportSend(Request $request)
    {
        if($request->isMethod('post')){
            DB::beginTransaction();
            try {
                $input = $request->all();
                $validator = Validator::make($input, [
                    'name' => 'required|max:50',
                    'email' => 'required|email|max:50',
                    'enquiry' => 'required',
                ]);

                if ($validator->fails()) {
                    throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
                }
                $input['ip_address'] = $request->ip();
                $support = new Support();
                if($support->fill($input)->save())
                {
                    Mail::send('email.support', ['enquiry' => $input], function ($m) use ($input) {
                        $m->from(env('MAIL_FROM_ADDRESS'), 'AsportCoach');
                        $m->to(env('MAIL_FROM_ADDRESS'), $input['name'])->subject('Support!');
                    });
                    DB::commit();
                    return redirect()->back()->withSuccess("Thank you for contact us.");
                }
                throw new Exception("Error Processing Request", 1);
            } catch (Exception $e) {
                DB::rollback();
                return redirect()->back()->withInput($request->all())->withError($e->getMessage());
            }
        }
        return view('Frontend.support');
    }
    
    public function fetchCoachList(Request $request)
    {   $input = $request->all();
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

        if (!empty(auth()->user())) {
            $coaches->where('id','!=', auth()->id());
        }

        $coaches =  $coaches->whereHas('roles',function($query){
                        $query->where('role_id',3);
                    })
                    ->where('deleted_status', '!=', 1)
                    ->where('test_user', '!=', 1)
                    ->where(['status' => 1, 'confirmed' => 1])
                    ->orderBy('username','ASC')
                    ->paginate(25);
       // echo "<pre>"; print_r($coaches->total()); exit;
        return view('Frontend.coach_list',compact('coaches'))
            ->with('i', ($request->input('page', 1) - 1) * 25);
    }

    public function fetchUserDetail($slug)
    {
        $user = User::whereHas('roles',function($query){
                        $query->where('role_id',3);
                    })->where('deleted_status', '!=', 1)
            ->where('test_user', '!=', 1)
            ->where('username', $slug)->first();
        if (!empty($user)) {
            return view('Frontend.coach_profile',compact('user'));
        } else {
            return redirect()->back()->withErrors('User not found');
        }
    }

    public function downloadFile($type, $id) {
        ob_end_clean();
        try {
            if ($type == "U") {
                $file = Video::where('id', $id)->first();
                if (empty($file)) {
                    throw new Exception("File not valid.", 1);
                }
                $key = $file->aws_video_uploaded_path;
                $filename = $file->file_name;
            } else {
                $file = Message::where('id', $id)->whereIn('message_type', [2,3,12])->first();
                if (empty($file)) {
                    throw new Exception("File not valid.", 1);
                }
                $key = 'messages/'.$file->message;
                $filename = $file->message;
                if ($type == "D") {
                    $filename = $file->thumbnail;
                }
            }

            $s3Client = Storage::cloud()->getAdapter()->getClient();

            $stream = $s3Client->getObject([
                'Bucket' => config('staging_live_config.AWS_BUCKET'),
                'Key'   => $key
            ]);

            return response($stream['Body'], 200)->withHeaders([
                'Content-Type'        => $stream['ContentType'],
                'Content-Length'      => $stream['ContentLength'],
                'Content-Disposition' => 'attachment; filename="'. $filename .'"'
            ]);
        }catch (Exception $e) {
            return redirect()->back()->withError($e->getMessage());
        }
    }

    public function thumbnailUpload(Request $request) {
            try {
            $videos = Video::where('file_type',1)->get();
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

                        $originalImage = Image::make($outputthumb)->fit(320, 160)->orientate();
                        $originalImage = $originalImage->stream();

                        $thumb_upload = Storage::disk('s3')->put($destination_thumb_path, $originalImage,'public');

                        if ($thumb_upload) {
                            $video->thumbnail = $imagename;
                            $video->save();
                        }
                    }
                }
            }
            $messages = Message::where('message_type', 3)->get();
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
                        $originalImage = Image::make($outputthumb)->fit(320, 160)->orientate();
                        $originalImage = $originalImage->stream();

                        Storage::disk('s3')->put($destination_thumb_path, $originalImage,'public');

                        $message->thumbnail = $imagename;
                        $message->save();
                    }
                }
            }
        }catch (Exception $e) {
            print_r($e->getMessage()); die();
        }
    }

    public function thumbnailImageUpload(Request $request) {
            try {
                $photos = Video::where('file_type',2)->get();
                foreach ($photos as $photo) {
                    if (!empty($photo->aws_video_folder_path)) {

                        $user_parent_directory = UserFolder::whereNull('user_folder_id')->where('user_id',$photo->user_id)->first();

                        $parent_type_folder =  $user_parent_directory->slug.'/images/';

                        $child_type_folder =  !empty($photo->user_folders->slug) ? $photo->user_folders->slug.'/' : '';

                        $exists = Storage::disk('s3')->has($photo->aws_video_uploaded_path);

                        if ($exists) {
                            $thumb_img = Image::make($photo->aws_video_folder_path)->fit(320, 160)->orientate();
                            $thumb_img = $thumb_img->stream();
                            Storage::disk('s3')->put($parent_type_folder.$child_type_folder.'thumb/'.$photo->file_name, $thumb_img,'public');

                            $photo->thumbnail = $photo->file_name;
                            $photo->save();
                        } else {
                            $photo->delete();
                        }
                    }
                }

                $messages = Message::where('message_type', 2)->get();

                foreach ($messages as $message) {
                    if (!empty($message->aws_file_url)) {
                        $exists = Storage::disk('s3')->has('messages/'.$message->message);
                        if ($exists) {
                            $thumb_img = Image::make($message->aws_file_url)->fit(320, 160)->orientate();
                            $thumb_img = $thumb_img->stream();
                            Storage::disk('s3')->put('messages/thumb/'.$message->message, $thumb_img,'public');

                            $message->thumbnail = $message->message;
                            $message->save();
                        }
                    }
                }
        }catch (Exception $e) {
            print_r($e->getMessage()); die();
        }
    }

    public function createChatMeeting(Request $request) {
            try {
                $chats = Chat::where('chat_type', 1)->with('meeting')->get();

                foreach ($chats as $chat){

                    if (empty($chat->meeting)) {
                        $title = 'Video';
                        $password = str_random(6);
                        $duration = 1;
                        $schedule = scheduledMeeting($duration, $password, '2020-24-20T12:02:00Z', $title, $title);
                        dd($schedule);
                        $schedule = json_decode($schedule);
                        if (!isset($schedule->id) || empty($schedule->id)) {
                            throw new Exception("Error in meeting create", 1);
                        }

                        $meeting = new ChatMeeting();
                        $meeting->chat_id = $chat->id;
                        $meeting->meeting_id = (string)$schedule->id;
                        $meeting->meeting_password = $password;
                        $meeting->save();
                    }
                }
        }catch (Exception $e) {
            print_r($e->getMessage()); die();
        }
    }

}
