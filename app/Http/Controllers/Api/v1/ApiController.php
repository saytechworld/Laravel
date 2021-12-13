<?php

namespace App\Http\Controllers\Api\v1;

use App\Services\Api\v1\CoachInformationTrait;
use App\Services\Api\v1\EventCategoryTrait;
use App\Services\Api\v1\FolderTrait;
use App\Services\Api\v1\NotificationTrait;
use App\Services\Api\v1\PaymentTrait;
use App\Services\Api\v1\PhotoUploadTrait;
use App\Services\Api\v1\PlanTrait;
use App\Services\Api\v1\TeamTrait;
use App\Services\Api\v1\UserChatTrait;
use App\Services\Api\v1\UserEventTrait;
use App\Services\Api\v1\VideoUploadTrait;
use App\Services\Api\v1\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Api\v1\UserTrait;
use App\Services\Api\v1\ApiUserTrait;

class ApiController extends Controller
{

    public function __construct()
    {
        $this->WebApiArray['status'] = false;
        $this->WebApiArray['message'] = "No record found.";
        $this->WebApiArray['data'] = null;
        $this->WebApiArray['login_required'] = false;
    }

    use UserTrait, ApiUserTrait, CoachInformationTrait, TeamTrait, FolderTrait,EventCategoryTrait,
        UserEventTrait, PhotoUploadTrait, VideoUploadTrait, NotificationTrait, PlanTrait,SendsPasswordResetEmails,UserChatTrait,PaymentTrait;
}
