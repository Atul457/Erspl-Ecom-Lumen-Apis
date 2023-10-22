<?php

namespace App\Helpers;

use App\Constants\StatusCodes;

// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @todo Document this
 */
class ResponseGenerator
{
    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     * @param $response holds
     */
    public static function generateErrorResponse(array $response)
    {
        return [
            "status" => $response["status"] ?? false,
            "data"  => $response["data"] ?? null,
            "statusCode" => $response["statusCode"] ?? StatusCodes::INTERNAL_SERVER_ERROR,
            "message" => $response["message"] ?? "Someting went Wrong. Try Again",
        ];
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public static function generateSuccessResponse(array $response)
    {
        return [
            "status" => ($response["status"] ?? true),
            "data"  => ($response["data"] ?? null),
            "statusCode" => ($response["statusCode"] ?? StatusCodes::OK),
            "message" => $response["message"] ?? null,
        ];
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public static function generateResponseWithStatusCode(
        array $response
    ) {
        return [
            "response" =>  $response,
            "statusCode" => $response["statusCode"] ?? StatusCodes::INTERNAL_SERVER_ERROR
        ];
    }
}
