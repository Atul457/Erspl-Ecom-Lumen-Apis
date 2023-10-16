<?php

namespace App\Http\Controllers;

use App\Services\OfferPriceBundlingService;
use Illuminate\Http\Request;

class OfferPriceBundlingController extends Controller
{

    private OfferPriceBundlingService $service;

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function __construct()
    {
        $this->service = new OfferPriceBundlingService();
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function offerAvailableList(Request $req)
    {
        $res = $this->service->offerAvailableList($req);
        return response($res["response"], $res["statusCode"]);
    }
    


    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function offersList(Request $req)
    {
        $res = $this->service->offersList($req);
        return response($res["response"], $res["statusCode"]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function availOffer(Request $req)
    {
        $res = $this->service->availOffer($req);
        return response($res["response"], $res["statusCode"]);
    }
}
