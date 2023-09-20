<?php

namespace App\Http\Controllers;

use App\Constants\StatusCodes;
use App\Helpers\ExceptionHelper;
use App\Models\CancelReason;
use Illuminate\Support\Facades\Log;

class CancelReasonController extends Controller
{
    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function reasonList()
    {
        $reasonList = CancelReason::select("id", "name")
            ->where([
                "status" => 1
            ])
            ->get()
            ->toArray();

        if (!count($reasonList))
            throw ExceptionHelper::somethingWentWrong();

        return response([
            "data" => [
                "reasonList" => $reasonList
            ],
            "status" =>  true,
            "statusCode" => StatusCodes::OK,
            "messsage" => null
        ], StatusCodes::OK);
    }
}
