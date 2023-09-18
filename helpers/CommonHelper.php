<?php

namespace App\Helpers;

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
