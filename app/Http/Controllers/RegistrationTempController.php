<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Models\RegistrationTemp;
use App\Helpers\RequestValidator;

use App\Helpers\OTPHelper;
use App\Helpers\ExceptionHelper;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;


// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @TODO Document this
 */
class RegistrationTempController extends Controller
{

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function manageUserInTemp(array $dataToSave)
    {
        $whereQuery = ["mobile" => $dataToSave["mobile"]];
        $exists = RegistrationTemp::where($whereQuery)->exists();
        $insertedOrUpdated = false;

        if (!$exists)
            $insertedOrUpdated = RegistrationTemp::insert($dataToSave);
        else
            $insertedOrUpdated =  (bool)RegistrationTemp::where($whereQuery)->update($dataToSave);

        return $insertedOrUpdated;
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function getUserWithTable(string $mobile)
    {
        $whereQuery = [
            "mobile" => $mobile
        ];

        $userWithTable = [
            "user" => null,
            "existsInRegistrations" => false,
        ];

        $user = Registration::where($whereQuery)->first();
        $userWithTable["user"] =  $user;
        $userWithTable["existsInRegistrations"] =  $user ? true : false;

        if ($user)
            return $userWithTable;

        $user = RegistrationTemp::where($whereQuery)->first();
        $userWithTable["user"] =  $user;
        $userWithTable["existsInRegistrations"] =  $user ? true : false;

        return $userWithTable;
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function resendOtp(Request $req)
    {
        $defaultOtp = "0000";
        $otp = OTPHelper::generateOtp();

        $data = RequestValidator::validate(
            $req->input(),
            ['digits' => ':attribute must be of :digits digits'],
            ["mobile" => "required|digits:10"]
        );

        $userWithTable = $this->getUserWithTable($data["mobile"]);

        if (!$userWithTable["user"])
            throw ExceptionHelper::unAuthorized([
                "message" => "This mobile does't Registered."
            ]);

        if ($this->isDefaultMobile($data["mobile"]))
            $otp = $defaultOtp;
        else
            OTPHelper::sendOTP($otp, $data["mobile"]);

        $updated = Registration::where("mobile", $data["mobile"])->update(["otp" => $otp]);

        return response([
            "data" => null,
            "status" =>  $updated ? true : false,
            "statusCode" => $updated ? 200 : 500,
            "messsage" => $updated ? "OTP Sent Successfully." : "Something went wrong."
        ], $updated ? 200 : 500);
    }




    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function checkRegOtp(Request $req)
    {
        $whereQuery = [];

        $data = RequestValidator::validate(
            $req->input(),
            [
                'digits' => ':attribute must be of :digits digits',
                'unique' => 'Registration with the provided :attribute already exists',
                'exists' => "Registration with provided :attribute doesn't exists, please signUp."
            ],
            [
                "otp" => "digits:4|required",
                "mobile" => "required|digits:10|exists:tbl_registration_temp|unique:tbl_registration",
            ]
        );

        $whereQuery["mobile"] = $data["mobile"];
        $user = RegistrationTemp::where($whereQuery)->first()->toArray();

        if ($user["otp"] !== $data["otp"]) {
            RegistrationTemp::where($whereQuery)->update([
                "attempt" => $user["attempt"] + 1
            ]);
            throw ExceptionHelper::unAuthorized([
                "message" => "Invalid OTP",
            ]);
        } else
            RegistrationTemp::where($whereQuery)->update([
                "attempt" => 1
            ]);

        // Remove redundant keys
        unset($user["id"]);
        unset($user["created_at"]);
        unset($user["updated_at"]);

        $user = Registration::insert($user);
        $user = Registration::where($whereQuery)->first();

        if (!$user)
            throw ExceptionHelper::somethingWentWrong();

        $token = Auth::login($user);
        $response["token"] = $token;

        return response([
            "status" => true,
            "statusCode" => 200,
            "data" => $response,
            "message" => "Logged in successfully.",
        ], 200);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function isDefaultMobile($mobile)
    {
        $numbersWithDefaultOTP = [];
        // $numbersWithDefaultOTP = ["9779755869", "6280926975"];
        $isDefaultNumber = in_array($mobile, $numbersWithDefaultOTP);
        return $isDefaultNumber ? 1 : 0;
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function isReferralByValid($referral_by)
    {
        if (empty($referral_by))
            return true;
        $referralByIsValid = Registration::where("referral_code", $referral_by)->exists();
        return  $referralByIsValid;
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function signupAccount(Request $req)
    {

        $defaultOtp = "0000";
        $referralPostFix = "ERSPL";
        $defaultRegistrationType = "App";

        $data = RequestValidator::validate(
            $req->input(),
            [
                'email' => ':attribute not valid',
                'string' => ':attribute must be a string',
                "in" => ':attribute can only be 0, 1, or 2',
                'required' => ':attribute is a required field',
                'digits' => ':attribute must be of :digits digits',
                'min' => ':attribute must be of at least :min characters',
                'unique' => 'Registration with the provided :attribute already exists',
                'size' => ':attribute must be of :size alpha numeric characters',
            ],
            [
                "gender" => "in:0,1,2",
                "last_name" => "string",
                "middle_name" => "string",
                "dob" => "date_format:Y-m-d",
                'password' => 'required|min:6',
                "referral_by" => "string|size:15",
                "first_name" => "required|string|min:2",
                'email' => 'required|email|unique:tbl_registration,email',
                "alt_mobile" => "unique:tbl_registration,mobile|digits:10",
                "mobile" => "required|unique:tbl_registration,mobile|digits:10",
            ]
        );

        // Hashing
        $password = $data["password"];
        $hashedPassword = Hash::make($password);

        // Add mandatory fields
        $data["attempt"] = 1;
        $data["password"] = $hashedPassword;
        $data["otp"] = OTPHelper::generateOtp();
        $data["reg_type"] = $defaultRegistrationType;
        $data["referral_code"] = $data['mobile'] . $referralPostFix;

        // Validate referral_by
        $isReferralByValid = $this->isReferralByValid($data["referral_by"] ?? "");

        if (!$isReferralByValid)
            throw ExceptionHelper::unAuthorized([
                'message' => "Enter a valid Referral Code."
            ]);

        if ($this->isDefaultMobile($data["mobile"]))
            $data["otp"] = $defaultOtp;

        $insertedOrUpdated = $this->manageUserInTemp($data);

        if (!$insertedOrUpdated)
            throw ExceptionHelper::somethingWentWrong();

        OTPHelper::sendOTP($data["otp"], $data["mobile"]);

        return response([
            "data" => [
                "otp" => $data["otp"]
            ],
            "status" => true,
            "statusCode" => 200,
            "messsage" => "OTP Sent Successfully."
        ], 200);
    }
}
