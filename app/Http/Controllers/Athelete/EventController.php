<?php

namespace App\Http\Controllers\Athelete;

use App\Models\Event;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use  App\Services\Athelete\UserEventTrait;


class EventController extends Controller
{
    use UserEventTrait;
    
}
