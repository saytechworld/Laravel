<?php

use Illuminate\Database\Seeder;
use App\Models\UserDetail;
use App\Models\Message;
use Illuminate\Support\Facades\Storage;
use App\Models\StaticPage;

class AwsMediaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = UserDetail::whereNotNull('image')->get();
        foreach ($users as $user) {
            $folder = public_path("/images/users/" . $user->image);
            $destination = 'users/' . $user->image;
            if (file_exists($folder)) {
                Storage::disk('s3')->put($destination, fopen($folder, 'r+'), 'public');
            }
        }

        $static_pages = StaticPage::whereNotNull('featured_image')->get();
        foreach ($static_pages as $static_page) {
            $folder = public_path("/images/staticpages/" . $static_page->featured_image);
            $destination = 'staticpages/' . $static_page->featured_image;
            if (file_exists($folder)) {
                Storage::disk('s3')->put($destination, fopen($folder, 'r+'), 'public');
            }
        }

        $messages = Message::whereIn('message_type', [2, 3])->get();
        foreach ($messages as $message) {
            $folder = base_path("storage/app/public/messages/" . $message->message);
            $destination = 'messages/' . $message->message;
            if (file_exists($folder)) {
                $ext = substr($message->message, strpos($message->message, ".") + 1);
                if (strtolower($ext) == 'mp4') {
                    Storage::disk('s3')->put($destination, fopen($folder, 'r+'), [
                        'visibility' => 'public',
                        'mimetype' => 'video/mp4'
                    ]);
                } else {
                    Storage::disk('s3')->put($destination, fopen($folder, 'r+'), 'public');
                }
            }
        }
    }
}
