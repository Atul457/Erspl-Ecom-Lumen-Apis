<?php

namespace App\Services;

use App\Constants\StatusCodes;
use App\Helpers\ExceptionHelper;
use App\Helpers\OTPHelper;
use App\Helpers\RequestValidator;
use App\Helpers\ResponseGenerator;
use App\Models\Home;
use App\Models\Registration;
use App\Models\WrongReg;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

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
        $numbersWithDefaultOTP = ["7737772424", "8239108159", "8209446253"];
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
                'min' => ':attribute must be of at least :min characters'
            ],
            [
                "mobile" => "required|digits:10",
                'password' => 'min:6',
                'token' => 'string',
            ]
        );

        $defaultOtp = "1234";
        $otp = OTPHelper::generateOtp();

        $mobile = $data["mobile"];
        $tokenId = $data["token"] ?? "";
        $password = $data["password"] ?? "";
        $currentTime = date('Y-m-d H:i:s');

        $user = $user->select("id", "dob", "image", "email", "gender", "mobile", "status", "reg_type", "last_name", "alt_mobile", "first_name", "referral_by", "middle_name", "email_status", "password")
            ->where("mobile", $mobile)
            ->first();

        $sqlHome = Home::select("sms_vendor", "sms_mobile_digit")
            ->first();

        $homeData = $sqlHome->toArray();
        $firstDigit = substr($mobile, 0, 1);

        if ($homeData['sms_mobile_digit'] <= $firstDigit) {

            $sql = Registration::select("status", "attempt", "suspended_datetime")
                ->where("mobile", $mobile);

            if ($sql->count() > 0) {

                $sqlData = $sql
                    ->first()
                    ->toArray();

                if ($sqlData['status'] == 1) {

                    $suspendTime           = $sqlData['suspended_datetime'];
                    $setSuspendTimeEnd     = date('Y-m-d H:i:s', strtotime($sqlData['suspended_datetime'] . '+2 minutes'));
                    $setSuspendCurrentTime = date('Y-m-d H:i:s');
                    $timeFirst             = strtotime($setSuspendCurrentTime);
                    $timeSecond            = strtotime($setSuspendTimeEnd);
                    $differenceInSeconds   = $timeSecond - $timeFirst;

                    $checkValidation  = 1;

                    if (empty($suspendTime)) {
                        $checkValidation = 0;
                    } else {
                        if ($differenceInSeconds <= 0) {

                            Registration::where("mobile", $mobile)
                                ->update([
                                    "attempt" => 0,
                                    "suspended_datetime" => null
                                ]);

                            $checkValidation = 0;
                        }
                    }

                    if ($checkValidation == 0) {

                        $loginAttempt = $sqlData['attempt'] + 1;

                        if ($loginAttempt == 6) {
                            $cDateTime     = date('Y-m-d H:i:s');
                            $secs = $differenceInSeconds % 60;
                            $hrs  = $differenceInSeconds / 60;
                            $mins = $hrs % 60;
                            $remTimeMinutes = sprintf('%02d', (int)$mins);
                            $remTimeSeconds = sprintf('%02d', (int)$secs);
                            $remTime        = $remTimeMinutes . ":" . $remTimeSeconds;

                            Registration::where("mobile", $mobile)
                                ->update([
                                    "attempt" => 0,
                                    "suspended_datetime" => $cDateTime
                                ]);

                            throw ExceptionHelper::error([
                                "statusCode" => StatusCodes::UNAUTHORIZED,
                                "message" => "Your account is locked out to 5 times OTP Request. Wait for " . $remTime . " minutes, and try to login again."
                            ]);
                        } else {

                            Registration::where("mobile", $mobile)
                                ->update([
                                    "attempt" => $loginAttempt,
                                    "otp" => $otp,
                                    "otp_datetime" => $currentTime,
                                    "token_id" => $tokenId
                                ]);

                            // If password is empty - use otp flow
                            if (empty($password)) {

                                if ($this->isDefaultMobile($data["mobile"]))
                                    $otp = $defaultOtp;
                                else
                                    OTPHelper::sendOTP($otp, $mobile);

                                Registration::where("mobile", $mobile)->update([
                                    "otp" => $otp,
                                    "token_id" => $tokenId
                                ]);

                                return ResponseGenerator::generateResponseWithStatusCode(
                                    ResponseGenerator::generateSuccessResponse([
                                        "message" => "OTP Sent Successfully."
                                    ])
                                );
                            }

                            if (!Hash::check($password, $user->password))
                                throw ExceptionHelper::error([
                                    "statusCode" => StatusCodes::UNAUTHORIZED,
                                    "message" => "Invalid credentials."
                                ]);

                            if ($user->status == 0)
                                throw ExceptionHelper::error([
                                    "statusCode" => StatusCodes::UNAUTHORIZED,
                                    "message" => "Your account is suspended."
                                ]);

                            $keysToHide = ['password'];
                            $user = $user->makeHidden($keysToHide);
                            $token = Auth::setTTL(24 * 10 * 60)->login($user);

                            Registration::where("mobile", $mobile)->update([
                                "token_id" => $tokenId,
                                "tInfo_temp" => $token
                            ]);

                            return ResponseGenerator::generateResponseWithStatusCode(
                                ResponseGenerator::generateSuccessResponse([
                                    "data" => [
                                        "token" => $token,
                                    ],
                                ])
                            );
                        }
                    } else {

                        $secs = $differenceInSeconds % 60;
                        $hrs  = (int)($differenceInSeconds / 3600);
                        $mins = (int)(($differenceInSeconds % 3600) / 60);
                        $remTimeMinutes = sprintf('%02d', $mins);
                        $remTimeSeconds = sprintf('%02d', $secs);
                        $remTime = $remTimeMinutes . ":" . $remTimeSeconds;


                        throw ExceptionHelper::error([
                            "statusCode" => StatusCodes::UNAUTHORIZED,
                            "message" => "Your account is locked out to 5 times OTP Request. Wait for " . $remTime . " minutes, and try to login again."
                        ]);
                    }
                } else {
                    throw ExceptionHelper::error([
                        "statusCode" => StatusCodes::UNAUTHORIZED,
                        "message" => "Your Account is Suspended."
                    ]);
                }
            } else {
                WrongReg::insert([
                    "mobile" => $mobile,
                    "platform" => 'Android Application',
                    "datetime" => $currentTime
                ]);

                throw ExceptionHelper::error([
                    "statusCode" => StatusCodes::NOT_FOUND,
                    "message" => "This mobile does't Registered. Please Sign Up."
                ]);
            }
        } else {
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::VALIDATION_ERROR,
                "message" => "Please Enter Valid Mobile Number"
            ]);
        }
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function logout()
    {
        auth()->logout();

        return ResponseGenerator::generateResponseWithStatusCode(
            ResponseGenerator::generateSuccessResponse([
                "message" => "Logged out successfully"
            ])
        );
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
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::UNAUTHORIZED,
                "message" => "This mobile does't Registered. Sign up First."
            ]);

        if ($user->otp !== $data["otp"]) {
            Registration::where($whereQuery)->update([
                "attempt" => $user->attempt + 1
            ]);
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::UNAUTHORIZED,
                "message" => "Invalid OTP",
                "data" => [
                    "attempt" => $user->attempt + 1
                ]
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
            "tInfo_temp" => $token
        ]);

        $response["token"] = $token;

        return ResponseGenerator::generateResponseWithStatusCode(
            ResponseGenerator::generateSuccessResponse([
                "data" => $response,
                "message" => "Logged in successfully."
            ])
        );
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
        unset($profileData["otp"]);
        unset($profileData["attempt"]);
        unset($profileData["password"]);
        unset($profileData["reg_type"]);
        unset($profileData["otp_datetime"]);
        unset($profileData["tInfo_temp"]);
        unset($profileData["suspended_datetime"]);

        return ResponseGenerator::generateResponseWithStatusCode(
            ResponseGenerator::generateSuccessResponse([
                "data" => $profileData,
            ])
        );
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
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::UNAUTHORIZED,
                "message" => "Token not sent"
            ]);

        $whereQuery = [
            "tInfo_temp" => $token
        ];

        $user = Registration::select("id", "dob", "image", "email", "gender", "mobile", "status", "reg_type", "last_name", "alt_mobile", "first_name", "referral_by", "middle_name", "email_status")
            ->where($whereQuery)
            ->first();

        if (!$user)
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::UNAUTHORIZED,
                "message" => "Invalid token sent",
                "data" => null
            ]);

        auth()->logout();

        $token = Auth::setTTL(24 * 10 * 60)->login($user);

        Registration::where($whereQuery)
            ->update([
                "tInfo_temp" => $token
            ]);

        return ResponseGenerator::generateResponseWithStatusCode(
            ResponseGenerator::generateSuccessResponse([
                "data" => [
                    "token" => $token
                ],
            ])
        );
    }
}
