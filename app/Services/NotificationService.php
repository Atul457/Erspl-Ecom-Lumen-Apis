<?php

namespace App\Services;

use App\Constants\StatusCodes;
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

            return [
                "response" => [
                    "data" => [
                        "notificationList" => $notificationList
                    ],
                    "status" =>  true,
                    "statusCode" => StatusCodes::OK,
                    "messsage" => null
                ],
                "statusCode" => StatusCodes::OK
            ];

        return response([
            "data" => [
                "notificationList" => $notificationList
            ],
            "status" =>  true,
            "statusCode" => StatusCodes::OK,
            "messsage" => null
        ], StatusCodes::OK);
    }
}
