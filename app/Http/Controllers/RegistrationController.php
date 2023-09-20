<?php

namespace App\Http\Controllers;

use App\Constants\StatusCodes;
use App\Models\Registration;
use App\Services\RegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

class RegistrationController extends Controller
{

    private RegistrationService $service;

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function __construct()
    {
        $this->service = new RegistrationService();
    }


    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function index()
    {
        return response()->json([
            "data" => null,
            "status" => true,
            "statusCode" => StatusCodes::OK,
            "message" => "Welcome to ecom apis"
        ], StatusCodes::OK);
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
    public function loginAccount(Request $req, Registration $user)
    {
        $res = $this->service->loginAccount($req, $user);
        return response($res["response"], $res["statusCode"]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function logout()
    {
        $res = $this->service->logout();
        return response($res["response"], $res["statusCode"]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function checkLoginOtp(Request $req)
    {
        $res = $this->service->checkLoginOtp($req);
        return response($res["response"], $res["statusCode"]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function getProfile(Request $req)
    {
        $res = $this->service->getProfile($req);
        return response($res["response"], $res["statusCode"]);
    }
}
