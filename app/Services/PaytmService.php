<?php

namespace App\Services;

use paytm\paytmchecksum\PaytmChecksum;
use App\Constants\StatusCodes;
use App\Helpers\CurlRequestHelper;
use App\Helpers\RequestValidator;
use App\Helpers\ResponseGenerator;
use App\Helpers\UtilityHelper;
use App\Models\Home;
use App\Models\Order;
use App\Models\PaytmPaymentLog;
use Illuminate\Http\Request;

// H:\work\projects\ecom-lumen\vendor\paytm\paytmchecksum\paytmchecksum\PaytmChecksum.php


// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @todo Document this
 */
class PaytmService
{

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function paytmConfig()
    {
        $config = array(
            'mid' => env("PAYTM_MID_CONFIG"),
            'mkey' => env("PAYTM_MKEY_CONFIG"),
            'env' => 'test',
            'merchant_website' => 'DEFAULT',
            'channel' => 'WEB',
            'industry_type' => 'Retail'
        );

        return ResponseGenerator::generateResponseWithStatusCode(
            ResponseGenerator::generateSuccessResponse([
                "data" => $config,
            ])
        );
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function createpayment(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [],
            [
                "orderId" => "required|numeric"
            ]
        );

        $userId = $req->user()->id;
        $referenceId = trim($data["orderId"]);
        $date = date('Y-m-d');
        $currentDate = date('Y-m-d H:i:s');

        UtilityHelper::disableSqlStrictMode();

        $sqlOrderTotal = Order::select("order_total")
            ->where("order_reference", $referenceId)
            ->groupBy("order_reference");

        $orderTotalData = $sqlOrderTotal
            ->first()
            ?->toArray();

        UtilityHelper::enableSqlStrictMode();

        $paymentLog = new PaytmPaymentLog();
        $paymentLog->order_reference = $referenceId;
        $paymentLog->customer_id = $userId;
        $paymentLog->amount = $orderTotalData['order_total'];
        $paymentLog->order_date = $date;
        $paymentLog->datetime = $currentDate;
        $paymentLog->save();

        // Retrieve the data from the tbl_home table
        $sqlShop = Home::select('paytm_mid', 'paytm_mkey')->first();
        $sqlShopData = $sqlShop->toArray();

        $mid = $sqlShopData["paytm_mid"];
        $mkey = $sqlShopData["paytm_mkey"];

        $paytmParams = array();
        $orderid = $referenceId;

        $paytmParams["body"] = array(
            "mid" => $mid,
            "orderId" => $orderid,
            "requestType" => "Payment",
            "websiteName" => 'WEBSTAGING',
            "callbackUrl" => "https://securegw-stage.paytm.in/theia/paytmCallback?ORDER_ID=$orderid",
            "txnAmount" => array(
                "value"     => $orderTotalData['order_total'],
                "currency"  => "INR",
            ),
            "userInfo"  => array(
                "custId"    => $userId,
            ),
        );

        $checksum = PaytmChecksum::generateSignature(
            json_encode($paytmParams, JSON_UNESCAPED_SLASHES),
            $mkey
        );

        $paytmParams['head'] = array('signature' => $checksum);
        $post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);
        $url = "https://securegw-stage.paytm.in/theia/api/v1/initiateTransaction?mid=$mid&orderId=$orderid";

        $result = CurlRequestHelper::sendRequest([
            "method" => "POST",
            "url" => $url,
            "headers" => [
                'Content-Type: application/json'
            ],
            "data" => $post_data,
            "additionalSetOptArray" => [
                CURLOPT_FOLLOWLOCATION => 1
            ]
        ]);

        $response = json_decode($result["response"]);

        $data = array(
            'amount' => $orderTotalData['order_total'],
            'txn' => $response?->body?->txnToken ?? null,
            'orderid' => $orderid,
            'isStaging' => "true",
            'mid' => $mid,
            'checksum' => $checksum,
            "callbackUrl" => "https://securegw-stage.paytm.in/theia/paytmCallback?ORDER_ID=$orderid"
        );

        return ResponseGenerator::generateResponseWithStatusCode(
            ResponseGenerator::generateSuccessResponse([
                "data" => $data,
                "message" => "Tranaction token created",
            ])
        );
    }
}
