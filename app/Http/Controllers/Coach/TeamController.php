<?php

namespace App\Http\Controllers\Coach;

use App\Services\Coach\TeamTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TeamController extends Controller
{
    use TeamTrait;
}
