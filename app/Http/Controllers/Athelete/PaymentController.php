<?php
namespace App\Http\Controllers\Athelete;

use App\Http\Requests;
use App\Services\Athelete\PaymentTrait;
use App\Http\Controllers\Controller;

class PaymentController extends Controller
{
   use PaymentTrait;
}
