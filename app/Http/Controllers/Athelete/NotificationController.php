<?php

namespace App\Http\Controllers\Athelete;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Athelete\NotificationTrait;

class NotificationController extends Controller
{
    use NotificationTrait;
}
