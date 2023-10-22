<?php

namespace App\Helpers;

use App\Models\CancelReason;
use App\Models\Home;
use App\Models\Product;
use App\Models\Registration;
use App\Models\Shop;
use App\Models\UomType;


// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @todo Document this
 */
class CommonHelper
{

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public static function uomName(int $unitId)
    {
        $sqlCategoryData = UomType::where("id", $unitId)->first()->toArray();
        return $sqlCategoryData['name'] ?? "";
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public static function generateTXN(int $digits = 16)
    {
        $i = 0; //counter
        $pin = ""; //our default pin is blank.
        while ($i < $digits) {
            //generate a random number between 0 and 9.
            $pin .= mt_rand(0, 9);
            $i++;
        }
        return $pin;
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public static function shopName(int $shopId)
    {
        $shop = Shop::where("id", $shopId)->first();

        if ($shop)
            $shop = $shop->toArray();
        else
            throw ExceptionHelper::error([
                "Shop with id:" . $shopId . " not found"
            ]);

        return $shop['name'] ?? "";
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public static function shopAddress(int $shopId)
    {
        $shop = Shop::where("id", $shopId)->first();

        if ($shop)
            $shop = $shop->toArray();
        else
            throw ExceptionHelper::error([
                "Shop with id:" . $shopId . " not found"
            ]);

        return $shop['address'] ?? "";
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public static function shopDeliveryTime(int $shopId)
    {
        $shop = Shop::where("id", $shopId)->first();

        if ($shop)
            $shop = $shop->toArray();
        else
            throw ExceptionHelper::error([
                "Shop with id:" . $shopId . " not found"
            ]);

        return $shop['delivery_time'] ?? "";
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public static function starPendingOrderNotification($head, $body, $orderId, $token)
    {
        $headings      = array(
            "en" => $head
        );
        $content = array(
            "en" => $body
        );
        $playerId = array();
        array_push($playerId, $token);
        $fields = array(
            'app_id' => env("APP_ID"),
            'android_channel_id' => env("ANDROID_CHANNEL_ID"),
            'include_player_ids' => $playerId,
            'data' => array("orderId" => $orderId, "type" => "pendingOrder"),
            'headings' => $headings,
            'contents' => $content
        );

        $fields = json_encode($fields);

        return CurlRequestHelper::sendRequest([
            "method" => "POST",
            "url" => "https://onesignal.com/api/v1/notifications",
            "headers" => [
                'Content-Type: application/json; charset=utf-8'
            ],
            "data" => $fields
        ]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public static function sendPushNotification($registration_ids, $arrNotification)
    {
        $sqlHome = Home::select("notification_key")->first();
        $sqlHomeData = $sqlHome->toArray();
        $key = $sqlHomeData['notification_key'];
        $path_to_firebase_cm = 'https://fcm.googleapis.com/fcm/send';

        $fields = array(
            'to' => $registration_ids,
            "priority" => "high",
            'data' => $arrNotification
        );

        $headers = array(
            'Authorization:key=' . $key,
            'Content-Type:application/json'
        );

        $fields = json_encode($fields);

        return CurlRequestHelper::sendRequest([
            "method" => "POST",
            "url" => $path_to_firebase_cm,
            "headers" =>  $headers,
            "data" => $fields,
            "additionalSetOptArray" => [
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
            ]
        ]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public static function ceoCancelOrderNotification($head, $body, $type, $orderId, $token)
    {
        $playerId = array();
        $content = array("en" => $body);
        $headings = array("en" => $head);

        array_push($playerId, $token);

        $fields = array(
            'app_id' => "6b25b170-6323-407b-a49a-3cce0b94a27f",
            'android_channel_id' => "9c0af781-9fdd-421f-97f4-cd723255a16f",
            'include_external_user_ids' => $playerId,
            'data' => array("orderId" => $orderId, "type" => $type),
            'android' => array("priority" => 10),
            'headings' => $headings,
            'contents' => $content
        );

        $oneSignalAuthToken = env("ONE_SIGNAL_AUTH_TOKEN");
        $fields = json_encode($fields);

        return CurlRequestHelper::sendRequest([
            "method" => "POST",
            "url" => "https://onesignal.com/api/v1/notifications",
            "headers" =>  array(
                'Content-Type: application/json; charset=utf-8',
                "Authorization: Basic $oneSignalAuthToken"
            ),
            "data" => $fields,
        ]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public static   function starCancelOrderNotification($head, $body, $orderId, $token)
    {
        $playerId = array();
        $content = array("en" => $body);
        $headings = array("en" => $head);

        array_push($playerId, $token);

        $fields = array(
            'app_id' => env("APP_ID_"),
            'include_player_ids' => $playerId,
            'android_channel_id' => env("ANDROID_CHANNEL_ID_"),
            'data' => array(
                "orderId" => $orderId,
                "type" => "cancelOrder"
            ),
            'headings' => $headings,
            'contents' => $content
        );

        $fields = json_encode($fields);

        return CurlRequestHelper::sendRequest([
            "method" => "POST",
            "url" => "https://onesignal.com/api/v1/notifications",
            "headers" =>  array('Content-Type: application/json; charset=utf-8'),
            "data" => $fields,
        ]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public static function productHsn($productId)
    {
        $sqlProduct = Product::find($productId);
        return $sqlProduct['hsn_code'] ?? null;
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public static function cancelReason($reason_id)
    {
        $sqlCategory = CancelReason::where("id", $reason_id)
            ->first()
            ?->toArray();
        $sqlCategoryData = $sqlCategory;
        return $sqlCategoryData['name'];
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public static function customerNameWeb($customer_id)
    {
        $sqlCategory = Registration::select("first_name", "middle_name", "last_name")
            ->where("id", $customer_id)
            ->first()
            ?->toArray();
        $sqlCategoryData = $sqlCategory;
        return ($sqlCategoryData['first_name'] ?? "")
            . " " .
            ($sqlCategoryData['middle_name'] ?? "")
            . " " .
            ($sqlCategoryData['last_name'] ?? "");
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public static function starNewOrderNotification($head, $body, $orderId, $token)
    {
        $playerId = array();
        $headings = array("en" => $head);
        $content = array("en" => $body);
        array_push($playerId, $token);

        $fields = array(
            'app_id' => "bbf49dc7-fcbd-4db7-9ec1-6056ffc64084",
            'android_channel_id' => "9cc1209b-4861-4fcb-9d14-ef272a5652aa",
            'include_player_ids' => $playerId,
            'data' => array("orderId" => $orderId, "type" => "newOrder"),
            'android' => array("priority" => 10),
            'headings' => $headings,
            'contents' => $content
        );

        $fields = json_encode($fields);

        return CurlRequestHelper::sendRequest([
            "method" => "POST",
            "url" => "https://onesignal.com/api/v1/notifications",
            "headers" =>  array('Content-Type: application/json; charset=utf-8'),
            "data" => $fields,
        ]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public static function ceoNewOrderNotification2($head, $body, $orderId, $token)
    {
        $headings = array(
            "en" => $head
        );

        $content = array(
            "en" => $body
        );

        $playerId = array();
        array_push($playerId, $token);
        $fields = array(
            'app_id' => env("APP_ID__"),
            'android_channel_id' => env("ANDROID_CHANNEL_ID__"),
            'include_player_ids' => $playerId,
            'priority' => 0,
            'data' => array(
                "orderId" => $orderId,
                "type" => 'newOrder'
            ),
            'headings' => $headings,
            'contents' => $content
        );

        $fields = json_encode($fields);

        return CurlRequestHelper::sendRequest([
            "method" => "POST",
            "url" => "https://onesignal.com/api/v1/notifications",
            "headers" => array('Content-Type: application/json; charset=utf-8'),
            "data" => $fields,
        ]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public static function ceoNewOrderNotification($head, $body, $orderId, $token)
    {
        $headings = array(
            "en" => $head
        );
        $content = array(
            "en" => $body
        );
        $playerId = array();
        array_push($playerId, $token);
        $fields = array(
            'priority' => 1,
            'app_id' => env("APP_ID"),
            'content_available' => true,
            'include_external_user_ids' => $playerId,
            'android_channel_id' => env("ANDROID_CHANNEL_ID"),
            'data' => array("orderId" => $orderId, "type" => 'newOrder'),
            'headings' => $headings,
            'contents' => $content
        );

        $fields = json_encode($fields);
        $oneSignalAuthToken = env("ONE_SIGNAL_AUTH_TOKEN") ?? "";

        return CurlRequestHelper::sendRequest([
            "method" => "POST",
            "url" => "https://onesignal.com/api/v1/notifications",
            "headers" => array(
                "Content-Type: application/json; charset=utf-8",
                "Authorization: Basic $oneSignalAuthToken"
            ),
            "data" => $fields,
        ]);
    }
}
