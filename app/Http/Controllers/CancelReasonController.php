<?php

namespace App\Http\Controllers;

use App\Services\CancelReasonService;

class CancelReasonController extends Controller
{

    private CancelReasonService $service;

    
    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function __construct()
    {
        $this->service = new CancelReasonService();
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function reasonList()
    {
        $res = $this->service->reasonList();
        return response($res["response"], $res["statusCode"]);
    }
}
