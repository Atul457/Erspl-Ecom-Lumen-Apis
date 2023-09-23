<?php

namespace App\Http\Controllers;

use App\Constants\StatusCodes;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{

    private NotificationService $service;

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function __construct()
    {
        $this->service = new NotificationService();
    }


    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function notificationList()
    {
        $res = $this->service->notificationList();
        return response($res["response"], $res["statusCode"]);
    }
}
