<?php

namespace App\Http\Controllers\Api\v3;

use App\Services\Api\v3\CoachInformationTrait;
use App\Services\Api\v3\EventCategoryTrait;
use App\Services\Api\v3\FolderTrait;
use App\Services\Api\v3\NotificationTrait;
use App\Services\Api\v3\PaymentTrait;
use App\Services\Api\v3\PhotoUploadTrait;
use App\Services\Api\v3\PlanTrait;
use App\Services\Api\v3\TeamTrait;
use App\Services\Api\v3\UserChatTrait;
use App\Services\Api\v3\UserEventTrait;
use App\Services\Api\v3\VideoUploadTrait;
use App\Services\Api\v3\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Api\v3\UserTrait;
use App\Services\Api\v3\ApiUserTrait;

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
