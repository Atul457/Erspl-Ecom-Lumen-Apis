<?php

namespace App\Helpers;

use App\Models\CancelReason;
use App\Models\Home;
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
    public static function shopName(int $shopId)
    {
        $shop = Shop::where("id", $shopId)->first();

        if ($shop)
            $shop = $shop->toArray();
        else
            throw ExceptionHelper::somethingWentWrong([
                "Shop with id:" . $shopId . " not found"
            ]);

        return $shop['name'] ?? "";
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
            throw ExceptionHelper::somethingWentWrong([
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
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        curl_exec($ch);
        curl_close($ch);
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

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $path_to_firebase_cm);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
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

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            "Authorization: Basic $oneSignalAuthToken"
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
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
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
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
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public static function ceoNewOrderNotification($head, $body, $orderId, $token)
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
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8', 'Authorization: Basic NTczZTcwYzItMTY2ZC00MmIxLTkxZTYtODgzM2MwNjdkOWM4'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        curl_exec($ch);
        curl_close($ch);
    }
}
