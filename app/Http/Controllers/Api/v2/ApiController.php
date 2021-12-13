<?php

namespace App\Http\Controllers\Api\v2;

use App\Services\Api\v2\CoachInformationTrait;
use App\Services\Api\v2\EventCategoryTrait;
use App\Services\Api\v2\FolderTrait;
use App\Services\Api\v2\NotificationTrait;
use App\Services\Api\v2\PaymentTrait;
use App\Services\Api\v2\PhotoUploadTrait;
use App\Services\Api\v2\PlanTrait;
use App\Services\Api\v2\TeamTrait;
use App\Services\Api\v2\UserChatTrait;
use App\Services\Api\v2\UserEventTrait;
use App\Services\Api\v2\VideoUploadTrait;
use App\Services\Api\v2\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Api\v2\UserTrait;
use App\Services\Api\v2\ApiUserTrait;

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
