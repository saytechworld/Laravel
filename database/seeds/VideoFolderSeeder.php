<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class VideoFolderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        try {
            $path = base_path("storage/app/public/");
            $dir = new \DirectoryIterator($path);
            foreach ($dir as $fileinfo) {
                if ($fileinfo->isDir() && !$fileinfo->isDot()) {
                    Storage::disk('s3')->makeDirectory('test_folder/'.$fileinfo->getFilename().'/');

                    $dir2 = new \DirectoryIterator($path.$fileinfo->getFilename().'/');
                    foreach ($dir2 as $fileinfo2) {
                        if ($fileinfo2->isDir() && !$fileinfo2->isDot()) {
                            Storage::disk('s3')->makeDirectory('test_folder/'.$fileinfo->getFilename().'/'.$fileinfo2->getFilename().'/');

                            $dir3 = new \DirectoryIterator($path.$fileinfo->getFilename().'/'.$fileinfo2->getFilename().'/');
                            foreach ($dir3 as $fileinfo3) {
                                if ($fileinfo3->isDir() && !$fileinfo3->isDot()) {
                                    Storage::disk('s3')->makeDirectory('test_folder/'.$fileinfo->getFilename().'/'.$fileinfo2->getFilename().'/'.$fileinfo3->getFilename().'/');

                                    $files = scandir($path.$fileinfo->getFilename().'/'.$fileinfo2->getFilename().'/'.$fileinfo3->getFilename().'/');
                                    foreach($files as $file)
                                    {
                                        if($file != '.' && $file != '..' && !is_dir($file)) {
                                            $ext = substr($file, strpos($file, ".") + 1);

                                            if (strtolower($ext) == 'mp4') {
                                                Storage::disk('s3')->put('test_folder/'.$fileinfo->getFilename().'/'.$fileinfo2->getFilename().'/'.$fileinfo3->getFilename().'/'
                                                    . $file, fopen($path.$fileinfo->getFilename().'/'.$fileinfo2->getFilename().'/'.$fileinfo3->getFilename().'/'.$file, 'r+'),[
                                                    'visibility' => 'public',
                                                    'mimetype' => 'video/mp4'
                                                ]);
                                            } else {
                                                Storage::disk('s3')->put('test_folder/'.$fileinfo->getFilename().'/'.$fileinfo2->getFilename().'/'.$fileinfo3->getFilename().'/'
                                                    . $file, fopen($path.$fileinfo->getFilename().'/'.$fileinfo2->getFilename().'/'.$fileinfo3->getFilename().'/'.$file, 'r+'),'public');
                                            }
                                        }
                                    }
                                }
                            }

                            $files2 = scandir($path.$fileinfo->getFilename().'/'.$fileinfo2->getFilename().'/');
                            foreach($files2 as $file2)
                            {
                                if($file2 != '.' && $file2 != '..' && !is_dir($file2)) {
                                    $ext = substr($file, strpos($file, ".") + 1);
                                    if (!empty($ext)) {
                                        if (strtolower($ext) == 'mp4') {
                                            Storage::disk('s3')->put('test_folder/'.$fileinfo->getFilename().'/'.$fileinfo2->getFilename().'/'. $file2,
                                                fopen($path.$fileinfo->getFilename().'/'.$fileinfo2->getFilename().'/'.$file2, 'r+'), [
                                                    'visibility' => 'public',
                                                    'mimetype' => 'video/mp4'
                                                ]);
                                        } else {
                                            Storage::disk('s3')->put('test_folder/'.$fileinfo->getFilename().'/'.$fileinfo2->getFilename().'/'. $file2,
                                                fopen($path.$fileinfo->getFilename().'/'.$fileinfo2->getFilename().'/'.$file2, 'r+'), 'public');
                                        }
                                    }
                                }
                            }

                        }
                    }
                }
            }
        } catch (\Exception $e) {
            echo "<pre>"; print_r($e->getMessage());
            exit;
        }
    }
}
