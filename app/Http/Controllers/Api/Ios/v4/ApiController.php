<?php

namespace App\Http\Controllers\Api\Ios\v4;

use App\Services\Api\Ios\v4\CoachInformationTrait;
use App\Services\Api\Ios\v4\EventCategoryTrait;
use App\Services\Api\Ios\v4\FolderTrait;
use App\Services\Api\Ios\v4\NotificationTrait;
use App\Services\Api\Ios\v4\PaymentTrait;
use App\Services\Api\Ios\v4\PhotoUploadTrait;
use App\Services\Api\Ios\v4\PlanTrait;
use App\Services\Api\Ios\v4\TeamTrait;
use App\Services\Api\Ios\v4\UserChatTrait;
use App\Services\Api\Ios\v4\UserEventTrait;
use App\Services\Api\Ios\v4\VideoUploadTrait;
use App\Services\Api\Ios\v4\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Api\Ios\v4\UserTrait;
use App\Services\Api\Ios\v4\ApiUserTrait;

class ApiController extends Controller
{

    public function __construct()
    {
        $this->WebApiArray['status'] = false;
        $this->WebApiArray['message'] = "No record found.";
        $this->WebApiArray['data'] = null;
        $this->WebApiArray['login_required'] = false;
        $this->WebApiArray['subscription'] = false;
    }

    use UserTrait, ApiUserTrait, CoachInformationTrait, TeamTrait, FolderTrait,EventCategoryTrait,
        UserEventTrait, PhotoUploadTrait, VideoUploadTrait, NotificationTrait, PlanTrait,SendsPasswordResetEmails,UserChatTrait,PaymentTrait;
}
