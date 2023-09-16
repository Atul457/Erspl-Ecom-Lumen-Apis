<?php

namespace App\Http\Controllers;

use App\Helpers\CommonHelper;
use App\Helpers\ExceptionHelper;
use App\Helpers\RequestValidator;
use App\Models\Order;
use App\Models\OrderEdited;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            "statusCode" => 200,
            "messsage" => null
        ], 200);
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
                    $orderTime    = date('Y-m-d H:i:s', strtotime($data['date']));
                    $deliverTime  = date('Y-m-d H:i:s');
                    $start_datetime = new DateTime($orderTime);
                    $diff = $start_datetime->diff(new DateTime($deliverTime));
                    $deliveryTime = $diff->i;
                } else
                    $deliveryTime = 61; // Order time greater than 60 mins.
            } else
                $deliveryTime = 62; // Order completed

            $orderIDs = array();
            $couponDiscount = 0;
            $offerDiscount  = 0;

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
            $itemsCount  = $itemCount1 + $itemCount2 + $itemCount3;

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
            "statusCode" => 200,
            "messsage" => null
        ], 200);
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

        $orderId  = $data['orderId'];

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

        $orderStage   = array();
        $placedDate   = "";
        $approveDate  = "";
        $dispatchDate = "";
        $deliverDate  = "";

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
            "statusCode" => 200,
            "messsage" => null
        ], 200);
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

        $orderId  = $data['orderId'];

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
        $data   = $sqlOrder[0];
        $orderStatus = $data['status'];

        return response([
            "data" => [
                "orderStatus" => $orderStatus,
            ],
            "status" =>  true,
            "statusCode" => 200,
            "messsage" => null
        ], 200);
    }
}
