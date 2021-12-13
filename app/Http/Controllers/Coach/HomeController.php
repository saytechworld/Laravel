<?php

namespace App\Http\Controllers\Coach;

use App\Services\Coach\AtheleteInformationTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    use AtheleteInformationTrait;
}
