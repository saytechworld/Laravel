<?php

namespace App\Http\Middleware;

use Closure;
use JWTAuth;
use Exception;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use App\Notifications\UserConfirmation;

class JwtMiddleware extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if ($user->hasRoles('admin') || $user->hasRoles('subadmin')) {
                throw new Exception("You are not authorized to access app.", 1);
            }
            if (!$user->IsActive()) {
                throw new Exception("Account has been disabled. Contact our team.", 1);
            }
            if (!$user->IsDeletedStatus()) {
                throw new Exception("Your account has been deleted. Please contact admin if you wish to reactivate your account at " . env('MAIL_USERNAME') . " ", 1);
            }
            if (!$user->IsConfirmed()) {
                $user->notify(new UserConfirmation($user['confirmation_code']));
                throw new Exception("Your account is not confirmed. A verification link has been sent to your email account (please check the inbox as well as the spam folder).", 1);
            }
            return $next($request);
        } catch (Exception $e) {
            $this->WebApiArray['error'] = true;
            $this->WebApiArray['status'] = false;
            $this->WebApiArray['login_required'] = true;
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            $this->WebApiArray['error'] = true;
            $this->WebApiArray['status'] = false;
            $this->WebApiArray['login_required'] = true;
            $this->WebApiArray['message'] = 'Token is Expired.';
            return response()->json($this->WebApiArray);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            $this->WebApiArray['error'] = true;
            $this->WebApiArray['status'] = false;
            $this->WebApiArray['login_required'] = true;
            $this->WebApiArray['message'] = 'Authorization Token not found.';
            return response()->json($this->WebApiArray);
        }
    }
}
