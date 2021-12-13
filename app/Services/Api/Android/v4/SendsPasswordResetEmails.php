<?php

namespace App\Services\Api\Android\v4;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Exception, Validator;

trait SendsPasswordResetEmails
{
    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        try {
            $input = $request->all();
            $validator = Validator::make($input,[
                'email'   =>  'required|email|max:150|exists:users,email',
            ]);
            if ($validator->fails()) {
                throw new Exception(implode('<br/>',$validator->errors()->all()), 1);
            }

            $user = User::where('email', $input['email'])->first();

            if ($user->hasRoles('admin') || $user->hasRoles('subadmin')) {
                throw new Exception("You are not authorized to access this panel.", 1);
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

            // We will send the password reset link to this user. Once we have attempted
            // to send the link, we will examine the response then see the message we
            // need to show to the user. Finally, we'll send out a proper response.
            $response = $this->broker()->sendResetLink(
                $request->only('email')
            );

            return $response == Password::RESET_LINK_SENT
                    ? $this->sendResetLinkResponse($request, $response)
                    : $this->sendResetLinkFailedResponse($request, $response);
        } catch (Exception $e) {
            $this->WebApiArray['message'] = $e->getMessage();
            return response()->json($this->WebApiArray);
        }
    }

    /**
     * Validate the email for the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function validateEmail(Request $request)
    {
        $request->validate(['email' => 'required|email|max:50']);
    }

    /**
     * Get the response for a successful password reset link.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendResetLinkResponse(Request $request, $response)
    {
        
        $this->WebApiArray['status'] = true;
        $this->WebApiArray['message'] = "Reset password link has been sent to your email account (please check the inbox as well as the spam folder).";
        return response()->json($this->WebApiArray);
    }

    /**
     * Get the response for a failed password reset link.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendResetLinkFailedResponse(Request $request, $response)
    {
        $this->WebApiArray['status'] = false;
        $this->WebApiArray['message'] = "Error Processing Request.";
        return response()->json($this->WebApiArray);
    }

    /**
     * Get the broker to be used during password reset.
     *
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     */
    public function broker()
    {
        return Password::broker();
    }
}
