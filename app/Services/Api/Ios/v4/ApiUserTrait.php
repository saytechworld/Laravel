<?php

namespace App\Services\Api\Ios\v4;

use App\Notifications\UserConfirmation;
use JWTAuth;
use Exception;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

trait ApiUserTrait
{

    public function getAuthenticatedUser()
    {
        try {

            if (! $user = JWTAuth::parseToken()->authenticate()) {
                $this->WebApiArray['login_required'] = true;
                $this->WebApiArray['message'] = $e->getMessage();
                echo json_encode($this->WebApiArray);
                exit();
            }
        } catch (TokenExpiredException $e) {
            $this->WebApiArray['status'] = false;
            $this->WebApiArray['login_required'] = true;
            $this->WebApiArray['message'] = 'Token is expired.';
            echo json_encode($this->WebApiArray);
            exit();
        } catch (TokenInvalidException $e) {
            $this->WebApiArray['status'] = false;
            $this->WebApiArray['login_required'] = true;
            $this->WebApiArray['message'] = 'Token is invalid.';
            echo json_encode($this->WebApiArray);
            exit();
        } catch (JWTException $e) {
            $this->WebApiArray['status'] = false;
            $this->WebApiArray['login_required'] = true;
            $this->WebApiArray['message'] = 'Token is absent.';
            echo json_encode($this->WebApiArray);
            exit();
        }
        return $user;
    }

    public function sendFirebaseNotification($user_references, $dbname)
    {
        $serviceAccount = ServiceAccount::fromJsonFile(public_path(env('FIREBASE_CREDENTIALS')));
        $firebase = (new Factory)
            ->withServiceAccount($serviceAccount)
            ->withDatabaseUri(env('FIREBASE_DATABASE'))
            ->create();
        $database = $firebase->getDatabase();
        $database->getReference($dbname)->push($user_references);

        return true;
    }

}
