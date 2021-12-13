<?php

namespace App\Http\Controllers\Coach;

use App\Services\Coach\PaymentTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PaymentController extends Controller
{
    use PaymentTrait;
}
