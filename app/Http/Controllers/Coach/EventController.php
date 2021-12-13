<?php

namespace App\Http\Controllers\Coach;

use App\Services\Coach\UserEventTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class EventController extends Controller
{
    use UserEventTrait;
}
