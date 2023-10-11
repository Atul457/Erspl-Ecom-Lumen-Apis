<?php

namespace App\Http\Controllers;

use App\Services\CouponService;
use Illuminate\Http\Request;

class CouponController extends Controller
{

    private CouponService $service;

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function __construct()
    {
        $this->service = new CouponService();
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function applyCoupon(Request $req)
    {
        $res = $this->service->applyCoupon($req);
        return response($res["response"], $res["statusCode"]);
    }
}
