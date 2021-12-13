<?php

namespace App\Http\Controllers\Coach;

use App\Services\Coach\EventCategoryTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class EventCategoryController extends Controller
{
    use EventCategoryTrait;
}
