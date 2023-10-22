<?php

namespace App\Services;

use App\Constants\StatusCodes;
use App\Helpers\CommonHelper;
use App\Helpers\ExceptionHelper;
use App\Helpers\OTPHelper;
use App\Helpers\RequestValidator;
use App\Helpers\ResponseGenerator;
use App\Helpers\UtilityHelper;
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
use App\Models\Rating;
use App\Models\Refund;
use App\Models\Registration;
use App\Models\ReturnOrder;
use App\Models\SellerLeger;
use App\Models\Shop;
use App\Models\Wallet;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @todo Document this
 */
class OrderService
{

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function orderList(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [],
            [
                "orderReferenceId" => "required|string"
            ]
        );

        $orderReferenceId = $data["orderReferenceId"];

        $sqlOrder = Order::select("order_id")
            ->where("order_reference", $orderReferenceId)
            ->whereIn("payment_status", [1, 2])
            ->groupBy("order_id")
            ->orderBy("status", "asc");

        $count = $sqlOrder->count();

        if (!$count)
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::NOT_FOUND,
                "message" => "Order List Not Found."
            ]);

        $orderList = array();
        $order_total = 0;

        $sqlOrder = $sqlOrder
            ->get()
            ->toArray();

        foreach ($sqlOrder as $data) {

            $sqlCheck = Order::select("*")
                ->where("order_id",  $data['order_id']);

            $checkData = $sqlCheck
                ->first();

            $orderId = $data['order_id'];

            if (!$checkData)
                throw ExceptionHelper::error([
                    "statusCode" => StatusCodes::NOT_FOUND,
                    "message" => "Product with order_id: $orderId not found"
                ]);

            $checkData = $checkData->toArray();

            if ($checkData['edit_status'] == 1) {

                $sqlOrder1 = OrderEdited::select("*")
                    ->where("order_id",  $data['order_id'])
                    ->where("qty", "!=", 0);

                $orderId =  $data['order_id'];

                $data1 = $sqlOrder1->first();

                if (!$sqlOrder1)
                    ExceptionHelper::error([
                        "statusCode" => StatusCodes::NOT_FOUND,
                        "message" => "order_edited item not found with order_id: $orderId and qty!=0"
                    ]);

                $data1 = $data1->toArray();
                $itemCount = $sqlOrder1->count();

                $sqlTotal = OrderEdited::where("order_id", $data['order_id']);
                $order_total = $sqlTotal->sum('total');
                $order_total = $order_total - $data1['shop_discount'] - $data1['offer_total'];
                $status = $data1['status'];

                if ($status == 3) {

                    $sqlReturnStatus = OrderEdited::select("id")
                        ->where([
                            "order_id" => $data['order_id'],
                            "status" => 7
                        ]);

                    $returnCount = $sqlReturnStatus->count();

                    if ($returnCount > 0)
                        $status = '7';

                    $orderList[] = array(
                        "shopName" => CommonHelper::shopName($data1['shop_id']),
                        "order_id" => str_pad($data1['order_id'], 4, "0", STR_PAD_LEFT),
                        "otp" => $data1['otp'],
                        "order_date" => date('d M, Y h:i A', strtotime($data1['date'])),
                        "order_total" => sprintf('%0.2f', $order_total),
                        "order_status" => $status,
                        "returnStatus" => $data1['return_status'],
                        "itemCount" => $itemCount,
                        "payment_type" => $data1['payment_type'],
                        "delivery_type" => $data1['delivery_type']
                    );
                }
            } else {

                $sqlOrder1 = Order::where("order_id", $data['order_id']);

                $data1 = $sqlOrder1
                    ->first();

                if (!$sqlOrder1)
                    ExceptionHelper::error([
                        "statusCode" => StatusCodes::NOT_FOUND,
                        "message" => "order_edited item not found with order_id: $orderId and qty!=0"
                    ]);

                $data1 = $data1->toArray();

                $itemCount = $sqlOrder1->count();

                $sqlTotal = Order::where("order_id", $data['order_id']);

                $order_total = $sqlTotal->sum("total");

                $order_total = $order_total - $data1['shop_discount'] - $data1['offer_total'];
                $status = $data1['status'];

                if ($status == 3) {

                    $sqlReturnStatus = Order::select("id")
                        ->where([
                            "order_id" => $data['order_id'],
                            "status" => 7
                        ]);

                    $returnCount = $sqlReturnStatus->count();

                    if ($returnCount > 0)
                        $status = '7';

                    $orderList[] = array(
                        "shopName" => CommonHelper::shopName($data1['shop_id']),
                        "order_id" => str_pad($data1['order_id'], 4, "0", STR_PAD_LEFT),
                        "otp" => $data1['otp'],
                        "order_date" => date('d M, Y h:i A', strtotime($data1['date'])),
                        "order_total" => sprintf('%0.2f', $order_total),
                        "order_status" => $status,
                        "returnStatus" => $data1['return_status'],
                        "itemCount" => $itemCount,
                        "payment_type" => $data1['payment_type'],
                        "delivery_type" => $data1['delivery_type']
                    );
                }
            }
        }

        return ResponseGenerator::generateResponseWithStatusCode(
            ResponseGenerator::generateSuccessResponse([
                "data" => [
                    "orderStage" => $status,
                    "orderList" => $orderList,
                ],
            ])
        );
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function orderReferenceList(Request $req)
    {
        $userId = $req->user()->id;

        $sqlOrder = Order::select("order_reference", "order_date", "status", "payment_type", "delivery_type", "date")
            ->where("customer_id", $userId)
            ->whereIn("payment_status", [1, 2])
            ->whereNotNull("order_reference")
            ->groupBy("order_reference", "order_date")
            ->orderBy("order_reference", "desc");

        $count = $sqlOrder->count();

        if (!$count)
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::NOT_FOUND,
                "message" => "Order List Not Found."
            ]);

        $pageCount =  ceil($count / 10);
        $orderReferenceList = array();

        // Disable strict mode temporarily
        DB::statement('SET SESSION sql_mode = ""');

        $sqlOrder = $sqlOrder
            ->get()
            ->toArray();

        // Re-enable strict mode
        DB::statement('SET SESSION sql_mode = "STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"');

        foreach ($sqlOrder as $data) {

            $sqlOrder1 = Order::select("id")
                ->where([
                    "order_reference" => $data['order_reference'],
                    "edit_status" => 0
                ]);

            $itemCount1 = $sqlOrder1->count();

            $sqlOrder2 = OrderEdited::select("id")
                ->where([
                    "order_reference" => $data['order_reference'],
                    "edit_status" => 1
                ])
                ->where("qty", "!=", 0)
                ->whereIn("status", [3, 7]);

            $itemCount2 = $sqlOrder2->count();

            $sqlOrder3 = Order::select("id")
                ->where([
                    "order_reference" => $data['order_reference'],
                    "edit_status" => 1
                ])
                ->whereNotIn("status", [3, 7]);

            $itemCount3 = $sqlOrder3->count();

            $sqlShop = Order::select("id")
                ->where([
                    "order_reference" => $data['order_reference']
                ])
                ->groupBy("shop_id");

            $shopCount = $sqlShop->count();

            $sqlDeliver = Order::select("id")
                ->where([
                    "order_reference" => $data['order_reference'],
                    "status" => 3
                ])
                ->groupBy("order_id");

            $sqlFailed = Order::select("id")
                ->where([
                    "order_reference" => $data['order_reference'],
                    "status" => 4
                ])
                ->groupBy("order_id");

            $sqlCancel = Order::select("id")
                ->where([
                    "order_reference" => $data['order_reference'],
                    "status" => 5
                ])
                ->groupBy("order_id");

            $sqlReject = Order::select("id")
                ->where([
                    "order_reference" => $data['order_reference'],
                    "status" => 6
                ])
                ->groupBy("order_id");

            $sqlReturn = Order::select("id")
                ->where([
                    "order_reference" => $data['order_reference'],
                    "status" => 7
                ])
                ->groupBy("order_id");

            $sqlCancelAdmin = Order::select("id")
                ->where([
                    "order_reference" => $data['order_reference'],
                    "status" => 8
                ])
                ->groupBy("order_id");

            $sqlPlaced = Order::select("id")
                ->where([
                    "order_reference" => $data['order_reference']
                ])
                ->whereIn("status", [0, 1])
                ->groupBy("order_id");

            if ($sqlDeliver->count() == $shopCount)
                $status = "Delivered";
            if ($sqlFailed->count() == $shopCount)
                $status = "Failed";
            else if (
                ($sqlCancel->count() == $shopCount)
                ||
                ($sqlCancelAdmin->count() == $shopCount)
            )
                $status = "Cancelled";
            else if ($sqlReject->count() == $shopCount)
                $status = "Rejected";
            else if ($sqlReturn->count() == $shopCount)
                $status = "Returned";
            else if ($sqlPlaced->count() == $shopCount)
                $status = "Placed";
            else {

                $sqlProcessing = Order::select("id")
                    ->where("order_reference", $data['order_reference'])
                    ->whereIn("status", [0, 1, 2]);

                $sqlCompleted = Order::select("id")
                    ->where("order_reference", $data['order_reference'])
                    ->whereIn("status", [3]);

                if ($sqlProcessing->count() > 0)
                    $status = "Processing";
                else if ($sqlCompleted->count() > 0)
                    $status = "Delivered";
                else
                    $status = "Completed";
            }

            $sqlStatus = Order::select("id")
                ->where("order_reference", $data['order_reference'])
                ->whereIn("status", [0, 1, 2]);

            if ($sqlStatus->count()) {
                if ($data['order_date'] == date('Y-m-d')) {
                    $orderTime = date('Y-m-d H:i:s', strtotime($data['date']));
                    $deliverTime = date('Y-m-d H:i:s');
                    $start_datetime = new DateTime($orderTime);
                    $diff = $start_datetime->diff(new DateTime($deliverTime));
                    $deliveryTime = $diff->i;
                } else
                    $deliveryTime = 61; // Order time greater than 60 mins.
            } else
                $deliveryTime = 62; // Order completed

            $orderIDs = array();
            $couponDiscount = 0;
            $offerDiscount = 0;

            $sqlOrderID = Order::select("order_id", "shop_id", "shop_discount")
                ->where("order_reference", $data['order_reference'])
                ->groupBy("order_id")
                ->get()
                ->toArray();

            foreach ($sqlOrderID as $sqlOrderIDs) {

                $orderIDs[] = [
                    "orderId" => $sqlOrderIDs['order_id'],
                    "shopName" => CommonHelper::shopName($sqlOrderIDs['shop_id'])
                ];
            }

            $order_total = 0;
            $order_total1 = 0;
            $order_total2 = 0;
            $order_total3 = 0;

            $totalData1 = Order::where('order_reference', $data['order_reference'])
                ->where('edit_status', 0);
            $order_total1 = $totalData1->sum("total");

            $sqlTotal2 = OrderEdited::where('order_reference', $data['order_reference'])
                ->where('edit_status', 1)
                ->whereIn("status", [3, 7]);
            $order_total2 = $sqlTotal2->sum("total");

            $sqlTotal3 = OrderEdited::where('order_reference', $data['order_reference'])
                ->where('edit_status', 1)
                ->whereNotIn("status", [3, 7]);
            $order_total3 = $sqlTotal3->sum("total");

            $sqlOrderDis1 = Order::select("shop_discount", "offer_total")
                ->where('order_reference', $data['order_reference'])
                ->where('edit_status', 0)
                ->groupBy("order_id")
                ->get()
                ->toArray();

            foreach ($sqlOrderDis1 as $orderDis1) {
                $couponDiscount = $couponDiscount + $orderDis1['shop_discount'];
                $offerDiscount = $offerDiscount + $orderDis1['offer_total'];
            }

            $sqlOrderDis2 = OrderEdited::select("shop_discount", "offer_total")
                ->where('order_reference', $data['order_reference'])
                ->where('edit_status', 1)
                ->groupBy("order_id")
                ->get()
                ->toArray();

            foreach ($sqlOrderDis2 as $orderDis2) {
                $couponDiscount = $couponDiscount + $orderDis2['shop_discount'];
                $offerDiscount = $offerDiscount + $orderDis2['offer_total'];
            }

            $order_total = ($order_total1 + $order_total2 + $order_total3) - ($couponDiscount + $offerDiscount);
            $itemsCount = $itemCount1 + $itemCount2 + $itemCount3;

            $orderReferenceList[] = array(
                "id" => $data['order_reference'],
                "order_reference" => str_pad($data['order_reference'], 10, "0", STR_PAD_LEFT),
                "order_date" => date('d M, Y h:i A', strtotime($data['date'])),
                "placedDate" => date('Y/m/d H:i:s', strtotime($data['date'])),
                "order_total" => sprintf('%0.2f', $order_total),
                "order_status" => $data['status'],
                "itemCount" => $itemsCount,
                "shopCount" => $shopCount,
                "payment_type" => $data['payment_type'],
                "delivery_type" => $data['delivery_type'],
                "orderStage" => $status,
                "deliveryTime" => $deliveryTime,
                "orderIDs" => $orderIDs
            );
        }

        return ResponseGenerator::generateResponseWithStatusCode(
            ResponseGenerator::generateSuccessResponse([
                "data" => [
                    "count" => $count,
                    "pageCount" => $pageCount,
                    "orderReferenceList" => $orderReferenceList,
                ],
            ])
        );
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function orderStage(Request $req)
    {

        $data = RequestValidator::validate(
            $req->input(),
            [],
            [
                "orderId" => "required|numeric"
            ]
        );

        $orderId = $data['orderId'];

        $sqlOrder = Order::select("*")
            ->where("order_id", $orderId)
            ->groupBy("order_id");

        // Disable strict mode temporarily
        DB::statement('SET SESSION sql_mode = ""');

        $sqlOrder = $sqlOrder
            ->get()
            ->toArray();
        $count = count($sqlOrder);

        // Re-enable strict mode
        DB::statement('SET SESSION sql_mode = "STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"');

        if (!$count)
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::NOT_FOUND,
                "message" => "orders not found via order_id: $orderId and group_by('order_id')"
            ]);

        $orderStatus = null;
        $placedDate = "";
        $approveDate = "";
        $dispatchDate = "";
        $deliverDate = "";

        // Temp fix
        $data = $sqlOrder[0];

        if (!empty($data['date']))
            $placedDate = date('d M,Y H:i A', strtotime($data['date']));
        if (!empty($data['approved_date']))
            $approveDate = date('d M,Y H:i A', strtotime($data['approved_date']));
        if (!empty($data['dispatch_date']))
            $dispatchDate = date('d M,Y H:i A', strtotime($data['dispatch_date']));
        if (!empty($data['delivered_date']))
            $deliverDate = date('d M,Y H:i A', strtotime($data['delivered_date']));
        if ($data['status'] == 0)
            $orderStatus = 0;
        else if ($data['status'] == 1)
            $orderStatus = 1;
        else if ($data['status'] == 2)
            $orderStatus = 2;
        else if ($data['status'] == 3)
            $orderStatus = 3;

        return ResponseGenerator::generateResponseWithStatusCode(
            ResponseGenerator::generateSuccessResponse([
                "data" => [
                    "placedDate" => $placedDate,
                    "approveDate" => $approveDate,
                    "deliverDate" => $deliverDate,
                    "orderStatus" => $orderStatus,
                    "orderId" => $data['order_id'],
                    "dispatchDate" => $dispatchDate,
                    "orderStatus" => $data['order_id'],
                ],
            ])
        );
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function getOrderStatus(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [],
            [
                "orderId" => "required|numeric"
            ]
        );

        $orderId = $data['orderId'];

        $sqlOrder = Order::select("*")
            ->where([
                "order_id" => $orderId
            ])
            ->groupBy("order_id");

        $count = $sqlOrder->count();

        if (!$count)
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::NOT_FOUND,
                "message" => "orders not found via order_id: $orderId and group_by('order_id')"
            ]);

        // Disable strict mode temporarily
        DB::statement('SET SESSION sql_mode = ""');

        $sqlOrder = $sqlOrder
            ->get()
            ->toArray();

        // Re-enable strict mode
        DB::statement('SET SESSION sql_mode = "STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"');

        // Temp fix
        $data = $sqlOrder[0];
        $orderStatus = $data['status'];

        return ResponseGenerator::generateResponseWithStatusCode(
            ResponseGenerator::generateSuccessResponse([
                "data" => [
                    "orderStatus" => $orderStatus,
                ],
            ])
        );
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function saveOrder(Request $req)
    {

        $data = RequestValidator::validate(
            $req->input(),
            [
                'digits' => ':attribute must be of :digits digits',
                'numeric' => ':attribute must contain only numbers',
                'min' => ':attribute must be of at least :min characters',
                'in' => ':attribute must be one of the following values: :values',
            ],
            [
                "name" => "required",
                "mobile" => "required|digits:10",
                "email" => "required|email",
                "deliveryType" => "required|in:1,2,3",
                "pincode" => "required|numeric",
                "address" => "required",
                "city" => "required",
                "state" => "required",
                "flat" => "required",
                "landmark" => "required",
                "latitude" => "required|numeric",
                "longitude" => "required|numeric",
                "addressType" => "required",
                "promocode" => "required",
                "promoDiscount" => "required|numeric",
                "deliveryCharges" => "required|numeric",
                "subTotal" => "required|numeric",
                "offerTotal" => "required|numeric",
                "orderTotal" => "required|numeric",
                "paymentType" => "required|in:COD,PREPAID",
            ]
        );

        $userId = $req->user()->id;
        $name = $data['name'];
        $mobile = $data['mobile'];
        $email = $data['email'];
        $deliveryType = $data['deliveryType'];
        $pincode = $data['pincode'];
        $address = $data['address'];
        $city = $data['city'];
        $state = $data['state'];
        $flat = $data['flat'];
        $landmark = $data['landmark'];
        $latitude = $data['latitude'];
        $longitude = $data['longitude'];
        $addressType = $data['addressType'];
        $promocode = $data['promocode'];
        $promoDiscount = $data['promoDiscount'];
        $deliveryCharges = $data['deliveryCharges'];
        $subTotal = $data['subTotal'];
        $offerTotal = $data['offerTotal'];
        $orderTotal = $data['orderTotal'];
        $paymentType = $data['paymentType'];
        $currentDateTime = date('Y-m-d H:i:s');

        $sql = Order::select(DB::raw("MAX(order_reference) AS order_reference"))
            ->first()
            ->toArray();

        $maxReferenceId = $sql["order_reference"] ?? null;

        if ($maxReferenceId == null)
            $order_reference = 1;
        else
            $order_reference = $maxReferenceId + 1;

        $orderStatus = 0;
        $orderCount = 0;
        $cartCount = 0;
        $offerPrice = null;

        $sqlCart = Cart::where("user_id", $userId)
            ->groupBy("shop_id");

        // Disable strict mode temporarily
        DB::statement('SET SESSION sql_mode = ""');

        $sqlCart = $sqlCart
            ->get()
            ->toArray();

        // Re-enable strict mode
        DB::statement('SET SESSION sql_mode = "STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"');


        foreach ($sqlCart as $sqlCartData) {

            $offerTotal = 0;

            $sqlOfferProductCart = Cart::select("id")
                ->where([
                    "user_id" => $userId,
                    "shop_id" => $sqlCartData['shop_id'],
                    "offer_type" => 2
                ]);

            if ($sqlOfferProductCart->count())
                $offerTotal = $data['offerTotal'];

            $sql = Order::select(DB::raw("MAX(order_id) AS order_id"))
                ->first()
                ->toArray();

            $maxOrderId = $sql["order_id"];

            if ($maxOrderId == null)
                $order_id = 1;
            else
                $order_id = $sql['order_id'] + 1;

            $otp = OTPHelper::generateOtp();

            $sqlShopD = Shop::select("name", "mobile", "image", "city_id")
                ->where("id", $sqlCartData['shop_id']);

            $sqlShopDdata = $sqlShopD
                ->first();

            if (!$sqlShopDdata)
                throw ExceptionHelper::error([
                    "message" => "shop with id: " . $sqlCartData['shop_id'] . " not found"
                ]);

            $sqlShopDdata = $sqlShopDdata
                ->toArray();

            $sql1 = Cart::select("product_id", "qty", "offer_type")
                ->where([
                    "user_id" => $userId,
                    "shop_id" => $sqlCartData['shop_id']
                ]);

            if ($sql1->count()) {

                $shopTotal = 0;
                $shopActualTotal = 0;
                $offerDiscountTotal = 0;
                $basicAmountTotal = 0;
                $orderAmountTotal = 0;
                $tcs = 0;
                $tds = 0;
                $grossAmount = 0;
                $aggregatorCommissionAmount = 0;
                $payableMerchantAmount = 0;

                $sql1 = $sql1
                    ->get()
                    ->toArray();

                foreach ($sql1 as $sqlData) {

                    $offerType = $sqlData['offer_type'];

                    $sqlProduct = Product::select("*")
                        ->where([
                            "id" => $sqlData['product_id'],
                            "status" => 1
                        ]);

                    $sqlProductData = $sqlProduct
                        ->get()
                        ->toArray();

                    if (count($sqlProductData)) {

                        $cartCount++;

                        $sqlProductData = $sqlProduct
                            ->first()
                            ->toArray();

                        $sqlHsnCode = HsnCode::select("tax_rate", "cess")
                            ->where("hsn_code", $sqlProductData['hsn_code']);

                        $sqlHsnCodeData = $sqlHsnCode
                            ->first();

                        if (!$sqlHsnCode)
                            ExceptionHelper::error([
                                "statusCode" => StatusCodes::NOT_FOUND,
                                "message" => "hsn_code item not found with hsn_code: " . $sqlProductData['hsn_code'] . ""
                            ]);

                        $hsnCount = $sqlHsnCode->count();
                        $sqlHsnCodeData = $sqlHsnCodeData->toArray();

                        $taxSlab = $sqlHsnCodeData['tax_rate'];
                        $cessSlab = $sqlHsnCodeData['cess'];

                        $product_id    = $sqlData['product_id'];

                        if ($sqlProductData['price'] == 0)
                            $price = $sqlProductData['sellingprice'];
                        else
                            $price = $sqlProductData['price'];

                        $price1 = $price;
                        $weight = $sqlProductData['weight'] . " " . CommonHelper::uomName($sqlProductData['unit_id']);
                        $qty = $sqlData['qty'];
                        $total = $price1 * $sqlData['qty'];

                        if ($paymentType == 'PREPAID' || $paymentType == 'COD' || $paymentType == 'WALLET') {
                            $shopActualTotal = $shopActualTotal + $total;
                            $orderAmountTotal = $orderAmountTotal + $total;

                            if ($hsnCount > 0) {
                                $basicAmount      = ($total / (100 + $taxSlab + $cessSlab) * 100);
                                $basicAmountTotal = $basicAmountTotal + $basicAmount;
                            } else
                                $basicAmountTotal = $basicAmountTotal + $total;
                        }

                        $sqlOfferBundle = OfferBundling::select("*")
                            ->where([
                                "offer_unique_id" => $sqlProductData['unique_code'],
                                "status" => 1
                            ]);

                        if ($sqlOfferBundle->count()) {

                            $offerBundleData = $sqlOfferBundle
                                ->first()
                                ->toArray();

                            $sqlPrimaryId = Product::select("*")
                                ->where([
                                    "unique_code" =>  $offerBundleData['primary_unique_id'],
                                    "shop_id" =>  $offerBundleData['shop_id'],
                                    "status" => 1
                                ]);

                            $primaryIdData = $sqlPrimaryId
                                ->first();

                            if (!$primaryIdData)
                                throw ExceptionHelper::error([
                                    "message" => "product not found with unique_code: " . $offerBundleData['primary_unique_id'] . ", shop_id: " . $offerBundleData['shop_id'] . " and status = 1"
                                ]);

                            $primaryIdData = $primaryIdData
                                ->toArray();

                            $sqlPrimaryQty = Cart::select("qty")
                                ->where([
                                    "product_id" => $primaryIdData['id'],
                                    "shop_id" => $sqlCartData['shop_id'],
                                    "user_id" => $userId
                                ]);

                            $primaryQtyData = $sqlPrimaryQty
                                ->first();

                            if (!$primaryQtyData)
                                throw ExceptionHelper::error([
                                    "message" => "cart not found with product_id: " . $primaryIdData['id'] . ", shop_id: " . $sqlCartData['shop_id'] . " and user_id = " . $userId . ""
                                ]);

                            $primaryQtyData = $primaryQtyData
                                ->toArray();

                            $offerId = $offerBundleData['id'];
                            $offerPrice  = $offerBundleData['offer_amount'];
                            $offerDiscount = $price1 - $offerBundleData['offer_amount'];
                            $offerPrimaryId = $primaryIdData['id'];
                            $offerPrimaryQty = $primaryQtyData['qty'];
                        } else {
                            $offerId = NULL;
                            $offerPrice = NULL;
                            $offerDiscount = NULL;
                            $offerPrimaryId = NULL;
                            $offerPrimaryQty = NULL;

                            $sqlPriceOfferCheck = OfferPriceBundling::select("*")
                                ->where([
                                    "offer_unique_id" => $sqlProductData['unique_code'],
                                    "status" => 1
                                ]);

                            if ($sqlPriceOfferCheck->count()) {

                                $priceOfferCheckData   = $sqlPriceOfferCheck
                                    ->first()
                                    ->toArray();
                                $offerId = $priceOfferCheckData['id'];
                                $offerPrice = $priceOfferCheckData['offer_amount'];
                                $offerDiscount = $price1 - $priceOfferCheckData['offer_amount'];
                            }
                        }


                        $offerDiscountTotal = $offerDiscountTotal + $offerDiscount;

                        if ($offerType == 0)
                            $shopTotal  = $shopTotal + $total;

                        if ($qty) {

                            if ($paymentType == 'COD' || $paymentType == 'WALLET') {

                                $product = new Order();
                                $product->order_type = 'app';
                                $product->otp = $otp;
                                $product->order_reference = $order_reference;
                                $product->order_date = $currentDateTime;
                                $product->customer_id = $userId;
                                $product->shop_id = $sqlCartData['shop_id'];
                                $product->shop_city_id = $sqlShopDdata['city_id'];
                                $product->name = $name;
                                $product->mobile = $mobile;
                                $product->email = $email;
                                $product->pincode = $pincode;
                                $product->flat = $flat;
                                $product->landmark = $landmark;
                                $product->latitude = $latitude;
                                $product->longitude = $longitude;
                                $product->address = $address;
                                $product->state = $state;
                                $product->city = $city;
                                $product->address_type = $addressType;
                                $product->status = $orderStatus;
                                $product->payment_status = 1;
                                $product->payment_type = $paymentType;
                                $product->order_id = $order_id;
                                $product->product_id = $product_id;
                                $product->product_barcode = $sqlProductData['barcode'];
                                $product->product_name = $sqlProductData['name'];
                                $product->weight = $weight;
                                $product->basic_price = $basicAmount;
                                $product->price = $price;
                                $product->mrp = $sqlProductData['sellingprice'];
                                $product->qty = $qty;
                                $product->total = $total;
                                $product->hsn_code = $sqlProductData['hsn_code'];
                                $product->tax_rate = $taxSlab;
                                $product->cess_rate = $cessSlab;
                                $product->delivery_type = $deliveryType;
                                $product->delivery_charge = $deliveryCharges;
                                $product->order_total = $orderTotal;
                                $product->offer_type = $offerType;
                                $product->offer_id = $offerId;
                                $product->offer_price = $offerPrice;
                                $product->offer_discount = $offerDiscount;
                                $product->offer_primary_id = $offerPrimaryId;
                                $product->offer_primary_qty = $offerPrimaryQty;
                                $product->offer_total = $offerTotal;
                                $product->coupon = $promocode;
                                $product->coupon_discount = $promoDiscount;
                                $product->date = $currentDateTime;

                                // Save the product instance to the database
                                $save =   $product->save();

                                if ($save)
                                    $orderCount++;
                            } else {

                                $product = new Order();
                                $product->order_type = 'app';
                                $product->otp = $otp;
                                $product->order_reference = $order_reference;
                                $product->order_date = $currentDateTime;
                                $product->customer_id = $userId;
                                $product->shop_id = $sqlCartData['shop_id'];
                                $product->shop_city_id = $sqlShopDdata['city_id'];
                                $product->name = $name;
                                $product->mobile = $mobile;
                                $product->email = $email;
                                $product->pincode = $pincode;
                                $product->flat = $flat;
                                $product->landmark = $landmark;
                                $product->latitude = $latitude;
                                $product->longitude = $longitude;
                                $product->address = $address;
                                $product->state = $state;
                                $product->city = $city;
                                $product->address_type = $addressType;
                                $product->status = $orderStatus;
                                $product->payment_status = 0;
                                $product->payment_type = $paymentType;
                                $product->order_id = $order_id;
                                $product->product_id = $product_id;
                                $product->product_barcode = $sqlProductData['barcode'];
                                $product->product_name = $sqlProductData['name'];
                                $product->weight = $weight;
                                $product->basic_price = $basicAmount;
                                $product->price = $price;
                                $product->mrp = $sqlProductData['sellingprice'];
                                $product->qty = $qty;
                                $product->total = $total;
                                $product->hsn_code = $sqlProductData['hsn_code'];
                                $product->tax_rate = $taxSlab;
                                $product->cess_rate = $cessSlab;
                                $product->delivery_type = $deliveryType;
                                $product->delivery_charge = $deliveryCharges;
                                $product->order_total = $orderTotal;
                                $product->offer_type = $offerType;
                                $product->offer_id = $offerId;
                                $product->offer_price = $offerPrice;
                                $product->offer_discount = $offerDiscount;
                                $product->offer_primary_id = $offerPrimaryId;
                                $product->offer_primary_qty = $offerPrimaryQty;
                                $product->offer_total = $offerTotal;
                                $product->coupon = $promocode;
                                $product->coupon_discount = $promoDiscount;
                                $product->date = $currentDateTime;

                                $sqlOrder =  $product->save();

                                if ($sqlOrder)
                                    $orderCount++;
                            }
                        }
                    }
                }

                if (!empty($promoDiscount))
                    $shopDiscount = sprintf('%0.2f', (($shopTotal * $promoDiscount) / $subTotal));
                else
                    $shopDiscount = 0;

                $shopTotalDiscounted = ($shopTotal + $offerPrice) - $shopDiscount;
                $shopActualTotal = sprintf('%0.2f', $shopActualTotal - ($offerDiscountTotal + $shopDiscount));

                Order::where("order_id", $order_id)
                    ->update([
                        "shop_total" => $shopTotal,
                        "shop_actual_total" => $shopActualTotal,
                        "shop_discount" => $shopDiscount,
                    ]);


                /*START REFERRAL BOUNS CODE*/

                $sqlhome = Home::select("referral_amount")->first();
                $sqlhomeData = $sqlhome->toArray();

                $sqlReferral = Registration::select("referral_by", "referral_code")
                    ->where("id", $userId)
                    ->where("referral_by", "!=", "")
                    ->where("referral_status", "0");

                if ($sqlReferral->count())
                    Order::where("order_reference", $order_reference)
                        ->update([
                            "referral_bonus" =>  $sqlhomeData['referral_amount']
                        ]);
                else
                    Order::where("order_reference", $order_reference)
                        ->update([
                            "referral_bonus" => "0"
                        ]);

                /*END REFERRAL BOUNS CODE*/


                if ($paymentType == 'PREPAID') {
                    /*INVOICE SETTLEMENT CALCULATION STORE START*/

                    $payTmComm = ($orderAmountTotal * 1.60) / 100;
                    $payTmCommGst = ($payTmComm * 18) / 100;
                    $grossAmount  = 0.00;
                    $tcs = ($basicAmountTotal * 1) / 100;
                    $tds = ($basicAmountTotal * 1) / 100;
                    $aggregatorCommissionAmount = $tcs + $tds;
                    $payableMerchantAmount = 0.00;

                    if ($basicAmountTotal > 0) {

                        $orderPrepaidTransaction = new OrderPrepaidTransaction();
                        $orderPrepaidTransaction->order_date = $currentDateTime;
                        $orderPrepaidTransaction->order_reference = $order_reference;
                        $orderPrepaidTransaction->order_id = $order_id;
                        $orderPrepaidTransaction->shop_id = $sqlCartData['shop_id'];
                        $orderPrepaidTransaction->basic_amount = $basicAmountTotal;
                        $orderPrepaidTransaction->gross_amount = $grossAmount;
                        $orderPrepaidTransaction->aggregator_commission_amount = $aggregatorCommissionAmount;
                        $orderPrepaidTransaction->payable_merchant_amount = $payableMerchantAmount;
                        $orderPrepaidTransaction->total_payout_from_nodal_account = $grossAmount;
                        $orderPrepaidTransaction->tcs = $tcs;
                        $orderPrepaidTransaction->tds = $tds;

                        $orderPrepaidTransaction->save();
                    }

                    /*INVOICE SETTLEMENT CALCULATION STORE END*/
                }

                if ($paymentType == 'COD') {

                    /*INVOICE SETTLEMENT CALCULATION STORE START*/

                    $payTmComm    = 0;
                    $payTmCommGst = 0;
                    $grossAmount  = $orderAmountTotal;
                    $tcs          = ($basicAmountTotal * 1) / 100;
                    $tds          = ($basicAmountTotal * 1) / 100;
                    $aggregatorCommissionAmount = $tcs + $tds;
                    $payableMerchantAmount      = $grossAmount - $aggregatorCommissionAmount;
                    if ($basicAmountTotal > 0) {

                        $orderCodTransaction = new OrderCodTransaction();
                        $orderCodTransaction->order_date = $currentDateTime;
                        $orderCodTransaction->order_reference = $order_reference;
                        $orderCodTransaction->order_id = $order_id;
                        $orderCodTransaction->shop_id = $sqlCartData['shop_id'];
                        $orderCodTransaction->basic_amount = $basicAmountTotal;
                        $orderCodTransaction->gross_amount = $grossAmount;
                        $orderCodTransaction->aggregator_commission_amount = $aggregatorCommissionAmount;
                        $orderCodTransaction->payable_merchant_amount = $payableMerchantAmount;
                        $orderCodTransaction->total_payout_from_nodal_account = $grossAmount;
                        $orderCodTransaction->tcs = $tcs;
                        $orderCodTransaction->tds = $tds;

                        $orderCodTransaction->save();
                    }

                    /*INVOICE SETTLEMENT CALCULATION STORE END*/

                    $orderDeliveryLogs = new OrderDeliveryLogs();
                    $orderDeliveryLogs->order_id = $order_id;
                    $orderDeliveryLogs->remark = 'Order Placed By Customer';
                    $orderDeliveryLogs->cust_id = $userId;
                    $orderDeliveryLogs->datetime = $currentDateTime;
                    $orderDeliveryLogs->save();
                }



                if ($paymentType == 'WALLET') {

                    $wallet = new Wallet();
                    $wallet->customer_id = $userId;
                    $wallet->order_reference = $order_reference;
                    $wallet->order_id = $order_id;
                    $wallet->amount = $shopTotalDiscounted;
                    $wallet->remark = 'Order Placed - ' . $order_id;
                    $wallet->payment_status = 2;
                    $wallet->date = $currentDateTime;

                    $sqlWallet = $wallet->save();

                    if ($sqlWallet) {

                        $sqlReg = Registration::select("wallet_balance")
                            ->where("id", $userId);

                        $regData = $sqlReg
                            ->first()
                            ->toArray();

                        $updateBalance = $regData['wallet_balance'] - $shopTotalDiscounted;

                        Registration::where("id", $userId)
                            ->update([
                                "wallet_balance" => $updateBalance
                            ]);
                    }
                }
            }
        }


        if (!(!empty($orderCount) && ($orderCount == $cartCount))) {

            Order::where("order_reference", $order_reference)
                ->delete();

            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::NOT_FOUND,
                "message" => "Order Not Placed. Try Again."
            ]);
        }

        if ($paymentType == 'COD' || $paymentType == 'WALLET') {
            Cart::where("user_id", $userId)
                ->delete();

            if ($paymentType == 'WALLET')
                Wallet::where("order_reference", $order_reference)
                    ->update([
                        "status" => 1
                    ]);

            // Disable strict mode temporarily
            DB::statement('SET SESSION sql_mode = ""');

            $sqlOrderShop = Order::select("order_date", "shop_id", "shop_city_id", "order_id")
                ->where("order_reference", $order_reference)
                ->groupBy("shop_id")
                ->get()
                ->toArray();

            // Re-enable strict mode
            DB::statement('SET SESSION sql_mode = "STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"');

            foreach ($sqlOrderShop as $orderShopData) {

                /* Send Notification for ShopKeeper */
                $title = "Congratulations! You Have Received New Order";
                $body  = "Order ID : " . $orderShopData['order_id'];

                CommonHelper::ceoNewOrderNotification($title, $body, $orderShopData['order_id'], $orderShopData['shop_id']);

                $notificationReceiveLogs = new NotificationReceiveLogs();
                $notificationReceiveLogs->order_id = $orderShopData['order_id'];
                $notificationReceiveLogs->sent_remark = 'Sent';
                $notificationReceiveLogs->sent_time = $currentDateTime;
                $notificationReceiveLogs->datetime = $currentDateTime;
                $notificationReceiveLogs->save();

                /* Send Notification for Delivery Boy */
                $title = "New Pending Order. Order ID: " . $orderShopData['order_id'];
                $body  = "Open Application";

                $sqlToken = Employee::where([
                    "designation_id" => 2,
                    "status" => 1,
                    "assign_status" => 0,
                    "online_status" => 1,
                    "city_id" => $orderShopData['shop_city_id']
                ])
                    ->get()
                    ->toArray();

                foreach ($sqlToken as $sqlTokenData) {
                    CommonHelper::starPendingOrderNotification($title, $body, $orderShopData['order_id'], $sqlTokenData['token_id']);
                }
            }
        }

        return ResponseGenerator::generateResponseWithStatusCode(
            ResponseGenerator::generateSuccessResponse([
                "data" => [
                    "orderId" => $order_reference,
                    "orderTotal" => $orderTotal,
                    "orderCount" => $orderCount,
                    "paymentMode" => $paymentType,
                    "shopName" => $sqlShopDdata['name'],
                    "shopMobile" => $sqlShopDdata['mobile'],
                    "image" => url("shops") . "/" . $sqlShopDdata['image'],
                    "address" => $name . " " . $flat . " " . $landmark,
                    "time" => date('h:i A'),
                    "mobile" => $mobile,
                    "date" => date('M d, Y', strtotime($currentDateTime))
                ],
                "message" => "Order Saved Successfully."
            ])
        );
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function orderCancel(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [
                "orderId.exists" => "order with id: :input doesn't exist",
                "reasonId.exists" => "cancel reason with id: :input doesn't exist"
            ],
            [
                "orderId" => "required|numeric|exists:tbl_order,order_id",
                "reasonId" => "numeric|exists:tbl_cancel_reason,id",
                "remark" => "string"
            ]
        );

        $currentDate = date('Y-m-d H:i:s');
        $orderId     = round($data['orderId']);
        $reasonId    = $data['reasonId'];
        $remark      = $data['remark'];
        $date        = date('Y-m-d H:i:s');

        $sql = Order::select("*", "total as sum(total)")
            ->where("order_id", $orderId)
            ->first()
            ->toArray();

        $data = $sql;

        if (!($data['status'] < 2))
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::UNAUTHORIZED,
                "message" => "Order Already Dispatched or Cancelled"
            ]);

        $sqlDelete = Order::where("order_id", $orderId)
            ->update([
                "status" => 5,
                "cancel_status" => 1,
                "reason_id" => $reasonId ?? null,
                "cancel_remark" => $remark ?? null,
                "cancel_date" => $date
            ]);

        if (!$sqlDelete)
            throw ExceptionHelper::error([
                "message" => "The order could be updated"
            ]);

        $refundAmount = $data['sum(total)'];

        OrderEdited::where("order_id", $orderId)
            ->update([
                "status" => 5,
                "cancel_status" => 1,
                "reason_id" => $reasonId ?? null,
                "cancel_remark" => $remark ?? null,
                "cancel_date" => $date
            ]);

        Employee::where("id", $data['deliveryboy_id'])
            ->update([
                "assign_status" => 0
            ]);

        OrderDeliveryLogs::insert([
            "order_id" => $orderId,
            "remark" => "Order Canceled By Customer",
            "cust_id" => $data["customer_id"],
            "datetime" => $date
        ]);

        $sqlTxn = OrderPrepaidTransaction::select("tcs", "tds")
            ->where("order_id", $orderId)
            ->first();

        if (!$sqlTxn)
            throw ExceptionHelper::error([
                "message" => "prepaid transaction not found via order_id: $orderId"
            ]);

        $txnData = $sqlTxn->toArray();
        $creditAmount = $txnData['tcs'] + $txnData['tds'];

        if ($data['edit_status'] == 1 && $data['edit_confirm'] == 1) {

            $sqlOrderEdit = OrderEdited::select("total as sum(total)")
                ->where("order_id", $orderId)
                ->first();

            if (!$sqlOrderEdit)
                throw ExceptionHelper::error([
                    "message" => "item not found in order edited table where order_id: $orderId"
                ]);

            $sqlOrderEditData = $sqlOrderEdit->toArray();
            $refundAmount     = $sqlOrderEditData['sum(total)'];

            $sqlTxn = OrderPrepaidTransaction::select("tcs", "tds")
                ->where("order_id", $orderId)
                ->first();

            $txnData = $sqlTxn->toArray();
            $creditAmount = $txnData['edit_tcs'] + $txnData['edit_tds'];
        }


        if ($data['payment_type'] == 'PREPAID' || $data['payment_type'] == 'WALLET') {

            $sqlEditTotal = Order::select("total as sum(total)", "shop_discount", "offer_total")
                ->where("order_id", $orderId);

            $editTotalData = $sqlEditTotal
                ->get()
                ->toArray();

            if ($data['edit_status'] == 1 && $data['edit_confirm'] == 1) {

                $sqlEditTotal = OrderEdited::select("total as sum(total)", "shop_discount", "offer_total")
                    ->where("order_id", $orderId)
                    ->first();

                if (!$sqlEditTotal)
                    throw ExceptionHelper::error([
                        "message" => "item not found in order edited table where order_id: $orderId"
                    ]);
            }

            $editTotalData = $sqlEditTotal->toArray();
            $refundAmount1 = $editTotalData['sum(`total`)'] - ($editTotalData['shop_discount'] + $editTotalData['offer_total']);
            $refundAmount1 = sprintf('%0.2f', $refundAmount1);
        }

        if ($data['payment_type'] == 'PREPAID') {

            $refund = new Refund();
            $refund->order_date = $data['order_date'];
            $refund->customer_id = $data['customer_id'];
            $refund->customer_name = $data['name'];
            $refund->order_reference = $data['order_reference'];
            $refund->order_id = $data['order_id'];
            $refund->incomimg_txn_id = $data['paytm_txn_id'];
            $refund->source = $data['payment_mode'];
            $refund->orignal_order_total = $refundAmount;
            $refund->edit_order_total = 0;
            $refund->reason = 'ORDER CANCELLED BY CUSTOMER';
            $refund->refund_amount = $refundAmount1;
            $refund->status = 0;
            $refund->save();

            $sellerLedger = new SellerLeger();
            $sellerLedger->order_date = $data['order_date'];
            $sellerLedger->shop_id = $data['shop_id'];
            $sellerLedger->shop_name = CommonHelper::shopName($data['shop_id']);
            $sellerLedger->shop_city_id = $data['shop_city_id'];
            $sellerLedger->order_reference = $data['order_reference'];
            $sellerLedger->order_id = $data['order_id'];
            $sellerLedger->transaction_detail = $data['paytm_txn_id'];
            $sellerLedger->payment_mode = $data['payment_mode'];
            $sellerLedger->particular = 'ORDER CANCELLED - ' . $data['order_id'];
            $sellerLedger->debit = $refundAmount;
            $sellerLedger->credit = 0;
            $sellerLedger->datetime = $currentDate;
            $sellerLedger->case = 0;
            $sellerLedger->save();

            $sellerLedger = new SellerLeger();
            $sellerLedger->order_date = $data['order_date'];
            $sellerLedger->shop_id = $data['shop_id'];
            $sellerLedger->shop_name = CommonHelper::shopName($data['shop_id']);
            $sellerLedger->shop_city_id = $data['shop_city_id'];
            $sellerLedger->order_reference = $data['order_reference'];
            $sellerLedger->order_id = $data['order_id'];
            $sellerLedger->transaction_detail = $data['paytm_txn_id'];
            $sellerLedger->payment_mode = $data['payment_mode'];
            $sellerLedger->particular = 'TDS TCS REVERSAL - ' . $data['order_id'];
            $sellerLedger->debit = 0;
            $sellerLedger->credit = $creditAmount;
            $sellerLedger->datetime = $currentDate;
            $sellerLedger->case = 0;
            $sellerLedger->save();
        }


        if ($data['payment_type'] == 'WALLET') {

            $sellerLedger = new SellerLeger();
            $sellerLedger->order_date = $data['order_date'];
            $sellerLedger->shop_id = $data['shop_id'];
            $sellerLedger->shop_name = CommonHelper::shopName($data['shop_id']);
            $sellerLedger->shop_city_id = $data['shop_city_id'];
            $sellerLedger->order_reference = $data['order_reference'];
            $sellerLedger->order_id = $data['order_id'];
            $sellerLedger->transaction_detail = $data['paytm_txn_id'];
            $sellerLedger->payment_mode = $data['payment_mode'];
            $sellerLedger->particular = 'ORDER CANCELLED - ' . $data['order_id'];
            $sellerLedger->debit = $refundAmount;
            $sellerLedger->credit = 0;
            $sellerLedger->datetime = $currentDate;
            $sellerLedger->case = 0;
            $sellerLedger->save();

            $sellerLedger = new SellerLeger();
            $sellerLedger->order_date = $data['order_date'];
            $sellerLedger->shop_id = $data['shop_id'];
            $sellerLedger->shop_name = CommonHelper::shopName($data['shop_id']);
            $sellerLedger->shop_city_id = $data['shop_city_id'];
            $sellerLedger->order_reference = $data['order_reference'];
            $sellerLedger->order_id = $data['order_id'];
            $sellerLedger->transaction_detail = $data['paytm_txn_id'];
            $sellerLedger->payment_mode = $data['payment_mode'];
            $sellerLedger->particular = 'TDS TCS REVERSAL - ' . $data['order_id'];
            $sellerLedger->debit = 0;
            $sellerLedger->credit = $creditAmount;
            $sellerLedger->datetime = $currentDate;
            $sellerLedger->case = 0;
            $sellerLedger->save();

            $wallet = new Wallet();
            $wallet->customer_id = $data['customer_id'];
            $wallet->order_reference = $data['order_reference'];
            $wallet->order_id = $data['order_id'];
            $wallet->amount = $refundAmount1;
            $wallet->remark = 'Order Canceled Refund - ' . $data['order_id'];
            $wallet->payment_status = 1;
            $wallet->status = 1;
            $wallet->date = $currentDate;
            $sqlWallet =  $wallet->save();

            if ($sqlWallet) {

                $sqlReg = Registration::select("wallet_balance")
                    ->where("id", $data['customer_id']);
                $regData = $sqlReg->first()?->toArray();
                $customerId = $data['customer_id'];

                if (!$regData)
                    throw ExceptionHelper::error([
                        "message" =>    "user with id: $customerId doesn't exists"
                    ]);

                $updateBalance = $regData['wallet_balance'] + $refundAmount1;
                Registration::where("id", $customerId)
                    ->update([
                        "wallet_balance" => $updateBalance
                    ]);
            }
        }

        $title = "Oops!! Customers cancelled order #" . $orderId;
        $body = CommonHelper::cancelReason($reasonId);

        /* Send Notifiction for Shopkeeper */
        CommonHelper::ceoCancelOrderNotification($title, $body, 'cancelOrder', $orderId, $data['shop_id']);

        $title = "The Order has been cancelled. Order ID - #" . $orderId;
        $body = CommonHelper::cancelReason($reasonId);

        /* Send Notifiction for Delivery Boy */
        $employeeId = $data['deliveryboy_id'];
        $sqlToken = Employee::select("token_id")
            ->where("id", $employeeId);

        $sqlTokenData = $sqlToken
            ->first()
            ?->toArray();

        if (!$sqlTokenData)
            throw ExceptionHelper::error([
                "employee with id: $employeeId doesn't exists"
            ]);

        CommonHelper::starCancelOrderNotification(
            $title,
            $body,
            $orderId,
            $sqlTokenData['token_id']
        );

        return ResponseGenerator::generateResponseWithStatusCode(
            ResponseGenerator::generateSuccessResponse([
                "message" => "Order Cancelled.",
            ])
        );
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function orderReturnAcceptPartner(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [
                "orderId.exists" => "order with id: :input doesn't exist"
            ],
            [
                "orderId" => "required|numeric|exists:tbl_order,order_id"
            ]
        );

        $status  = 1;
        $deliveryBoyList = [];
        $otp = OTPHelper::generateOtp();
        $orderId  = round($data['orderId']);

        $sqlOrder = Order::select("shop_id", "latitude", "longitude", "customer_id", "name", "mobile", "total as sum(total)")
            ->where("order_id", $orderId);

        $sqlOrderData = $sqlOrder
            ->first()
            ->toArray();

        $sqlDelivery = Employee::select("id", "latitude", "longitude")
            ->where([
                "designation_id" => 3,
                "status" => 1,
                "online_status" => 1,
                "assign_status" => 0,
            ]);

        if ($sqlOrderData['shop_id'] == 2222222)
            $deliveryBoyId = 614;
        else {
            foreach ($sqlDelivery as $dataDelivery) {
                $distance = UtilityHelper::getDistanceBetweenPlaces(
                    [
                        "lat" => $sqlOrderData['latitude'],
                        "long" => $sqlOrderData['longitude'],
                    ],
                    [
                        "lat" => $dataDelivery['latitude'],
                        "long" => $dataDelivery['longitude']
                    ]
                );

                $deliveryBoyList[] = array('deliveryBoyId' => $dataDelivery['id'], 'distance' => $distance);
                $columns = array_column($deliveryBoyList, 'distance');
                array_multisort($columns, SORT_ASC, $deliveryBoyList);
            }

            $count = sizeof($deliveryBoyList);

            if ($count > 0)
                $deliveryBoyId = $deliveryBoyList[0]['deliveryBoyId'];
            else
                $deliveryBoyId = "";
        }

        $sqlOrder1 = Order::where("order_id", $orderId)
            ->update([
                "otp" => $otp,
                "return_status" => $status,
                "return_deliveryboy_id" => $deliveryBoyId,
            ]);

        if (!$sqlOrder1)
            throw ExceptionHelper::error([
                "message" => "unable to update order"
            ]);

        $sqlOrder1 = OrderEdited::where("order_id", $orderId)
            ->update([
                "otp" => $otp,
                "return_status" => $status,
                "return_deliveryboy_id" => $deliveryBoyId,
            ]);

        $title = "Return Request against order #" . $orderId . " is accepted";
        $body  = "Click to View order detail";

        $arrNotification["title"]   = $title;
        $arrNotification["body"]    = $body;
        $arrNotification["sound"]   = "default";
        $arrNotification["type"]    = "orderDetail";
        $arrNotification["dataId"]  = $orderId;
        $arrNotification["dataId2"] = "";

        $sqlToken1 = Registration::select("token_id")
            ->where("id", $sqlOrderData['customer_id']);
        $sqlTokenData1 = $sqlToken1->first()?->toArray();

        CommonHelper::sendPushNotification($sqlTokenData1['token_id'], $arrNotification);

        $title = "Order ID #" . $orderId . " Return Order Received";
        $body  = "Open the App to check the details";

        $sqlToken = Employee::select("*")
            ->where("id", $deliveryBoyId);

        $sqlTokenData = $sqlToken->first()?->toArray();

        if (!$sqlTokenData)
            throw ExceptionHelper::error([
                "message" => "Employee not found in employee table via id=$deliveryBoyId"
            ]);

        CommonHelper::starNewOrderNotification(
            $title,
            $body,
            $orderId,
            $sqlTokenData['token_id']
        );

        /* OTP to user */
        $mobile = $sqlOrderData['mobile'];
        OTPHelper::sendOTP($otp, $mobile, $sqlOrderData['name']);

        return ResponseGenerator::generateResponseWithStatusCode(
            ResponseGenerator::generateSuccessResponse([
                "message" => "Return Request Accepted",
            ])
        );
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public static function orderReturn(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [
                "orderId.exists" => "order with id: :input doesn't exist"
            ],
            [
                "orderId" => "required|numeric|exists:tbl_order,order_id",
                "reasonId" => "numeric",
                "remark" => "string"
            ]
        );

        $orderId = $data['orderId'];
        $productIds = $data['productIds'] ?? [];
        $reasonId = $data['reasonId'];
        $remark = $data['remark'];
        $currentDate = date('Y-m-d H:i:s');

        $otp = OTPHelper::generateOtp();

        $sqlOrder = Order::select("*")
            ->where("order_id", $orderId);

        $orderData = $sqlOrder->first()?->toArray();

        if (!$orderData)
            throw ExceptionHelper::error([
                "message" => "order with order_id: $orderId doesn't exists"
            ]);

        if (empty($orderData['delivered_date']))
            throw ExceptionHelper::error([
                "message" => "delivered_date is empty in row where order_id: $orderId"
            ]);

        $deliveredTime = date('Y-m-d H:i:s', strtotime($orderData['delivered_date']));
        $returnTime    = date('Y-m-d H:i:s', strtotime($deliveredTime . ' +1 days'));

        if (!($currentDate <= $returnTime))
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::UNAUTHORIZED,
                "message" => "Return Window Is Closed"
            ]);

        if (!empty($productIds)) {

            $loop = 0;
            $arrproductIds = json_decode($productIds);

            foreach ($arrproductIds as $aPs) {

                $productId = $aPs;
                Order::where('order_id', $orderId)
                    ->where('product_id', $productId)
                    ->update([
                        "otp" => $otp,
                        "accept_status" => 0,
                        "status" => 7,
                        "return_reason_id" => $reasonId,
                        "return_remark" => $remark,
                        "return_date" => $currentDate,
                    ]);

                OrderEdited::where('order_id', $orderId)
                    ->where('product_id', $aPs)
                    ->update([
                        "otp" => $otp,
                        "accept_status" => 0,
                        "status" => 7,
                        "return_reason_id" => $reasonId,
                        "return_remark" => $remark,
                        "return_date" => $currentDate,
                    ]);

                /*Start Remove Special Product*/
                $finalTable = "tbl_order";

                if ($orderData['edit_confirm'] == 1)
                    $finalTable = "tbl_order_edited";

                $sqlOrderSpecial = null;

                if ($finalTable === "tbl_order") {
                    $sqlOrderSpecial = Order::select("product_id")
                        ->where([
                            "order_id" => $orderId,
                            "offer_primary_id" => $productId
                        ])
                        ->where("offer_id", ">", 0);
                } else {
                    $sqlOrderSpecial = OrderEdited::select("product_id")
                        ->where([
                            "order_id" => $orderId,
                            "offer_primary_id" => $productId
                        ])
                        ->where("offer_id", ">", 0);
                }

                if ($sqlOrderSpecial->count()) {

                    $sqlOrderSpecialData = $sqlOrderSpecial
                        ->first()
                        ->toArray();

                    Order::where('order_id', $orderId)
                        ->where('product_id', $sqlOrderSpecialData['product_id'])
                        ->update([
                            'otp' => $otp,
                            'accept_status' => 0,
                            'status' => 7,
                            'return_reason_id' => $reasonId,
                            'return_remark' => $remark,
                            'return_date' => $currentDate,
                        ]);

                    OrderEdited::where('order_id', $orderId)
                        ->where('product_id', $sqlOrderSpecialData['product_id'])
                        ->update([
                            'otp' => $otp,
                            'accept_status' => 0,
                            'status' => 7,
                            'return_reason_id' => $reasonId,
                            'return_remark' => $remark,
                            'return_date' => $currentDate,
                        ]);
                }

                /*End Remove Special Product*/
                $loop++;
            }

            if (!$loop)
                throw ExceptionHelper::error([
                    "message" => "Loop count is $loop"
                ]);

            OrderEdited::where('order_id', $orderId)
                ->update([
                    "otp" => $otp
                ]);

            Order::where('order_id', $orderId)
                ->update([
                    "otp" => $otp
                ]);

            $sqlEditTotal = Order::select("total as sum(`total`)", "shop_total", "shop_discount")
                ->where([
                    "order_id" => $orderId,
                    "status" => 7,
                    "offer_type" => 0,
                ]);

            $editTotalData = $sqlEditTotal
                ->first()
                ?->toArray();

            if (!$editTotalData)
                throw ExceptionHelper::error([
                    "message" => "order where order_id: $orderId, status: 7 and offer_type=0 doesn't exists"
                ]);

            if ($orderData['edit_status'] == 1 && $orderData['edit_confirm'] == 1) {
                $sqlEditTotal = OrderEdited::select("total as sum(`total`)", "shop_total", "shop_discount")
                    ->where([
                        "order_id" => $orderId,
                        "status" => 7,
                        "offer_type" => 0,
                    ])
                    ->where("qty", "!=", 0);

                $editTotalData = $sqlEditTotal
                    ->first()
                    ?->toArray();

                if (!$editTotalData)
                    throw ExceptionHelper::error([
                        "message" => "order_edited row where order_id: $orderId, status: 7, qty != 0 and offer_type=0 doesn't exists"
                    ]);
            }

            $discount = ($editTotalData['sum(`total`)'] * $editTotalData['shop_discount']) / $editTotalData['shop_total'];
            $discount = sprintf('%0.2f', $discount);
            $refundAmount1 = $editTotalData['sum(`total`)'] - ($discount);
            $refundAmount1 = sprintf('%0.2f', $refundAmount1);

            Order::where('order_id', $orderId)
                ->where('status', 7)
                ->update(['refund_amount' => $refundAmount1]);

            OrderEdited::where('order_id', $orderId)
                ->where('status', 7)
                ->update(['refund_amount' => $refundAmount1]);

            $title = "Order has been returned #" . $orderId;
            $body = CommonHelper::cancelReason($reasonId);
            CommonHelper::ceoCancelOrderNotification(
                $title,
                $body,
                'returnOrder',
                $orderId,
                $orderData['shop_id']
            );

            return ResponseGenerator::generateResponseWithStatusCode(
                ResponseGenerator::generateSuccessResponse([
                    "message" => "Return Request Submitted.",
                ])
            );
        } else {

            $sqlReturn =  Order::where('order_id', $orderId)
                ->update([
                    'otp' => $otp,
                    'accept_status' => 0,
                    'status' => 7,
                    'return_reason_id' => $reasonId,
                    'return_remark' => $remark,
                    'return_date' => $currentDate,
                ]);

            if (!$sqlReturn)
                throw ExceptionHelper::error([
                    "message" => "unable to update order where order_id: $orderId"
                ]);

            OrderEdited::where('order_id', $orderId)
                ->update([
                    'otp' => $otp,
                    'accept_status' => 0,
                    'status' => 7,
                    'return_reason_id' => $reasonId,
                    'return_remark' => $remark,
                    'return_date' => $currentDate,
                ]);

            $sqlCheck = ReturnOrder::where('order_id', $orderId);

            if (!($sqlCheck->count())) {

                $sqlInsert =  DB::table('tbl_return_order')->insertUsing(
                    [
                        'otp',
                        'order_reference',
                        'order_id',
                        'shop_id',
                        'customer_id',
                        'product_id',
                        'product_barcode',
                        'product_name',
                        'weight',
                        'price',
                        'mrp',
                        'qty',
                        'return_reason_id',
                        'return_remark',
                    ],
                    function ($query) use ($orderId) {
                        $query->select([
                            'otp',
                            'order_reference',
                            'order_id',
                            'shop_id',
                            'customer_id',
                            'product_id',
                            'product_barcode',
                            'product_name',
                            'weight',
                            'price',
                            'mrp',
                            'qty',
                            'return_reason_id',
                            'return_remark',
                        ])->from('tbl_order')->where('order_id', $orderId);
                    }
                );

                if ($sqlInsert) {

                    $Pid = DB::getPdo()->lastInsertId();
                    $returnDate = date('Y-m-d');

                    $sqlReturnCheck = DB::select(DB::raw("select max(return_id) from `tbl_return_order`"))[0];
                    $maxReturnId = $sqlReturnCheck->{'max(return_id)'};

                    if ($maxReturnId == NULL)
                        $returnId = 1;
                    else
                        $returnId = $maxReturnId + 1;

                    ReturnOrder::where("id", $Pid)
                        ->update([
                            "return_id" => $returnId,
                            "datetime" => $currentDate,
                            "return_date" => $returnDate,
                        ]);
                }
            }

            $sqlEditTotal = Order::selectRaw('total as sum(`total`), shop_discount, offer_total')
                ->where('order_id', $orderId)
                ->where('status', 7)
                ->first()
                ?->toArray();

            if (!$sqlEditTotal)
                throw ExceptionHelper::error([
                    "order row with id: $orderId and status: 7 doesn't exists"
                ]);

            $editTotalData = $sqlEditTotal;

            if ($orderData['edit_status'] == 1 && $orderData['edit_confirm'] == 1) {
                $sqlEditTotal = OrderEdited::select("total as sum(`total`)", "shop_total", "shop_discount")
                    ->where([
                        "order_id" => $orderId,
                        "status" => 7,
                        "offer_type" => 0,
                    ])
                    ->where("qty", "!=", 0);

                $editTotalData = $sqlEditTotal
                    ->first()
                    ?->toArray();

                if (!$editTotalData)
                    throw ExceptionHelper::error([
                        "message" => "order_edited row where order_id: $orderId, status: 7, qty != 0 and offer_type=0 doesn't exists"
                    ]);
            }

            $refundAmount1 = $editTotalData['sum(`total`)'] - ($editTotalData['shop_discount'] + $editTotalData['offer_total']);
            $refundAmount1 = sprintf('%0.2f', $refundAmount1);

            ReturnOrder::where('id', $Pid)->update(['return_total' => $refundAmount1]);

            Order::where('order_id', $orderId)
                ->where('status', 7)
                ->update(['refund_amount' => $refundAmount1]);

            OrderEdited::where('order_id', $orderId)
                ->where('status', 7)
                ->update(['refund_amount' => $refundAmount1]);

            $title = "Order has been returned #" . $orderId;
            $body = CommonHelper::cancelReason($reasonId);

            CommonHelper::ceoCancelOrderNotification(
                $title,
                $body,
                'returnOrder',
                $orderId,
                $orderData['shop_id']
            );

            return ResponseGenerator::generateResponseWithStatusCode(
                ResponseGenerator::generateSuccessResponse([
                    "message" => "Return Request Submitted.",
                ])
            );
        }
    }



    public function orderCompleteCancel(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [
                "reasonId.exists" => "cancel reason with id: :input doesn't exist"
            ],
            [
                "referenceId" => "required|string",
                "reasonId" => "numeric|exists:tbl_cancel_reason,id",
                "remark" => "string"
            ]
        );

        $referenceId = $data['referenceId'];
        $reasonId    = $data['reasonId'];
        $remark      = $data['remark'];
        $date        = date('Y-m-d H:i:s');

        $c = 0;
        $orderCount = 0;

        $sqlDispatchCheck = Order::select("status")
            ->where("order_reference", $referenceId)
            ->groupBy("order_id");

        UtilityHelper::disableSqlStrictMode();

        $sqlDispatchCheck_ = $sqlDispatchCheck->get()
            ->toArray();

        UtilityHelper::enableSqlStrictMode();

        foreach ($sqlDispatchCheck_ as $sqlDispatchCheck__) {
            if ($sqlDispatchCheck__['status'] > 1)
                $c++;
        }

        if ($c !== 0)
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::RESOURCE_ALREADY_EXISTS,
                "data" => [
                    "status" => 0
                ],
                "message" => "One Of Your Order is Already Dispatched"
            ]);

        UtilityHelper::disableSqlStrictMode();

        $sqlOrder = Order::select("*")
            ->where("order_reference", $referenceId)
            ->groupBy("order_id")
            ->get()
            ->toArray();

        UtilityHelper::enableSqlStrictMode();

        foreach ($sqlOrder as $orderData) {

            $orderCount++;

            $sqlOrignal = Order::select(DB::raw("sum(total)"))
                ->where("order_id", $orderData['order_id']);

            $sqlOrignalData = $sqlOrignal
                ->first()
                ->toArray();

            $sqlTxn = OrderPrepaidTransaction::select("tcs", "tds", "aggregator_commission_amount")
                ->where("order_id", $orderData['order_id']);

            $txnData = $sqlTxn
                ->first()
                ?->toArray();

            $orderId = $orderData['order_id'];

            if (!$txnData)
                throw ExceptionHelper::error([
                    "message" => "prepaid transaction not found via order_id: $orderId"
                ]);

            $creditAmount = $txnData['aggregator_commission_amount'];
            $refundAmount = $sqlOrignalData['sum(total)'] ?? 0;

            if ($orderData['edit_status'] == 1 && $orderData['edit_confirm'] == 1) {

                $sqlOrderEdit = OrderEdited::select(DB::raw("sum(total)"))
                    ->where("order_id", $orderData['order_id']);

                $sqlOrderEditData = $sqlOrderEdit
                    ->first()
                    ->toArray();

                $refundAmount = $sqlOrderEditData['sum(total)'] ?? 0;

                $sqlTxn = OrderPrepaidTransaction::select("edit_tcs", "edit_tds", "edit_aggregator_commission_amount")
                    ->where("order_id", $orderData['order_id']);

                $txnData = $sqlTxn
                    ->first()
                    ?->toArray();

                $orderId = $orderData['order_id'];

                if (!$txnData)
                    throw ExceptionHelper::error([
                        "message" => "prepaid transaction not found via order_id: $orderId"
                    ]);

                $creditAmount = $txnData['edit_aggregator_commission_amount'];
            }


            if ($orderData['payment_type'] == 'PREPAID' || $orderData['payment_type'] == 'WALLET') {

                $sqlEditTotal = Order::select(DB::raw("sum(total)"), `shop_discount`, `offer_total`)
                    ->where("order_id", $orderData['order_id']);

                $editTotalData = $sqlEditTotal
                    ->first()
                    ?->toArray();

                $orderId = $orderData['order_id'];

                if ($orderData['edit_status'] == 1 && $orderData['edit_confirm'] == 1) {

                    $sqlEditTotal = OrderEdited::select(DB::raw("sum(total)"), `shop_discount`, `offer_total`)
                        ->where("order_id", $orderData['order_id']);

                    $editTotalData = $sqlEditTotal
                        ->first()
                        ?->toArray();
                }

                if (!$editTotalData)
                    throw ExceptionHelper::error([
                        "message" => "order not found via order_id: $orderId"
                    ]);

                $refundAmount1 = $editTotalData['sum(`total`)'] - ($editTotalData['shop_discount'] + $editTotalData['offer_total']);
                $refundAmount1 = sprintf('%0.2f', $refundAmount1);
            }

            if ($orderData['payment_type'] == 'PREPAID') {

                $orderData = [
                    'order_date' => $orderData['order_date'],
                    'customer_id' => $orderData['customer_id'],
                    'customer_name' => $orderData['name'],
                    'order_reference' => $orderData['order_reference'],
                    'order_id' => $orderData['order_id'],
                    'incomimg_txn_id' => $orderData['paytm_txn_id'],
                    'source' => $orderData['payment_mode'],
                    'orignal_order_total' => $refundAmount,
                    'edit_order_total' => 0,
                    'reason' => 'ORDER CANCELLED BY CUSTOMER',
                    'refund_amount' => $refundAmount1,
                    'status' => 0,
                ];

                $sellerLedgerData = [
                    'order_date' => $orderData['order_date'],
                    'shop_id' => $orderData['shop_id'],
                    'shop_name' => CommonHelper::shopName($orderData['shop_id']),
                    'shop_city_id' => $orderData['shop_city_id'],
                    'order_reference' => $orderData['order_reference'],
                    'order_id' => $orderData['order_id'],
                    'transaction_detail' => $orderData['paytm_txn_id'],
                    'payment_mode' => $orderData['payment_mode'],
                    'particular' => 'ORDER CANCELLED - ' . $orderData['order_id'],
                    'debit' => $refundAmount,
                    'credit' => 0,
                    'datetime' => $date,
                    'case' => 0,
                ];

                SellerLeger::create($sellerLedgerData);

                $sellerLedgerData = [
                    'order_date' => $orderData['order_date'],
                    'shop_id' => $orderData['shop_id'],
                    'shop_name' => $orderData['shop_name'],
                    'shop_city_id' => $orderData['shop_city_id'],
                    'order_reference' => $orderData['order_reference'],
                    'order_id' => $orderData['order_id'],
                    'transaction_detail' => $orderData['paytm_txn_id'],
                    'payment_mode' => $orderData['payment_mode'],
                    'particular' => 'TDS TCS REVERSAL - ' . $orderData['order_id'],
                    'debit' => 0,
                    'credit' => $creditAmount,
                    'datetime' => $date,
                    'case' => 0,
                ];

                SellerLeger::create($sellerLedgerData);
                Refund::create($orderData);
            }


            if ($orderData['payment_type'] == 'WALLET') {

                $sellerLedgerData = [
                    'order_date' => $orderData['order_date'],
                    'shop_id' => $orderData['shop_id'],
                    'shop_name' => CommonHelper::shopName($orderData['shop_id']),
                    'shop_city_id' => $orderData['shop_city_id'],
                    'order_reference' => $orderData['order_reference'],
                    'order_id' => $orderData['order_id'],
                    'transaction_detail' => $orderData['paytm_txn_id'],
                    'payment_mode' => $orderData['payment_mode'],
                    'particular' => 'ORDER CANCELLED - ' . $orderData['order_id'],
                    'debit' => $refundAmount,
                    'credit' => 0,
                    'datetime' => $date,
                    'case' => 0,
                ];

                SellerLeger::create($sellerLedgerData);

                $sellerLedgerData = [
                    'order_date' => $orderData['order_date'],
                    'shop_id' => $orderData['shop_id'],
                    'shop_name' => CommonHelper::shopName($orderData['shop_id']),
                    'shop_city_id' => $orderData['shop_city_id'],
                    'order_reference' => $orderData['order_reference'],
                    'order_id' => $orderData['order_id'],
                    'transaction_detail' => $orderData['paytm_txn_id'],
                    'payment_mode' => $orderData['payment_mode'],
                    'particular' => 'TDS TCS REVERSAL - ' . $orderData['order_id'],
                    'debit' => 0,
                    'credit' => $creditAmount,
                    'datetime' => $date,
                    'case' => 0,
                ];

                SellerLeger::create($sellerLedgerData);

                $walletData = [
                    'customer_id' => $orderData['customer_id'],
                    'order_reference' => $orderData['order_reference'],
                    'order_id' => $orderData['order_id'],
                    'amount' => $refundAmount1,
                    'remark' => 'Order Canceled Refund - ' . $orderData['order_id'],
                    'payment_status' => 1,
                    'status' => 1,
                    'date' => $date,
                ];

                Wallet::create($walletData);

                $registration = Registration::find($orderData['customer_id']);
                $userId = $orderData['customer_id'];

                if (!$registration)
                    throw ExceptionHelper::error([
                        "message" => "registration not found with id: $userId"
                    ]);

                $newWalletBalance = $registration->wallet_balance + $refundAmount1;
                $registration->update(['wallet_balance' => $newWalletBalance]);
            }

            /* Send Notifiction for Shopkeeper */
            $title   = "Oops!! Customers cancelled order #" . $orderData['order_id'];
            $body    = CommonHelper::cancelReason($reasonId);
            $title1   = "The Order has been cancelled. Order ID - #" . $orderData['order_id'];

            UtilityHelper::disableSqlStrictMode();

            $sqlNoti = Order::select("deliveryboy_id", "shop_id")
                ->where("order_reference", $referenceId)
                ->groupBy("shop_id")
                ->get()
                ->toArray();

            UtilityHelper::enableSqlStrictMode();

            foreach ($sqlNoti as $notiData) {

                CommonHelper::ceoCancelOrderNotification(
                    $title,
                    $body,
                    'cancelOrder',
                    $orderData['order_id'],
                    $notiData['shop_id']
                );

                $employeeId = $notiData['deliveryboy_id'];
                $sqlToken = Employee::select("token_id")
                    ->where("id", $employeeId);

                $sqlTokenData = $sqlToken
                    ->first()
                    ?->toArray();

                if (!$sqlTokenData)
                    throw ExceptionHelper::error([
                        "employee with id: $employeeId doesn't exists"
                    ]);

                CommonHelper::starCancelOrderNotification(
                    $title1,
                    $body,
                    $orderData['order_id'],
                    $sqlTokenData['token_id']
                );

                Employee::where("id", $orderData['deliveryboy_id'])
                    ->update([
                        "assign_status" => 0
                    ]);
            }
        }

        $sqlUpdate = Order::where("order_reference", $referenceId)
            ->update([
                "status" => 5,
                "cancel_status" => 1,
                "reason_id" => $reasonId,
                "cancel_remark" => $remark,
                "cancel_date" => $date
            ]);

        if ($sqlUpdate) {
            OrderEdited::where("order_reference", $referenceId)
                ->update([
                    "status" => 5,
                    "cancel_status" => 1,
                    "reason_id" => $reasonId,
                    "cancel_remark" => $remark,
                    "cancel_date" => $date
                ]);

            return ResponseGenerator::generateResponseWithStatusCode(
                ResponseGenerator::generateSuccessResponse([
                    "data" => [
                        "status" => 1
                    ],
                    "message" => "Order Cancelled",
                ])
            );
        }

        throw ExceptionHelper::error([
            "data" => [
                "status" => 0
            ]
        ]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function editOrderConfirm(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [],
            [
                "orderId" => "required|numeric"
            ]
        );

        $orderId = $data["orderId"];

        $sqlOrder = Order::select("shop_id", "payment_type", "payment_status")
            ->where("order_id", $orderId);

        if (!$sqlOrder->count())
            throw ExceptionHelper::error([
                "message" => "order with id: $orderId not found"
            ]);

        $orderData = $sqlOrder
            ->first()
            ->toArray();

        $sqlUpdate = Order::where("order_id", $orderId)
            ->update([
                "edit_confirm" => 1
            ]);

        if (!$sqlUpdate)
            throw ExceptionHelper::error([
                "message" => "unable to update order with id: $orderId"
            ]);

        $sqlUpdate = OrderEdited::where("order_id", $orderId)
            ->update([
                "edit_confirm" => 1
            ]);

        $arrNotification["title"]   = "Approved Order ID #" . $orderId;
        $arrNotification["body"]    = "Click to view detail";
        $arrNotification["sound"]   = url("/notification/sound/ceoOrderReceive.mpeg");
        $arrNotification["type"]    = "orders";
        $arrNotification["dataId"]  = $orderId;
        $arrNotification["dataId2"] = "";

        $sqlToken = Shop::select("token_id")
            ->where("id", $orderData['shop_id']);

        $sqlTokenData = $sqlToken
            ->first()
            ->toArray();

        CommonHelper::sendPushNotification(
            $sqlTokenData['token_id'],
            $arrNotification
        );

        if ($orderData['payment_type'] == 'PREPAID') {

            $sqlBasic = OrderEdited::select("product_id", "total")
                ->where("order_id", $orderId)
                ->where("qty", "!=", 0);

            if ($sqlBasic->count()) {

                $tcs = 0;
                $tds = 0;
                $tcs = 0;
                $tds = 0;
                $grossAmount = 0;
                // $paidToSeller = 0;
                $basicAmountTotal = 0;
                $orderAmountTotal = 0;
                $payableMerchantAmount = 0;
                $aggregatorCommissionAmount = 0;
                // $totalPayoutFromNodalAccount = 0;

                foreach ($sqlBasic->get()->toArray() as $sqlBasicData) {

                    $orderAmountTotal = $orderAmountTotal + $sqlBasicData['total'];
                    $sqlTaxSlab = HsnCode::select("tax_rate", "cess")
                        ->where("hsn_code", CommonHelper::productHsn($sqlBasicData['product_id']));

                    if ($sqlTaxSlab->count()) {

                        $sqlTaxSlabData = $sqlTaxSlab
                            ->first()
                            ->toArray();

                        $cessSlab = $sqlTaxSlabData['cess'];
                        $taxSlab = $sqlTaxSlabData['tax_rate'];
                        $basicAmount = ($sqlBasicData['total'] / (100 + $taxSlab + $cessSlab) * 100);
                        $basicAmountTotal = $basicAmountTotal + $basicAmount;
                    } else {
                        $basicAmountTotal = $basicAmountTotal + $sqlBasicData['total'];
                    }
                }


                if ($orderData['payment_type'] == 'PREPAID' && $orderData['payment_status'] == 1) {
                    /*INVOICE SETTLEMENT CALCULATION STORE START*/

                    // $payTmComm    = ($orderAmountTotal * 1.60) / 100;
                    // $payTmCommGst = ($payTmComm * 18) / 100;
                    $grossAmount  = 0.00;
                    //$orderAmountTotal - ($payTmComm+$payTmCommGst);
                    $tcs          = ($basicAmountTotal * 1) / 100;
                    $tds          = ($basicAmountTotal * 1) / 100;
                    $aggregatorCommissionAmount = $tcs + $tds;
                    $payableMerchantAmount      = 0.00;
                    //$grossAmount - $aggregatorCommissionAmount;

                    OrderPrepaidTransaction::where("order_id", $orderId)
                        ->update([
                            "basic_amount" => $basicAmountTotal,
                            "gross_amount" => $grossAmount,
                            "aggregator_commission_amount" => $aggregatorCommissionAmount,
                            "payable_merchant_amount" => $payableMerchantAmount,
                            "total_payout_from_nodal_account" => $grossAmount,
                            "tcs" => $tcs,
                            "tds" => $tds
                        ]);

                    /*INVOICE SETTLEMENT CALCULATION STORE END*/
                }
            }
        }

        return ResponseGenerator::generateResponseWithStatusCode(
            ResponseGenerator::generateSuccessResponse([
                "message" => "Order edit confirmed.",
            ])
        );
    }




    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function paymentStatus(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [],
            [
                "orderId" => "required|numeric",
                "txnStatus" => "required",
                "paymentMode" => "required",
                "txnDate" => "required",
                "txnId" => "required",
            ]
        );

        $currentDate = date('Y-m-d H:i:s');
        $orderId     = $data['orderId'];
        $userId      = $req->user()->id;
        $txnId       = $data['txnId'];
        $txnDate     = date('Y-m-d H:i:s', strtotime($data['txnDate']));
        $txnStatus   = $data['txnStatus'];
        $paymentMode = $data['paymentMode'];

        $orderStatus = 0;

        if ($txnStatus == 'TXN_SUCCESS') {
            $paymentStatus = 1;
            $msg = 'Order Placed Successfully';
        } else if ($txnStatus == 'PENDING') {
            $paymentStatus = 0;
            $msg = 'Order Placed. Waiting For Payment Confirmation';
        } else {
            $paymentStatus = 2;
            $orderStatus   = 4;
            $msg = 'Payment Failed';
        }

        $order = Order::where('order_reference', $orderId)
            ->first();

        if ($order) {
            $order->status = $orderStatus;
            $order->paytm_txn_id = $txnId;
            $order->payment_txn_date = $txnDate;
            $order->payment_mode = $paymentMode;
            $order->payment_status = $paymentStatus;
            $order->save();
        } else
            throw ExceptionHelper::error([
                "message" => "tbl_order row where order_reference: $orderId not found"
            ]);

        // Assigning this true since the code will only come here if order is updated
        $sqlUpdate = true;

        if ($sqlUpdate) {
            if ($paymentStatus == 1) {

                /*START REFERRAL BOUNS CODE*/
                $sqlhome = Home::where('id', 1)->first();
                $sqlReferral = Registration::where('id', $userId)
                    ->where('referral_by', '!=', '')
                    ->where('referral_status', 0)
                    ->select('referral_by', 'referral_code');

                $referral_amount = $sqlhome->referral_amount;

                if ($sqlReferral->count() > 0) {
                    $sqlReferralData = $sqlReferral
                        ->first()
                        ->toArray();

                    $referral_code = $sqlReferralData['referral_code'];
                    $referral_by = $sqlReferralData['referral_by'];

                    $sqlReferralUser = Registration::where('referral_code', $referral_by)
                        ->select('id')
                        ->first();

                    if ($sqlReferralUser) {
                        $wallet = new Wallet();
                        $wallet->customer_id = $sqlReferralUser->id;
                        $wallet->order_id = $orderId;
                        $wallet->amount = $referral_amount;
                        $wallet->remark = 'Referral Bonus';
                        $wallet->referral_code = $referral_code;
                        $wallet->referral_by = $referral_by;
                        $wallet->date = $currentDate;

                        $wallet->save();

                        Registration::where('id', $userId)->update(['referral_status' => 1]);
                    }
                }

                /*END REFERRAL BOUNS CODE*/

                Cart::where('user_id', $userId)->delete();

                UtilityHelper::disableSqlStrictMode();

                $sqlOrderShop = Order::select('order_date', 'shop_id', 'shop_city_id', 'order_id')
                    ->where('order_reference', $orderId)
                    ->groupBy('shop_id')
                    ->get()
                    ->toArray();

                UtilityHelper::enableSqlStrictMode();

                foreach ($sqlOrderShop as $orderShopData) {

                    $orderId = $orderShopData['order_id'];

                    // Retrieve the credit amount
                    $totalData = Order::where('order_id', $orderId)->sum('total');
                    $creditAmount = $totalData;

                    // Retrieve the debit amount
                    $transactionData = OrderPrepaidTransaction::where('order_id', $orderId)
                        ->select('aggregator_commission_amount', 'tds', 'tcs')
                        ->first();

                    if (!$transactionData)
                        throw ExceptionHelper::error([
                            "message" => "tbl_order_prepaid_transaction row not found with order_id: $orderId"
                        ]);

                    $debitAmount = $transactionData->aggregator_commission_amount;

                    // Insert the first record
                    $sellerLedger1 = new SellerLeger();
                    $sellerLedger1->order_date = $orderShopData['order_date'];
                    $sellerLedger1->shop_id = $orderShopData['shop_id'];
                    $sellerLedger1->shop_name = CommonHelper::shopName($orderShopData['shop_id']);
                    $sellerLedger1->shop_city_id = $orderShopData['shop_city_id'];
                    $sellerLedger1->order_reference = $orderId;
                    $sellerLedger1->order_id = $orderShopData['order_id'];
                    $sellerLedger1->transaction_detail = $txnId;
                    $sellerLedger1->payment_mode = $paymentMode;
                    $sellerLedger1->particular = 'ORDER RECEIVED - ' . $orderShopData['order_id'];
                    $sellerLedger1->debit = 0;
                    $sellerLedger1->credit = $creditAmount;
                    $sellerLedger1->datetime = $currentDate;
                    $sellerLedger1->case = 1;
                    $sellerLedger1->save();

                    // Insert the second record
                    $sellerLedger2 = new SellerLeger();
                    $sellerLedger2->order_date = $orderShopData['order_date'];
                    $sellerLedger2->shop_id = $orderShopData['shop_id'];
                    $sellerLedger2->shop_name = CommonHelper::shopName($orderShopData['shop_id']);
                    $sellerLedger2->shop_city_id = $orderShopData['shop_city_id'];
                    $sellerLedger2->order_reference = $orderId;
                    $sellerLedger2->order_id = $orderShopData['order_id'];
                    $sellerLedger2->transaction_detail = $txnId;
                    $sellerLedger2->payment_mode = $paymentMode;
                    $sellerLedger2->particular = 'TCS TDS DEDUCTION - ' . $orderShopData['order_id'];
                    $sellerLedger2->debit = $debitAmount;
                    $sellerLedger2->credit = 0;
                    $sellerLedger2->datetime = $currentDate;
                    $sellerLedger2->case = 1;
                    $sellerLedger2->save();

                    /* Send Notification for ShopKeeper */
                    $title = "Congratulations! You Have Received New Order";
                    $body  = "Order ID : " . $orderShopData['order_id'];

                    // $arrNotification["title"]    = $title;
                    // $arrNotification["body"]     = $body;
                    // $arrNotification["sound"]    = "";//DEFAULT_URL."notification/sound/OrderReceivedERSPL.mp3";
                    // $arrNotification["type"]     = "orders";
                    // $arrNotification["dataId"]   = $orderShopData['order_id'];
                    // $arrNotification["dataId2"]  = "";
                    // $sqlToken     = mysqli_query($cn,"select `token_id` from `tbl_shop` where `id` = '".$orderShopData['shop_id']."'");
                    // $sqlTokenData = mysqli_fetch_array($sqlToken); 
                    // sendPushNotification($sqlTokenData['token_id'],$arrNotification);

                    CommonHelper::ceoNewOrderNotification(
                        $title,
                        $body,
                        $orderShopData['order_id'],
                        $orderShopData['shop_id']
                    );

                    /* Send Notification for Delivery Boy */
                    $title = "New Pending Order. Order ID: " . $orderShopData['order_id'];
                    $body  = "Open Application";

                    // $arrNotification["title"]    = $title;
                    // $arrNotification["body"]     = $body;
                    // $arrNotification["sound"]    = "";//DEFAULT_URL."notification/sound/pendingTone.mp3";
                    // $arrNotification["type"]     = "pendingOrder";
                    // //$arrNotification["image"]    = "";
                    // $arrNotification["dataId"]   = $orderShopData['order_id'];
                    // $arrNotification["dataId2"]  = "";

                    $sqlToken = Employee::where('designation_id', 3)
                        ->where('status', 1)
                        ->where('assign_status', 0)
                        ->where('online_status', 1)
                        ->where('city_id', $orderShopData['shop_city_id'])
                        ->where('token_id', '!=', '')
                        ->get();

                    foreach ($sqlToken as $sqlTokenData) {
                        // Send push notifications to employees with valid 'token_id'
                        // You can use your notification function here
                        // sendPushNotification($sqlTokenData->token_id, $arrNotification);
                        CommonHelper::starPendingOrderNotification(
                            $title,
                            $body,
                            $orderShopData['order_id'],
                            $sqlTokenData->token_id
                        );
                    }
                }
            } else if ($paymentStatus == 0) {
                Cart::where('user_id', $userId)->delete();
            }

            return ResponseGenerator::generateResponseWithStatusCode(
                ResponseGenerator::generateSuccessResponse([
                    "data" => [
                        "paymentStatus" => $paymentStatus
                    ],
                    "message" => $msg,
                ])
            );
        }
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function orderDetail(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [],
            [
                "orderId" => "required|exists:tbl_order,order_id"
            ]
        );

        $orderId = $data['orderId'];

        $order = Order::where('order_id', $orderId)
            ->first()
            ->toArray();

        $checkData = $order;

        $approveButton = 0;
        $returnProduct = 0;
        $returnTotal = 0;
        $returnStage = 0;
        $returnStatus = 0;
        $refundAmount = "";
        $refundStatus = "0";
        $refundDate = "";
        $extraStatus = "0";
        $orderTotal = 0.00;
        $mrpTotal = 0.00;
        $returnAble = 1;
        $edit = 0;

        if ($order && $order["edit_status"] == 1) {

            $sqlOrder = OrderEdited::where('order_id', $orderId)
                ->get()
                ->toArray();

            $count = count($sqlOrder);

            if ($count > 0) {

                $sqlHome = Home::get()->toArray();
                $sqlHomeData = $sqlHome[0];

                $data = $sqlOrder[0];

                $sqlOrder1 = OrderEdited::where('order_id', $orderId)
                    ->get()
                    ->toArray();

                foreach ($sqlOrder1 as $sqlOrderData) {
                    if ($sqlOrderData['status'] == 7) {
                        $returnProduct = 1;
                        $returnStatus = 1;
                        $returnStage  = $sqlOrderData['return_status'];
                        $returnTotal  = $sqlOrderData['refund_amount'];
                    } else {
                        $returnProduct = 0;
                    }

                    if ($sqlOrderData['return_status'] == 3 && $sqlOrderData['status'] == 7) {
                        $refundAmount = $sqlOrderData['refund_amount'];
                        $refundStatus = $sqlOrderData['refund_status'];
                        $refundDate   = $sqlOrderData['refund_date'];
                    }

                    $shopId = $sqlOrderData['shop_id'];

                    $sqlEditCheck = Order::select('id', 'qty', 'price')
                        ->where('product_id', $sqlOrderData['product_id'])
                        ->where('order_id', $orderId)
                        ->first()
                        ?->toArray();

                    if (!$sqlEditCheck)
                        throw ExceptionHelper::error([
                            "message" => "order row where product_id: " . $sqlOrderData['product_id'] . ", order_id: $orderId not found"
                        ]);

                    $editCheckData = $sqlEditCheck;

                    if ($editCheckData['qty'] != $sqlOrderData['qty']) {
                        $editHead = "The " . CommonHelper::shopName($sqlOrderData['shop_id']) . " is running low on stock :(";
                        $edit = 1;
                    } else if ($editCheckData['price'] != $sqlOrderData['price']) {
                        $editHead = "Hurray!! you got some more discounts";
                        $edit = 1;
                    } else {
                        $editHead = "";
                        $edit = 0;
                    }

                    $orgTotal = $editCheckData['price'] * $editCheckData['qty'];

                    $sqlProductData = Product::select('*')
                        ->where('id', $sqlOrderData['product_id'])
                        ->first()
                        ?->toArray();

                    if (!$sqlProductData)
                        throw ExceptionHelper::error([
                            "message" => "product row where id: " . $sqlOrderData['product_id'] . " not found"
                        ]);

                    $productImage = "";
                    $directory = '../products/' . $sqlProductData['barcode'] . '/';
                    $partialName = '1.';
                    $files = glob($directory . '*' . $partialName . '*');

                    if ($files !== false) {
                        foreach ($files as $file) {
                            $productImage = basename($file);
                        }
                    } else {
                        $productImage = "";
                    }

                    if ($sqlProductData['returnable'] == 0) {
                        $returnAble = 0;
                    }

                    $primaryStatus = 1;

                    if ($sqlOrderData['offer_id'] > 0) {
                        $primaryStatus = 0;
                    }

                    $returnInformation = "";

                    $offerBundleData = OfferBundling::select('*')
                        ->where('primary_unique_id', $sqlProductData->unique_code)
                        ->where('status', 1)
                        ->first()
                        ?->toArray();

                    if ($offerBundleData) {
                        $sqlShopProductData = DB::table('tbl_product')
                            ->select('id', 'weight', 'price', 'sellingprice', 'unit_id')
                            ->where('shop_id', $sqlOrderData['shop_id'])
                            ->where('unique_code', $offerBundleData["offer_unique_id"])
                            ->first();

                        if ($sqlShopProductData) {
                            $sqlOfferProductData = DB::table('tbl_order_edited')
                                ->where('order_id', $orderId)
                                ->where('product_id', $sqlShopProductData["product_id"])
                                ->where('offer_id', '>', 0)
                                ->first();

                            if ($sqlOfferProductData) {
                                $returnInformation = "{$sqlOrderData['qty']} X {$sqlOrderData['product_name']} {$sqlOrderData['weight']} will return with this product";
                            }
                        }
                    }

                    $shopTotalData = OrderEdited::select(DB::raw('SUM(total) as total_sum'))
                        ->where('order_id', $orderId)
                        ->where('offer_type', 0)
                        ->first();

                    if (!$shopTotalData)
                        throw ExceptionHelper::error([
                            "message" => "tbl_order_edited row where order_id: " . $orderId . " and offer_type: 0 not found"
                        ]);

                    if ($sqlOrderData['offer_type'] == 0 && $sqlOrderData['shop_discount'] > 0) {
                        $price = ($sqlOrderData['shop_discount'] * $sqlOrderData['price']) / $shopTotalData->total_sum;
                        $price = $sqlOrderData['price'] - $price;
                    } elseif ($sqlOrderData['offer_type'] == 2) {
                        $price = $sqlOrderData['price'] - $sqlOrderData['offer_total'];
                    } else {
                        $price = $sqlOrderData['price'];
                    }

                    $total = $sqlOrderData['qty'] * $price;
                    $mrpTotal1 = $sqlOrderData['qty'] * $sqlOrderData['mrp'];

                    $productList[] = [
                        'productId' => $sqlOrderData['product_id'],
                        'editHead' => $editHead,
                        'product_name' => $sqlOrderData['qty'] . " x " . ucfirst(
                            strtolower(
                                mb_convert_encoding(
                                    $sqlProductData['product_name'],
                                    'UTF-8'
                                )
                            )
                        ),
                        'weight' => $sqlOrderData['weight'],
                        'price' => sprintf('%0.2f', $price),
                        'qty' => $sqlOrderData['qty'],
                        'orgQty' => $editCheckData['qty'],
                        'sellingprice' => sprintf('%0.2f', $sqlOrderData['mrp']),
                        'total' => sprintf('%0.2f', $total),
                        'orgTotal' => $orgTotal,
                        'sellingtotal' => sprintf('%0.2f', $sqlOrderData['mrp'] * $sqlOrderData['qty']),
                        'orgSellingtotal' => $mrpTotal1,
                        'photo' => url("products") . "/" . $sqlOrderData['product_barcode'] . '/' . $productImage,
                        "returnProduct" => $returnProduct,
                        "returnable" => $sqlProductData['returnable'],
                        'newEdit' => $edit,
                        "primaryStatus" => $primaryStatus,
                        "returnInformation" => $returnInformation,
                    ];

                    $mrpTotal += $sqlOrderData['qty'] * $sqlOrderData['mrp'];
                    $orderTotal += $sqlOrderData['total'];
                }

                if ($data['expected_delivered_date'] != '') {
                    $expected_delivered_date = date('d, M Y h:i A', strtotime($data['expected_delivered_date']));
                } else {
                    $expected_delivered_date = "";
                }

                if ($data['status'] == 5 || $data['status'] == 6) {
                    $cancelReason = CommonHelper::cancelReason($data['reason_id']);
                    $cancelRemark = $data['cancel_remark'];
                    $cancelDate   = date('d, M Y h:i A', strtotime($data['cancel_date']));
                } else if ($data['status'] == 8) {
                    $cancelReason = CommonHelper::cancelReason($data['denial_reason_id']);
                    $cancelRemark = $data['denial_remark'];
                    $cancelDate   = date('d, M Y h:i A', strtotime($data['denial_date']));
                } else {
                    $cancelReason = "";
                    $cancelRemark = "";
                    $cancelDate   = "";
                }

                if ($checkData['payment_type'] == 'PREPAID') {

                    $refundData = Refund::where('order_id', round($orderId));

                    $refundCount = $refundData
                        ->count();

                    if ($checkData['status'] == 5 || $checkData['status'] == 6 || $checkData['status'] == 8) {
                        if ($refundCount > 0) {

                            $refundData = $refundData
                                ->first()
                                ->toArray();

                            $refundTotalData = Refund::where('order_id', round($orderId))
                                ->sum('refund_amount');

                            $refundStatus = $refundData['status'];
                            $refundDate   = $refundData['refund_date'];
                            $refundAmount = $refundTotalData;
                        } else {
                            $refundStatus = "0";
                            $refundDate   = "";
                            $refundAmount = $checkData['refund_amount'];
                        }
                    } else {

                        $originalTotalData = Order::where('order_id', $orderId)->sum('total');

                        $originalAmount = $originalTotalData;
                        $extraAmount = $originalAmount - $orderTotal;

                        if ($extraAmount > 0 || $extraAmount < 0) {

                            $extraStatus = "1";

                            if ($refundCount > 0) {
                                $refundStatus = "0";
                                $refundDate   = $refundData['refund_date'];
                                $refundAmount = $refundData['refund_amount'];
                            } else {
                                $refundStatus = "0";
                                $refundDate   = "";
                                $refundAmount = $extraAmount;
                            }
                        }

                        if ($returnStatus == "1") {
                            $refundTotalData = Refund::where('order_id', round($orderId))
                                ->sum('refund_amount');

                            if ($refundCount > 0) {
                                $refundStatus = "1";
                                $refundDate = $refundData->refund_date;
                                $refundAmount = $refundTotalData;
                            } else {
                                $refundStatus = "0";
                                $refundDate = "";
                                $refundAmount = $returnTotal;
                            }
                        }
                    }
                }

                if ($returnStatus == "1") {
                    $refundTotal = Refund::where('order_id', round($orderId))->sum('refund_amount');

                    if ($refundCount > 0) {
                        $refundStatus = "1";
                        $refundDate = $refundData['refund_date'];
                        $refundAmount = $refundTotal;
                    } else {
                        $refundStatus = "0";
                        $refundDate = "";
                        $refundAmount = $returnTotal;
                    }
                }

                if ($data['delivered_date'] != '') {
                    $delivered_date = date('d, M Y h:i A', strtotime($data['delivered_date']));
                } else {
                    $delivered_date = "";
                }

                if ($data['shop_discount'] == NULL) {
                    $discount = 0;
                } else {
                    $discount = $data['shop_discount'];
                }

                if ($data['offer_total'] == NULL) {
                    $offerTotal = 0;
                } else {
                    $offerTotal = $data['offer_total'];
                }

                $gTotal = ($orderTotal + $data['delivery_charge']) - ($discount + $offerTotal);

                $sqlShop = Shop::find($shopId)
                    ?->toArray();

                if (!$sqlShop)
                    throw ExceptionHelper::error([
                        "message" => "tbl_shop row where id: " . $shopId . " not found"
                    ]);

                $sqlDelivery = Employee::find($data['deliveryboy_id'])
                    ?->toArray();;

                if (!$sqlDelivery)
                    throw ExceptionHelper::error([
                        "message" => "tbl_employee row where id: " . $data['deliveryboy_id'] . " not found"
                    ]);

                $sqlRating = Rating::where('order_id', $orderId)
                    ->first()
                    ?->toArray();

                if (!$sqlRating)
                    throw ExceptionHelper::error([
                        "message" => "tbl_employee row where order_id: " . $orderId . " not found"
                    ]);

                $sqlShopData = $sqlShop;
                $sqlDeliveryData = $sqlDelivery;
                $dataRating = $sqlRating;

                if ($dataRating['rating'] == NULL) {
                    $reviewStatus = '0';
                } else {
                    $reviewStatus = '1';
                }
                if ($dataRating['rating'] == NULL) {
                    $rating = '0.0';
                } else {
                    $rating = $dataRating['rating'];
                }
                if ($dataRating['delivery_boy_rating'] == NULL) {
                    $deliveryrating = '0.0';
                } else {
                    $deliveryrating = $dataRating['delivery_boy_rating'];
                }
                if ($dataRating['review'] == NULL) {
                    $review = "";
                } else {
                    $review = $dataRating['review'];
                }
                if ($dataRating['delivery_boy_review'] == NULL) {
                    $deliveryReview = "";
                } else {
                    $deliveryReview = $dataRating['delivery_boy_review'];
                }

                if ($data['status'] == 3) {
                    $orderTime    = date('Y-m-d H:i:s', strtotime($data['date']));
                    $deliverTime  = date('Y-m-d H:i:s', strtotime($data['delivered_date']));
                    $start_datetime = new DateTime($orderTime);
                    $diff = $start_datetime->diff(new DateTime($deliverTime));

                    $deliveryTime = $diff->i;
                } else {
                    $deliveryTime = 0;
                }

                $savings = $mrpTotal - $orderTotal;
                $savings = $savings + $discount + $offerTotal;
                $orderTotal = $orderTotal;

                if ($checkData['edit_confirm'] == 0) {
                    $approveButton = 1;
                }

                if ($data['status'] == 3) {
                    $deliveredTime = date('Y-m-d H:i:s', strtotime($data['delivered_date']));
                    $returnTime    = date('Y-m-d H:i:s', strtotime($deliveredTime . ' +1 days'));
                    $currentTime   = date('Y-m-d H:i:s');
                    if ($currentTime <= $returnTime) {
                        $returnButton = 1;
                    } else {
                        $returnButton = 0;
                    }
                } else {
                    $returnButton = 0;
                }

                $productDiscount = $mrpTotal - $orderTotal;

                $arr = array(
                    "deliveryTime" => $deliveryTime,
                    "pCount" => $count,
                    "order_id" => str_pad($data['order_id'], 4, "0", STR_PAD_LEFT),
                    "otp" => $checkData['otp'],
                    "orderStatus" => $data['status'],
                    "order_date" => date('d M, Y h:i A', strtotime($data['date'])),
                    "expected_delivered_date" => $expected_delivered_date,
                    "delivered_date" => $delivered_date,
                    "shop_id" => $shopId,
                    "shopName" => CommonHelper::shopName($shopId),
                    "shopMobile" => $sqlShopData['mobile'],
                    "shopAddress" => CommonHelper::shopAddress($shopId),
                    "shopLat" => $sqlShopData['latitude'],
                    "shopLong" => $sqlShopData['longitude'],
                    "customer_name" => $data['name'],
                    "mobile" => $data['mobile'],
                    "address" => $data['flat'] . ", " . $data['landmark'],
                    "latitude" => $data['latitude'],
                    "longitude" => $data['longitude'],
                    "address_type" => $data['address_type'],
                    "payment_type" => $data['payment_type'],
                    "order_status" => $data['status'],
                    "order_total" => sprintf('%0.2f', $mrpTotal),
                    "productDiscount" => sprintf('%0.2f', $productDiscount),
                    "delivery_charge" => round($data['delivery_charge']),
                    "coupon" => $data['coupon'],
                    "discount_amount" => sprintf('%0.2f', $discount),
                    "offerTotal" => round($offerTotal),
                    "gTotal" => sprintf('%0.2f', $gTotal),
                    "productlist" => $productList,
                    "rating" => $rating,
                    "deliveryId" => $data['deliveryboy_id'],
                    "deliveryrating" => $deliveryrating,
                    "review" => $review,
                    "deliveryreview" => $deliveryReview,
                    "reviewStatus" => $reviewStatus,
                    "contactSupport" => $sqlHomeData['contact'],
                    "delivery_type" => $data['delivery_type'],
                    "delivery_name" => $sqlDeliveryData['name'],
                    "delivery_mobile" => $sqlDeliveryData['mobile'],
                    "delivery_lat" => $sqlDeliveryData['latitude'],
                    "delivery_long" => $sqlDeliveryData['longitude'],
                    "cancelReason" => $cancelReason,
                    "cancelRemark" => $cancelRemark,
                    "cancelDate" => $cancelDate,
                    "returnStage" => $returnStage,
                    "returnStatus" => $returnStatus,
                    "returnTotal" => $returnTotal,
                    "savings" => sprintf('%0.2f', $savings),
                    "otp" => $data['otp'],
                    "originalStatus" => 1,
                    "approveButton" => $approveButton,
                    "returnAble" => $returnAble,
                    "refundStatus" => $refundStatus,
                    "refundAmount" => $refundAmount,
                    "refundDate" => $refundDate,
                    "invoiceDisplay" => "0",
                    "extraStatus" => $extraStatus,
                    "returnButton" => $returnButton
                );

                return ResponseGenerator::generateResponseWithStatusCode(
                    ResponseGenerator::generateSuccessResponse([
                        "data" => $arr,
                    ])
                );
            } else
                throw ExceptionHelper::error([
                    "statusCode" => StatusCodes::NOT_FOUND,
                    "message" => "Order Detail Not Found."
                ]);
        } else {

            $sqlOrder = Order::where('order_id', $orderId);
            $count = $sqlOrder->count();

            if ($count > 0) {

                $homeData = Home::first();
                $sqlHomeData = $homeData->toArray();

                $data = $sqlOrder
                    ->first()
                    ->toArray();

                $sqlOrder1 = Order::where('order_id', $orderId)
                    ->get()
                    ->toArray();

                foreach ($sqlOrder1 as $sqlOrderData) {

                    if ($sqlOrderData['status'] == 7) {
                        $returnProduct = 1;
                        $returnStatus = "1";
                        $returnStage  = $sqlOrderData['return_status'];
                        $returnTotal  = $sqlOrderData['refund_amount'];
                    } else {
                        $returnProduct = 0;
                    }

                    if ($sqlOrderData['return_status'] == 3 && $sqlOrderData['status'] == 7) {
                        $refundAmount = $sqlOrderData['refund_amount'];
                        $refundStatus = $sqlOrderData['refund_status'];
                        $refundDate   = $sqlOrderData['refund_date'];
                    }

                    $shopId = $sqlOrderData['shop_id'];

                    $sqlProduct = Product::select('*')
                        ->where('id', $sqlOrderData['product_id'])
                        ->first()
                        ?->toArray();

                    $sqlProductData = $sqlProduct;

                    if (!$sqlProductData)
                        throw ExceptionHelper::error([
                            "message" => "product row where id: " . $sqlOrderData['product_id'] . " not found"
                        ]);

                    $productImage = "";
                    $directory = '../products/' . $sqlProductData['barcode'] . '/';
                    $partialName = '1.';
                    $files = glob($directory . '*' . $partialName . '*');

                    if ($files !== false) {
                        foreach ($files as $file) {
                            $productImage = basename($file);
                        }
                    } else {
                        $productImage = "";
                    }
                    if ($sqlProductData['returnable'] == 0) {
                        $returnAble = 0;
                    }

                    $primaryStatus = 1;
                    if ($sqlOrderData['offer_id'] > 0) {
                        $primaryStatus = 0;
                    }

                    $returnInformation = "";

                    $offerBundleData = OfferBundling::where('primary_unique_id', $sqlProductData['unique_code'])
                        ->where('status', 1)
                        ->first()
                        ?->toArray();

                    if ($offerBundleData) {
                        $sqlShopProductData = Product::select('id', 'weight', 'price', 'sellingprice', 'unit_id')
                            ->where('shop_id', $sqlOrderData['shop_id'])
                            ->where('unique_code', $offerBundleData["offer_unique_id"])
                            ->first();

                        if ($sqlShopProductData) {
                            $sqlOfferProductData = Order::where('order_id', $orderId)
                                ->where('product_id', $sqlShopProductData["product_id"])
                                ->where('offer_id', '>', 0)
                                ->first();

                            if ($sqlOfferProductData) {
                                $returnInformation = $sqlOrderData['qty'] . " X " . $sqlOrderData['product_name'] . " " . $sqlOrderData['weight'] . " will return with this product";
                            }
                        }
                    }

                    $shopTotal = Order::where('order_id', $orderId)
                        ->where('offer_type', 0)
                        ->sum('total');

                    if ($sqlOrderData['offer_type'] == 0 && $sqlOrderData['shop_discount'] > 0) {
                        $price = ($sqlOrderData['shop_discount'] * $sqlOrderData['price']) / $shopTotal;
                        $price = number_format($sqlOrderData['price'] - $price, 2);
                    } elseif ($sqlOrderData['offer_type'] == 2) {
                        $price = $sqlOrderData['price'] - $sqlOrderData['offer_total'];
                    } else {
                        $price = $sqlOrderData['price'];
                    }

                    $total = $sqlOrderData['qty'] * $price;
                    $mrpTotal1 = $sqlOrderData['qty'] * $sqlOrderData['mrp'];

                    $productList[]  = array(
                        'productId' => $sqlOrderData['product_id'],
                        'product_name' => $sqlOrderData['qty'] . " x  " .  ucfirst(
                            strtolower(
                                mb_convert_encoding(
                                    $sqlOrderData['product_name'],
                                    'UTF-8'
                                )
                            )
                        ),
                        'weight' => $sqlOrderData['weight'],
                        'price' => $price,
                        'sellingprice' => sprintf('%0.2f',     $sqlOrderData['mrp']),
                        'qty' => $sqlOrderData['qty'],
                        'total' => sprintf('%0.2f',     $total),
                        'sellingtotal' => sprintf('%0.2f', $mrpTotal1),
                        'photo' => url("products/") . "/" . $sqlOrderData['product_barcode'] . '/' . $productImage,
                        "returnProduct" => $returnProduct,
                        "returnable" => $sqlProductData['returnable'],
                        'newEdit' => $edit,
                        "primaryStatus" => $primaryStatus,
                        "returnInformation" => $returnInformation
                    );

                    $mrpTotal = $mrpTotal + ($sqlOrderData['qty'] * $sqlOrderData['mrp']);
                    $orderTotal = $orderTotal + $sqlOrderData['total'];
                }

                if ($data['expected_delivered_date'] != '') {
                    $expected_delivered_date = date('d, M Y h:i A', strtotime($data['expected_delivered_date']));
                } else {
                    $expected_delivered_date = "";
                }

                if ($data['status'] == 5 || $data['status'] == 6) {
                    $cancelReason = CommonHelper::cancelReason($data['reason_id']);
                    $cancelRemark = $data['cancel_remark'];
                    $cancelDate   = date('d, M Y h:i A', strtotime($data['cancel_date']));
                } else if ($data['status'] == 8) {
                    $cancelReason = CommonHelper::cancelReason($data['denial_reason_id']);
                    $cancelRemark = $data['denial_remark'];
                    $cancelDate   = date('d, M Y h:i A', strtotime($data['denial_date']));
                } else {
                    $cancelReason = "";
                    $cancelRemark = "";
                    $cancelDate   = "";
                }

                $sqlRefund = Refund::where('order_id', round($orderId));

                $refundData = $sqlRefund
                    ->first()
                    ?->toArray();

                if ($checkData['payment_type'] == 'PREPAID') {

                    if ($checkData['status'] == 5 || $checkData['status'] == 6 || $checkData['status'] == 8) {
                        if ($sqlRefund->count() > 0) {

                            $refundTotal = DB::table('tbl_refund')
                                ->where('order_id', round($orderId))
                                ->sum('refund_amount');

                            $refundTotalData = Refund::where('order_id', round($orderId))
                                ->sum('refund_amount');

                            $refundStatus = $refundData['status'];
                            $refundDate   = $refundData['refund_date'];
                            $refundAmount = $refundTotalData;
                        } else {
                            $refundStatus = "0";
                            $refundDate   = "";
                            $refundAmount = $checkData['refund_amount'];
                        }
                    }

                    $extraAmount = "0";
                }

                if ($returnStatus == "1") {
                    if ($sqlRefund->count() > 0) {
                        $refundStatus = "0";
                        $refundDate   = $refundData['refund_date'];
                        $refundAmount = $refundData['refund_amount'];
                    } else {
                        $refundStatus = "0";
                        $refundDate   = "";
                        $refundAmount = $returnTotal;
                    }
                }

                if ($data['delivered_date'] != '') {
                    $delivered_date = date('d, M Y h:i A', strtotime($data['delivered_date']));
                } else {
                    $delivered_date = "";
                }

                if ($data['shop_discount'] == NULL) {
                    $discount = 0;
                } else {
                    $discount = $data['shop_discount'];
                }

                if ($data['offer_total'] == NULL) {
                    $offerTotal = 0;
                } else {
                    $offerTotal = $data['offer_total'];
                }

                $gTotal = ($orderTotal + $data['delivery_charge']) - $discount - $offerTotal;

                $sqlShop = Shop::find($shopId)
                    ?->toArray();

                if (!$sqlShop)
                    throw ExceptionHelper::error([
                        "message" => "tbl_shop row where id: " . $shopId . " not found"
                    ]);

                $sqlDelivery = Employee::find($data['deliveryboy_id'])
                    ?->toArray();;

                if (!$sqlDelivery)
                    throw ExceptionHelper::error([
                        "message" => "tbl_employee row where id: " . $data['deliveryboy_id'] . " not found"
                    ]);

                $sqlRating = Rating::where('order_id', $orderId)
                    ->first()
                    ?->toArray();

                if (!$sqlRating)
                    throw ExceptionHelper::error([
                        "message" => "tbl_rating row where order_id: " . $orderId . " not found"
                    ]);

                $sqlShopData = $sqlShop;
                $sqlDeliveryData = $sqlDelivery;
                $dataRating = $sqlRating;

                if ($dataRating['rating'] == NULL) {
                    $reviewStatus = '0';
                } else {
                    $reviewStatus = '1';
                }
                if ($dataRating['rating'] == NULL) {
                    $rating = "0.0";
                } else {
                    $rating = $dataRating['rating'];
                }
                if ($dataRating['delivery_boy_rating'] == NULL) {
                    $deliveryrating = "0.0";
                } else {
                    $deliveryrating = $dataRating['delivery_boy_rating'];
                }
                if ($dataRating['review'] == NULL) {
                    $review = "";
                } else {
                    $review = $dataRating['delivery_boy_review'];
                }
                if ($dataRating['delivery_boy_review'] == NULL) {
                    $deliveryReview = "";
                } else {
                    $deliveryReview = $dataRating['delivery_boy_review'];
                }


                if ($data['status'] == 3) {
                    $orderTime    = date('Y-m-d H:i:s', strtotime($data['date']));
                    $deliverTime  = date('Y-m-d H:i:s', strtotime($data['delivered_date']));
                    $start_datetime = new DateTime($orderTime);
                    $diff = $start_datetime->diff(new DateTime($deliverTime));

                    $deliveryTime = $diff->i;
                } else {
                    $deliveryTime = 0;
                }

                if ($data['status'] == 3) {
                    $deliveredTime = date('Y-m-d H:i:s', strtotime($data['delivered_date']));
                    $returnTime    = date('Y-m-d H:i:s', strtotime($deliveredTime . ' +1 days'));
                    $currentTime   = date('Y-m-d H:i:s');
                    if ($currentTime <= $returnTime) {
                        $returnButton = 1;
                    } else {
                        $returnButton = 0;
                    }
                } else {
                    $returnButton = 0;
                }

                $savings = $mrpTotal - $orderTotal;
                $savings = $savings + $discount + $offerTotal;

                $productDiscount = $mrpTotal - $orderTotal;

                $productDiscount = $mrpTotal - $orderTotal;

                $arr = array(
                    "deliveryTime" => $deliveryTime,
                    "pCount" => $count,
                    "order_id" => str_pad($data['order_id'], 4, "0", STR_PAD_LEFT),
                    "otp" => $checkData['otp'],
                    "orderStatus" => $data['status'],
                    "order_date" => date('d M, Y h:i A', strtotime($data['date'])),
                    "expected_delivered_date" => $expected_delivered_date,
                    "delivered_date" => $delivered_date,
                    "shop_id" => $shopId,
                    "shopName" => CommonHelper::shopName($shopId),
                    "shopMobile" => $sqlShopData['mobile'],
                    "shopAddress" => CommonHelper::shopAddress($shopId),
                    "shopLat" => $sqlShopData['latitude'],
                    "shopLong" => $sqlShopData['longitude'],
                    "customer_name" => $data['name'],
                    "mobile" => $data['mobile'],
                    "address" => $data['flat'] . ", " . $data['landmark'],
                    "latitude" => $data['latitude'],
                    "longitude" => $data['longitude'],
                    "address_type" => $data['address_type'],
                    "payment_type" => $data['payment_type'],
                    "order_status" => $data['status'],
                    "order_total" => sprintf('%0.2f', $mrpTotal),
                    "productDiscount" => sprintf('%0.2f', $productDiscount),
                    "delivery_charge" => round($data['delivery_charge']),
                    "coupon" => $data['coupon'],
                    "discount_amount" => sprintf('%0.2f', $discount),
                    "offerTotal" => round($offerTotal),
                    "gTotal" => sprintf('%0.2f', $gTotal),
                    "productlist" => $productList,
                    "rating" => $rating,
                    "deliveryId" => $data['deliveryboy_id'],
                    "deliveryrating" => $deliveryrating,
                    "review" => $review,
                    "deliveryreview" => $deliveryReview,
                    "reviewStatus" => $reviewStatus,
                    "contactSupport" => $sqlHomeData['contact'],
                    "delivery_type" => $data['delivery_type'],
                    "delivery_name" => $sqlDeliveryData['name'],
                    "delivery_mobile" => $sqlDeliveryData['mobile'],
                    "delivery_lat" => $sqlDeliveryData['latitude'],
                    "delivery_long" => $sqlDeliveryData['longitude'],
                    "cancelReason" => $cancelReason,
                    "cancelRemark" => $cancelRemark,
                    "cancelDate" => $cancelDate,
                    "returnStage" => $returnStage,
                    "returnStatus" => $returnStatus,
                    "returnTotal" => $returnTotal,
                    "savings" => sprintf('%0.2f', $savings),
                    "otp" => $data['otp'],
                    "originalStatus" => 0,
                    "approveButton" => $approveButton,
                    "returnAble" => $returnAble,
                    "refundStatus" => $refundStatus,
                    "refundAmount" => $refundAmount,
                    "refundDate" => $refundDate,
                    "invoiceDisplay" => "0",
                    "returnButton" => $returnButton
                );

                return ResponseGenerator::generateResponseWithStatusCode(
                    ResponseGenerator::generateSuccessResponse([
                        "data" => $arr,
                    ])
                );
            } else
                throw ExceptionHelper::error([
                    "statusCode" => StatusCodes::NOT_FOUND,
                    "message" => "Order Detail Not Found."
                ]);
        }
    }
}
