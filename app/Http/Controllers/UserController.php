<?php

namespace App\Http\Controllers;

use App\Helpers\ExceptionHelper;
use App\Helpers\OTPHelper;
use App\Helpers\RequestValidator;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function index()
    {

        return response()->json([
            "data" => null,
            "status" => true,
            "statusCode" => 200,
            "message" => "Welcome to ecom apis"
        ], 200);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function validateRequest(array $data, array $messages, array $validations)
    {

        $validator = Validator::make($data, $validations, $messages);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $error = $errors->first() ?? "Something went wrong";
            throw ValidationException::withMessages([
                'error' => $error,
            ]);
        }

        return $data;
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function getCurrentDateTime()
    {
        $currentDateTime = Date::now();
        return $currentDateTime;
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function loginAccount(Request $req, User $user)
    {

        $defaultOtp = "0000";
        $otp = OTPHelper::generateOtp();

        try {

            $data = RequestValidator::validate(
                $req->input(),
                [
                    'digits' => ':attribute must be of :digits digits',
                    'min' => ':attribute must be of at least :min characters',
                    'exists' => "User with :attribute doesn't exists, please signUp."
                ],
                [
                    "mobile" => "required|digits:10|exists:users",
                    'password' => 'min:6',
                ]
            );

            $mobile = $data["mobile"];
            $password = $data["password"] ?? "";
            $user = $user->select("*")->where("mobile", $mobile)->first();

            // If password is empty then using otp flow
            if (empty($data["password"])) {

                if ($this->isDefaultMobile($data["mobile"]))
                    $otp = $defaultOtp;
                else
                    OTPHelper::sendOTP($otp, $mobile);

                $updated = User::where("mobile", $mobile)->update([
                    "otp" => $otp
                ]);

                if (!$updated)
                    throw ExceptionHelper::somethingWentWrong();

                return response([
                    "data" => null,
                    "status" =>  true,
                    "statusCode" => 200,
                    "messsage" => "OTP Sent Successfully."
                ], 200);
            }

            if (!Hash::check($password, $user->password))
                throw ExceptionHelper::unAuthorized([
                    "message" => "Invalid credentials."
                ]);

            if ($user->status == 0)
                throw ExceptionHelper::unAuthorized([
                    "message" => "Your account is suspended."
                ]);

            $token = Auth::login($user);

            return response([
                "data" => null,
                "status" => true,
                "statusCode" => 200,
                "data" => [
                    "token" => $token
                ],
            ], 200);
        } catch (ValidationException $e) {

            return response([
                "data" => null,
                "status" => false,
                "statusCode" => 422,
                "message" => $e->getMessage(),
            ], 422);
        } catch (ExceptionHelper $e) {
            return response([
                "data" => $e->data,
                "status" => $e->status,
                "statusCode" => $e->statusCode,
                "message" => $e->getMessage(),
            ], $e->statusCode);
        }
    }


    
    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function isDefaultMobile($mobile)
    {
        $numbersWithDefaultOTP = ["8837684275", "9779755869", "6280926975"];
        // $numbersWithDefaultOTP = ["9779755869", "6280926975"];
        $isDefaultNumber = in_array($mobile, $numbersWithDefaultOTP);
        return $isDefaultNumber ? 1 : 0;
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function logout(Request $req)
    {
        try {

            auth()->logout();

            return response([
                "data" => null,
                "status" => true,
                "statusCode" => 200,
                "message" => "Logged out successfully"
            ], 200);
        } catch (ExceptionHelper $e) {
            return response([
                "data" => $e->data,
                "status" => $e->status,
                "message" => $e->getMessage(),
                "statusCode" => $e->statusCode,
            ], $e->statusCode);
        }
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function checkLoginOtp(Request $req)
    {

        $whereQuery = [];

        try {
            $data = RequestValidator::validate(
                $req->input(),
                [
                    'digits' => ':attribute must be of :digits digits',
                ],
                [
                    "otp" => "digits:4|required",
                    "mobile" => "required|digits:10",
                ]
            );

            $whereQuery["mobile"] = $data["mobile"];
            $user = User::where($whereQuery)->first();

            if (!$user)
                throw ExceptionHelper::unAuthorized([
                    "message" => "This mobile does't Registered. Sign up First."
                ]);

            if ($user->otp !== $data["otp"]) {
                User::where($whereQuery)->update([
                    "attempt" => $user->attempt + 1
                ]);
                throw ExceptionHelper::unAuthorized([
                    "message" => "Invalid OTP",
                    "attempt" => $user->attempt + 1
                ]);
            } else
                User::where($whereQuery)->update([
                    "attempt" => 1
                ]);

            $token = Auth::login($user);
            $response["token"] = $token;

            return response([
                "status" => true,
                "statusCode" => 200,
                "data" => $response,
                "message" => "Logged in successfully."
            ], 200);
        } catch (ValidationException $e) {

            return response([
                "data" => null,
                "status" => false,
                "statusCode" => 422,
                "message" => $e->getMessage(),
            ], 422);
        } catch (ExceptionHelper $e) {
            return response([
                "data" => $e->data,
                "status" => $e->status,
                "message" => $e->getMessage(),
                "statusCode" => $e->statusCode,
            ], $e->statusCode);
        }
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

        return response([
            "data" => $profileData
        ]);
    }


    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function checkWalletBalance(Request $req)
    {
        $data_ = null;
        $status = false;
        $message = "Success";
        $userId = $req->user()->id;

        try {

            $data = RequestValidator::validate(
                $req->input(),
                [
                    'numeric' => ':attribute must be be a number',
                ],
                [
                    "orderTotal" => "numeric|required"
                ]
            );

            $result = DB::select(
                'CALL check_balance(?, ?)',
                [$userId, $data["orderTotal"]]
            );
            $result = $result[0];

            if ($result->is_insufficient_balance) {
                $message = "Insuficient balance";
                $data_ = [];
                $data_["requiredBalance"] = floatval($result->required_balance);
            } else
                $status = true;

            return response([
                "data" => $data_,
                "status" => $status,
                "statusCode" => 200,
                "message" => $message,
            ], 200);
        } catch (ValidationException $e) {

            return response([
                "data" => null,
                "status" => false,
                "statusCode" => 422,
                "message" => $e->getMessage(),
            ], 422);
        } catch (ExceptionHelper $e) {
            return response([
                "data" => $e->data,
                "status" => $e->status,
                "message" => $e->getMessage(),
                "statusCode" => $e->statusCode,
            ], $e->statusCode);
        }
    }
}
