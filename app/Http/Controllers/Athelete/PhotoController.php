<?php

namespace App\Http\Controllers\Athelete;

use App\Models\Video;
use App\Services\Athelete\PhotoUploadTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PhotoController extends Controller
{
    use PhotoUploadTrait;
    
}
