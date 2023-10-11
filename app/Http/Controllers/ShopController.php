<?php

namespace App\Http\Controllers;

use App\Services\ShopService;
use Illuminate\Http\Request;

class ShopController extends Controller
{

    private ShopService $service;

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function __construct()
    {
        $this->service = new ShopService();
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function shopList(Request $req)
    {
        $res = $this->service->shopList($req);
        return response($res["response"], $res["statusCode"]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function shopDetail(Request $req)
    {
        $res = $this->service->shopDetail($req);
        return response($res["response"], $res["statusCode"]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function nearestShopList(Request $req)
    {
        $res = $this->service->nearestShopList($req);
        return response($res["response"], $res["statusCode"]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function searchShopDetail(Request $req)
    {
        $res = $this->service->searchShopDetail($req);
        return response($res["response"], $res["statusCode"]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function searchProductList(Request $req)
    {
        $res = $this->service->searchProductList($req);
        return response($res["response"], $res["statusCode"]);
    }
}
