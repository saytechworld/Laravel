<?php

namespace App\Http\Controllers\Coach;

use App\Models\Video;
use App\Services\Coach\PhotoUploadTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PhotoController extends Controller
{
    use PhotoUploadTrait;
    
}
