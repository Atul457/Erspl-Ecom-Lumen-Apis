<?php

namespace App\Http\Controllers;

use App\Services\PaytmService;


// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @todo Document this
 */
class PaytmController extends Controller
{

    private PaytmService $service;


    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function __construct()
    {
        $this->service = new PaytmService();
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function paytmConfig()
    {
        $res = $this->service->paytmConfig();
        return response($res["response"], $res["statusCode"]);
    }
}
