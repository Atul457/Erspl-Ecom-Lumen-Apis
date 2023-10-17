<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;

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



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function testCeoNotification()
    {
        $res = $this->service->testCeoNotification();
        return response($res["response"], $res["statusCode"]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function testCeoNotification2(Request $req)
    {
        $res = $this->service->testCeoNotification2($req);
        return response($res["response"], $res["statusCode"]);
    }
}
