<?php

namespace App\Http\Controllers;

use App\Constants\StatusCodes;
use App\Helpers\CommonHelper;
use App\Helpers\ExceptionHelper;
use App\Helpers\RequestValidator;
use App\Helpers\UtilityHelper;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Home;
use App\Models\OfferBundling;
use App\Models\OfferPriceBundling;
use App\Models\Order;
use App\Models\OrderEdited;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopTime;
use App\Models\Registration;
use App\Services\CartService;
use Exception;
use Illuminate\Http\Request;

class CartController extends Controller
{
    private CartService $service;

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function __construct()
    {
        $this->service = new CartService();
    }

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function addCart(Request $req)
    {
        $res = $this->service->addCart($req);
        return response($res["response"], $res["statusCode"]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function removeCart(Request $req)
    {
        $res = $this->service->removeCart($req);
        return response($res["response"], $res["statusCode"]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function repeatCart(Request $req)
    {
        $res = $this->service->repeatCart($req);
        return response($res["response"], $res["statusCode"]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function updateCart(Request $req)
    {
        $res = $this->service->updateCart($req);
        return response($res["response"], $res["statusCode"]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function wishToCart(Request $req)
    {
        $res = $this->service->wishToCart($req);
        return response($res["response"], $res["statusCode"]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function cartList(Request $req)
    {
        $res = $this->service->cartList($req);
        return response($res["response"], $res["statusCode"]);
    }
}
