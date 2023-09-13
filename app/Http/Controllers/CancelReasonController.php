<?php

namespace App\Http\Controllers;

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

        try {

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
                "statusCode" => 200,
                "messsage" => null
            ], 200);
        } catch (ExceptionHelper $e) {

            Log::error($e->getMessage());

            return response([
                "data" => $e->data,
                "status" => $e->status,
                "statusCode" => $e->statusCode,
                "message" => $e->getMessage(),
            ], $e->statusCode);
        }
    }
}
