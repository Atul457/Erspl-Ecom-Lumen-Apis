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
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDO;

// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @todo Document this
 */
class OrderController extends Controller
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
            throw ExceptionHelper::notFound([
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
                throw ExceptionHelper::notFound([
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
                    ExceptionHelper::notFound([
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
                    ExceptionHelper::notFound([
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

        return response([
            "data" => [
                "orderStage" => $status,
                "orderList" => $orderList,
            ],
            "status" =>  true,
            "statusCode" => StatusCodes::OK,
            "messsage" => null
        ], StatusCodes::OK);
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
            throw ExceptionHelper::notFound([
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

        return response([
            "data" => [
                "count" => $count,
                "pageCount" => $pageCount,
                "orderReferenceList" => $orderReferenceList,
            ],
            "status" =>  true,
            "statusCode" => StatusCodes::OK,
            "messsage" => null
        ], StatusCodes::OK);
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
            throw ExceptionHelper::notFound([
                "message" => "orders not found via order_id: $orderId and group_by('order_id')"
            ]);

        $orderStage = array();
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

        return response([
            "data" => [
                "placedDate" => $placedDate,
                "approveDate" => $approveDate,
                "deliverDate" => $deliverDate,
                "orderId" => $data['order_id'],
                "dispatchDate" => $dispatchDate,
                "orderStatus" => $data['order_id'],
            ],
            "status" =>  true,
            "statusCode" => StatusCodes::OK,
            "messsage" => null
        ], StatusCodes::OK);
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
            throw ExceptionHelper::notFound([
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

        return response([
            "data" => [
                "orderStatus" => $orderStatus,
            ],
            "status" =>  true,
            "statusCode" => StatusCodes::OK,
            "messsage" => null
        ], StatusCodes::OK);
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
                throw ExceptionHelper::somethingWentWrong([
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
                            ExceptionHelper::notFound([
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
                                throw ExceptionHelper::somethingWentWrong([
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
                                throw ExceptionHelper::somethingWentWrong([
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
                    ->where("referral_by", "!=", "");
                // ->where("referral_status", "0");

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

            throw ExceptionHelper::notFound([
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


        return response([
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
            "status" =>  true,
            "statusCode" => StatusCodes::OK,
            "messsage" => "Order Saved Successfully."
        ], StatusCodes::OK);
    }
}
