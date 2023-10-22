<?php

namespace App\Services;

use App\Constants\StatusCodes;
use App\Helpers\ExceptionHelper;
use App\Helpers\ResponseGenerator;
use App\Models\CancelReason;

// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @todo Document this
 */
class CancelReasonService
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
            throw ExceptionHelper::error();

        return ResponseGenerator::generateResponseWithStatusCode(
            ResponseGenerator::generateSuccessResponse([
                "data" => [
                    "reasonList" => $reasonList
                ],
            ])
        );
    }
}
