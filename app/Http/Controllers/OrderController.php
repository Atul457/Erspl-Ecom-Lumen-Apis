<?php

namespace App\Http\Controllers;

use App\Constants\StatusCodes;
use App\Helpers\CommonHelper;
use App\Helpers\ExceptionHelper;
use App\Helpers\OTPHelper;
use App\Helpers\RequestValidator;
use App\Models\Cart;
use App\Models\Employee;
use App\Models\Home;
use App\Models\HsnCode;
use App\Models\NotificationReceiveLogs;
use App\Models\OfferBundling;
use App\Models\OfferPriceBundling;
use App\Models\Order;
use App\Models\OrderCodTransaction;
use App\Models\OrderDeliveryLogs;
use App\Models\OrderEdited;
use App\Models\OrderPrepaidTransaction;
use App\Models\Product;
use App\Models\Registration;
use App\Models\Shop;
use App\Models\Wallet;
use App\Services\OrderService;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @todo Document this
 */
class OrderController extends Controller
{

    private OrderService $service;

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function __construct()
    {
        $this->service = new OrderService();
    }


    
    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function orderList(Request $req)
    {
        $res = $this->service->orderList($req);
        return response($res["response"], $res["statusCode"]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function orderReferenceList(Request $req)
    {
        $res = $this->service->orderReferenceList($req);
        return response($res["response"], $res["statusCode"]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function orderStage(Request $req)
    {
        $res = $this->service->orderStage($req);
        return response($res["response"], $res["statusCode"]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function getOrderStatus(Request $req)
    {
        $res = $this->service->getOrderStatus($req);
        return response($res["response"], $res["statusCode"]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function saveOrder(Request $req)
    {
        $res = $this->service->saveOrder($req);
        return response($res["response"], $res["statusCode"]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function orderCancel(Request $req)
    {
        $res = $this->service->orderCancel($req);
        return response($res["response"], $res["statusCode"]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function orderReturnAcceptPartner(Request $req)
    {
        $res = $this->service->orderReturnAcceptPartner($req);
        return response($res["response"], $res["statusCode"]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function orderReturn(Request $req)
    {
        $res = $this->service->orderReturn($req);
        return response($res["response"], $res["statusCode"]);
    }
    
}
