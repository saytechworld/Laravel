<?php

namespace App\Http\Controllers\Athelete;

use App\Models\Video;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use  App\Services\Athelete\VideoUploadTrait;

class VideoController extends Controller
{
    use VideoUploadTrait;
    
}
