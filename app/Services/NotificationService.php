<?php

namespace App\Services;

use App\Constants\StatusCodes;
use App\Helpers\CommonHelper;
use App\Helpers\RequestValidator;
use App\Helpers\ResponseGenerator;
use Illuminate\Http\Request;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;

// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @todo Document this
 */
class NotificationService
{
    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function notificationList()
    {
        $urlToPrepend = url('notification/');

        $notificationList = Notification::select("id", DB::raw("DATE_FORMAT(date, '%d %M %Y, %h.%i %p') as date"), "description",  DB::raw("CONCAT('$urlToPrepend/', image) as image"))
            ->whereIn("status", ["1"])
            ->get()
            ->toArray();

        return ResponseGenerator::generateResponseWithStatusCode(
            ResponseGenerator::generateSuccessResponse([
                "data" => [
                    "notificationList" => $notificationList
                ],
            ])
        );
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function testCeoNotification()
    {
        /* Send Notification for ShopKeeper */
        $title = "Congratulations! You Have Received New Order";
        $body  = "Order ID : 001";
        $token = "46";

        CommonHelper::ceoNewOrderNotification($title, $body, "001", $token);

        return ResponseGenerator::generateResponseWithStatusCode(
            ResponseGenerator::generateSuccessResponse([
                "message" => "Done",
            ])
        );
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function testCeoNotification2(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [],
            [
                "token" => "required",
            ]
        );

        /* Send Notification for ShopKeeper */
        $title = "Congratulations! You Have Received New Order";
        $body  = "Order ID : 001";
        $token = $data["token"];

        CommonHelper::ceoNewOrderNotification2($title, $body, "001", $token);

        return ResponseGenerator::generateResponseWithStatusCode(
            ResponseGenerator::generateSuccessResponse([
                "message" => "Done",
            ])
        );
    }
}
