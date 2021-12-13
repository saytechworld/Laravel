<?php

namespace App\Http\Controllers\Coach;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Coach\NotificationTrait;

class NotificationController extends Controller
{
    use NotificationTrait;
}
