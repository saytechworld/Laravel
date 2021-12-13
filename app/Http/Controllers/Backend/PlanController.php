<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Backend\SponsoredPlanTrait;

class PlanController extends Controller
{
    use SponsoredPlanTrait;
}
