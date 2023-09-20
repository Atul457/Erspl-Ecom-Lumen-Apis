<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\RegistrationTempService;


// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @TODO Document this
 */
class RegistrationTempController extends Controller
{

    private RegistrationTempService $service;

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function __construct()
    {
        $this->service = new RegistrationTempService();
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function resendOtp(Request $req)
    {
        $res = $this->service->resendOtp($req);
        return response($res["response"], $res["statusCode"]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function checkRegOtp(Request $req)
    {
        $res = $this->service->checkRegOtp($req);
        return response($res["response"], $res["statusCode"]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function signupAccount(Request $req)
    {
        $res = $this->service->signupAccount($req);
        return response($res["response"], $res["statusCode"]);
    }
}
