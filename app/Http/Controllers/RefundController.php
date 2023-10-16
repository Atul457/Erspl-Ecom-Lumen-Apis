<?php

namespace App\Http\Controllers;

use App\Services\RefundService;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    private RefundService $service;

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function __construct()
    {
        $this->service = new RefundService();
    }

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function refundDetails(Request $req)
    {
        $res = $this->service->refundDetails($req);
        return response($res["response"], $res["statusCode"]);
    }
}
