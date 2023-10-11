<?php

namespace App\Http\Controllers;

use App\Services\FavShopService;
use Illuminate\Http\Request;

class FavShopController extends Controller
{

    private FavShopService $service;


    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function __construct()
    {
        $this->service = new FavShopService();
    }


    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function addFavShop(Request $req)
    {
        $res = $this->service->addFavShop($req);
        return response($res["response"], $res["statusCode"]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function removeFav(Request $req)
    {
        $res = $this->service->removeFav($req);
        return response($res["response"], $res["statusCode"]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function favShopList(Request $req)
    {
        $res = $this->service->favShopList($req);
        return response($res["response"], $res["statusCode"]);
    }
}
