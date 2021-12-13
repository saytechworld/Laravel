<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class DeleteAwsMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:aws-media';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete Aws Media';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $path = base_path("storage/app/chunks/");
        $dir = new \DirectoryIterator($path);
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot()) {
                $now = Carbon::now();
                $file_time =Carbon::parse(date("F d Y H:i:s", filemtime($path.$fileinfo->getFilename())));
                if ($now->diff($file_time)->days > 0) {
                    unlink($path.$fileinfo->getFilename());
                }
            }
        }

        $all_files = Storage::disk('s3')->files('chunk_videos');
        foreach ($all_files as $awsFile) {
            $now = Carbon::now();
            $file_time = Storage::disk('s3')->lastModified($awsFile);
            $file_time =Carbon::parse(date("F d Y H:i:s", $file_time));

            if ($now->diff($file_time)->days > 0) {
                Storage::disk('s3')->delete($awsFile);
            }
        }
    }
}
