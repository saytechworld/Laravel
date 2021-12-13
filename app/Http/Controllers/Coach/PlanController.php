<?php

namespace App\Http\Controllers\Coach;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Coach\PlanTrait;

class PlanController extends Controller
{
    use PlanTrait;
}
