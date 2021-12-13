<?php

namespace App\Http\Controllers\Coach;

use App\Services\Coach\VideoUploadTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class VideoController extends Controller
{
    use VideoUploadTrait;
}
