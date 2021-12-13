<?php

namespace App\Http\Controllers\Athelete;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use  App\Services\Athelete\CoachInformationTrait;

class HomeController extends Controller
{
    use CoachInformationTrait;

}
