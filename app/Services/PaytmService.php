<?php

namespace App\Services;

use Paytm\PaytmChecksum\PaytmChecksum;
use App\Constants\StatusCodes;
use App\Helpers\RequestValidator;
use App\Models\Home;
use App\Models\Order;
use App\Models\PaytmPaymentLog;
use Illuminate\Http\Request;


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

        return [
            "response" => [
                "status" => true,
                "statusCode" => StatusCodes::OK,
                "data" => $config,
                "message" => null,
            ],
            "statusCode" => StatusCodes::OK
        ];
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

        $sqlOrderTotal = Order::select("order_total")
            ->where("order_reference", $referenceId)
            ->groupBy("order_reference");

        $orderTotalData = $sqlOrderTotal
            ->first()
            ?->toArray();

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
            "your_merchant_key"
        );

        $paytmParams['head'] = array('signature' => $checksum);
        $post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);
        $url = "https://securegw-stage.paytm.in/theia/api/v1/initiateTransaction?mid=$mid&orderId=$orderid";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $result = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($result);
        $data = array(
            'amount' => $orderTotalData['order_total'],
            'txn' => $response->body->txnToken,
            'orderid' => $orderid,
            'isStaging' => "true",
            'mid' => $mid,
            'checksum' => $checksum,
            "callbackUrl" => "https://securegw-stage.paytm.in/theia/paytmCallback?ORDER_ID=$orderid"
        );
        $txn_id = $response->body->txnToken;

        return [
            "response" => [
                "status" => true,
                "statusCode" => StatusCodes::OK,
                "data" => [
                    "data" => $data
                ],
                "message" => "Tranaction token created",
            ],
            "statusCode" => StatusCodes::OK
        ];
    }
}
