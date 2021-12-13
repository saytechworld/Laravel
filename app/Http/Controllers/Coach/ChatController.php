<?php

namespace App\Http\Controllers\Coach;

use App\Models\User;
use App\Services\Coach\UserChatTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ChatController extends Controller
{
	use UserChatTrait;
}
