<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $WebApiArray;

    public function __construct()
    {
        $this->WebApiArray['status'] = false;
        $this->WebApiArray['error'] = true;
        $this->WebApiArray['message'] = "No record found.";
        $this->WebApiArray['data'] = null;
        $this->WebApiArray['login_required'] = false;
    }
}
