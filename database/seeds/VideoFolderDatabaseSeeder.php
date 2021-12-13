<?php

use Illuminate\Database\Seeder;
use App\Models\UserFolder;
use App\Models\Video;
use Illuminate\Support\Facades\Storage;

class VideoFolderDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $parent_folders = UserFolder::whereNull('user_folder_id')->get();
        foreach ($parent_folders as $parent_folder) {
            Storage::disk('s3')->makeDirectory($parent_folder->slug.'/');

            Storage::disk('s3')->makeDirectory($parent_folder->slug.'/videos/');
            Storage::disk('s3')->makeDirectory($parent_folder->slug.'/images/');

            $child_folders = UserFolder::where('user_folder_id', $parent_folder->id)->get();

            foreach ($child_folders as $child_folder) {
                if ($child_folder->folder_type == 1) {
                    Storage::disk('s3')->makeDirectory($parent_folder->slug.'/videos/'.$child_folder->slug);
                    $video_files = Video::where(['user_folder_id' => $child_folder->id, 'file_type' => 1])->get();
                    foreach ($video_files as $video_file) {
                        $folder = base_path("storage/app/public/".$parent_folder->slug.'/videos/'.$child_folder->slug.'/'.$video_file->file_name);
                        $destination = $parent_folder->slug.'/videos/'.$child_folder->slug.'/'.$video_file->file_name;
                        if (file_exists($folder)) {
                            $ext = substr($video_file->file_name, strpos($video_file->file_name, ".") + 1);
                            if (strtolower($ext) == 'mp4') {
                                Storage::disk('s3')->put($destination, fopen($folder, 'r+'),[
                                    'visibility' => 'public',
                                    'mimetype' => 'video/mp4'
                                ]);
                            } else {
                                Storage::disk('s3')->put($destination, fopen($folder, 'r+'), 'public');
                            }
                        }
                    }
                } else {
                    Storage::disk('s3')->makeDirectory($parent_folder->slug.'/images/'.$child_folder->slug);
                    $image_files = Video::where(['user_folder_id' => $child_folder->id, 'file_type' => 2])->get();
                    foreach ($image_files as $image_file) {
                        $folder = base_path("storage/app/public/".$parent_folder->slug.'/images/'.$child_folder->slug.'/'.$image_file->file_name);
                        $destination = $parent_folder->slug.'/images/'.$child_folder->slug.'/'.$image_file->file_name;
                        if (file_exists($folder)) {
                            Storage::disk('s3')->put($destination, fopen($folder, 'r+'), 'public');
                        }
                    }
                }
            }

            $parent_files = Video::whereNull('user_folder_id')->where('user_id', $parent_folder->user_id)->get();

            foreach ($parent_files as $parent_file) {
                if ($parent_file->file_type == 1) {
                    $folder = base_path("storage/app/public/".$parent_folder->slug.'/videos/'.$parent_file->file_name);
                    $destination = $parent_folder->slug.'/videos/'.$parent_file->file_name;
                    if (file_exists($folder)) {

                        $ext = substr($parent_file->file_name, strpos($parent_file->file_name, ".") + 1);
                        if (strtolower($ext) == 'mp4') {
                            Storage::disk('s3')->put($destination, fopen($folder, 'r+'),[
                                'visibility' => 'public',
                                'mimetype' => 'video/mp4'
                            ]);
                        } else {
                            Storage::disk('s3')->put($destination, fopen($folder, 'r+'), 'public');
                        }
                    }

                } else {
                    $folder = base_path("storage/app/public/".$parent_folder->slug.'/images/'.$parent_file->file_name);
                    $destination = $parent_folder->slug.'/images/'.$parent_file->file_name;
                    if (file_exists($folder)) {
                        Storage::disk('s3')->put($destination, fopen($folder, 'r+'), 'public');
                    }
                }
            }
        }
    }
}
