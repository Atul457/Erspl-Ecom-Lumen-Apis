<?php

namespace App\Services;

use App\Constants\StatusCodes;
use App\Helpers\ExceptionHelper;
use App\Helpers\OTPHelper;
use App\Helpers\RequestValidator;
use App\Models\Registration;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Lumen\Http\Request;


// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @todo Document this
 */
class RegistrationService
{

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function isDefaultMobile($mobile)
    {
        $numbersWithDefaultOTP = [];
        $isDefaultNumber = in_array($mobile, $numbersWithDefaultOTP);
        return $isDefaultNumber ? 1 : 0;
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function loginAccount(Request $req, Registration $user)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [
                'digits' => ':attribute must be of :digits digits',
                'min' => ':attribute must be of at least :min characters',
                'exists' => "Registration with :attribute doesn't exists, please signUp."
            ],
            [
                "mobile" => "required|digits:10|exists:tbl_registration",
                'password' => 'min:6',
                'token' => 'string',
            ]
        );

        $defaultOtp = "0000";
        $otp = OTPHelper::generateOtp();

        $mobile = $data["mobile"];
        $tokenId = $data["token"] ?? "";
        $password = $data["password"] ?? "";

        $user = $user->select("id", "dob", "image", "email", "gender", "mobile", "status", "reg_type", "last_name", "alt_mobile", "first_name", "referral_by", "middle_name", "email_status", "password")
            ->where("mobile", $mobile)
            ->first();

        // If password is empty - use otp flow
        if (empty($password)) {

            if ($this->isDefaultMobile($data["mobile"]))
                $otp = $defaultOtp;
            else
                OTPHelper::sendOTP($otp, $mobile);

            $updated = Registration::where("mobile", $mobile)->update([
                "otp" => $otp,
                "token_id" => $tokenId
            ]);

            if (!$updated)
                throw ExceptionHelper::somethingWentWrong();

            return [
                "response" => [
                    "data" => null,
                    "status" =>  true,
                    "statusCode" => StatusCodes::OK,
                    "messsage" => "OTP Sent Successfully."
                ],
                "statusCode" => StatusCodes::OK
            ];
        }

        if (!Hash::check($password, $user->password))
            throw ExceptionHelper::unAuthorized([
                "message" => "Invalid credentials."
            ]);

        if ($user->status == 0)
            throw ExceptionHelper::unAuthorized([
                "message" => "Your account is suspended."
            ]);

        $keysToHide = ['password'];
        $user = $user->makeHidden($keysToHide);
        $token = Auth::setTTL(24 * 10 * 60)->login($user);

        Registration::where("mobile", $mobile)->update([
            "token_id" => $tokenId,
            "access_token" => $token
        ]);

        return [
            "response" => [
                "data" => null,
                "status" => true,
                "statusCode" => 200,
                "data" => [
                    "token" => $token,
                ],
            ],
            "statusCode" => StatusCodes::OK
        ];

        return $token;
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function logout(Request $req)
    {
        auth()->logout();

        return [
            "response" => [
                "data" => null,
                "status" => true,
                "statusCode" => StatusCodes::OK,
                "message" => "Logged out successfully"
            ],
            "statusCode" => StatusCodes::OK
        ];
    }





    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function checkLoginOtp(Request $req)
    {
        $whereQuery = [];

        $data = RequestValidator::validate(
            $req->input(),
            [
                'digits' => ':attribute must be of :digits digits',
            ],
            [
                'token' => 'string',
                "otp" => "digits:4|required",
                "mobile" => "required|digits:10",
                "mobile" => "required|digits:10",
            ]
        );

        $tokenId = $data["token"] ?? "";
        $whereQuery["mobile"] = $data["mobile"];
        $user = Registration::select("id", "dob", "otp", "image", "email", "gender", "mobile", "status", "reg_type", "last_name", "alt_mobile", "first_name", "referral_by", "middle_name", "email_status", "password")
            ->where($whereQuery)
            ->first();

        if (!$user)
            throw ExceptionHelper::unAuthorized([
                "message" => "This mobile does't Registered. Sign up First."
            ]);

        if ($user->otp !== $data["otp"]) {
            Registration::where($whereQuery)->update([
                "attempt" => $user->attempt + 1
            ]);
            throw ExceptionHelper::unAuthorized([
                "message" => "Invalid OTP",
                "attempt" => $user->attempt + 1
            ]);
        } else
            Registration::where($whereQuery)->update([
                "attempt" => 1
            ]);

        $keysToHide = ['password', 'otp'];
        $user = $user->makeHidden($keysToHide);
        $token = Auth::setTTL(24 * 10 * 60)->login($user);

        Registration::where("id", $user->id)->update([
            "token_id" => $tokenId,
            "access_token" => $token
        ]);

        $response["token"] = $token;

        return [
            "response" => [
                "status" => true,
                "data" => $response,
                "statusCode" => StatusCodes::OK,
                "message" => "Logged in successfully."
            ],
            "statusCode" => StatusCodes::OK
        ];
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function getProfile(Request $req)
    {

        $profileData = $req->user();

        // Remove redundant keys
        unset($profileData["otp"]);
        unset($profileData["attempt"]);
        unset($profileData["password"]);
        unset($profileData["reg_type"]);
        unset($profileData["created_at"]);
        unset($profileData["created_at"]);
        unset($profileData["updated_at"]);
        unset($profileData["otp_datetime"]);
        unset($profileData["suspended_datetime"]);

        return [
            "response" => [
                "status" => true,
                "message" => null,
                "data" => $profileData,
                "statusCode" => StatusCodes::OK,
            ],
            "statusCode" => StatusCodes::OK
        ];
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function updateToken(Request $req)
    {
        $tokenWithBearer = $req->header('Authorization') ?? "";
        $token = explode(" ", $tokenWithBearer)[1] ?? "";

        if (empty($token))
            throw ExceptionHelper::unAuthorized([
                "message" => "Invalid token sent"
            ]);

        $whereQuery = [
            "access_token" => $token
        ];

        $user = Registration::select("id", "dob", "image", "email", "gender", "mobile", "status", "reg_type", "last_name", "alt_mobile", "first_name", "referral_by", "middle_name", "email_status")
            ->where($whereQuery)
            ->first();

        if (!$user)
            throw ExceptionHelper::unAuthorized([
                "message" => "Invalid token sent"
            ]);

        $token = Auth::setTTL(24 * 10 * 60)->login($user);

        Registration::where($whereQuery)
            ->update([
                "access_token" => $token
            ]);

        return [
            "response" => [
                "status" => true,
                "message" => null,
                "data" => $token,
                "statusCode" => StatusCodes::OK,
            ],
            "statusCode" => StatusCodes::OK
        ];
    }
}
