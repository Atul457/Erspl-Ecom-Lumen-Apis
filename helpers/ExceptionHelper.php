<?php

namespace App\Helpers;

use App\Constants\StatusCodes;
use Exception;

// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @TODO Document this
 */
class ExceptionHelper extends Exception
{

    /** @var array|null */
    public $data;
    public bool $status;
    public int $statusCode;

    public function __construct(array $errorInfo)
    {
        $this->status = false;
        $this->data = $errorInfo["data"] ?? [];
        $this->message = $errorInfo["message"] ?? "Someting went Wrong. Try Again";
        $this->statusCode = $errorInfo["statusCode"] ?? StatusCodes::INTERNAL_SERVER_ERROR;
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public static function somethingWentWrong(array $errorInfo = [])
    {
        return new ExceptionHelper(
            ResponseGenerator::generateErrorResponse([
                "statusCode" => StatusCodes::INTERNAL_SERVER_ERROR,
                "data"  => $errorInfo["data"] ?? null,
                "message" => $errorInfo["message"] ?? "Someting went Wrong. Try Again"
            ])
        );
    }


    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public static function unAuthorized(array $errorInfo = [])
    {
        return new ExceptionHelper(
            ResponseGenerator::generateErrorResponse([
                "data"  => $errorInfo["data"] ?? null,
                "statusCode" => StatusCodes::UNAUTHORIZED,
                "message" => $errorInfo["message"] ?? "Unauthorized",
            ])
        );
    }


    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public static function notFound(array $errorInfo = [])
    {
        return new ExceptionHelper(
            ResponseGenerator::generateErrorResponse([
                "data"  => $errorInfo["data"] ?? null,
                "statusCode" => StatusCodes::NOT_FOUND,
                "message" => $errorInfo["message"] ?? "Resource not found",
            ])
        );
    }


    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public static function alreadyExists(array $errorInfo = [])
    {
        return new ExceptionHelper(
            ResponseGenerator::generateErrorResponse([
                "data"  => $errorInfo["data"] ?? null,
                "statusCode" => StatusCodes::RESOURCE_ALREADY_EXISTS,
                "message" => $errorInfo["message"] ?? "Resource already exists",
            ])
        );
    }


    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public static function unprocessable(array $errorInfo = [])
    {
        return new ExceptionHelper(
            ResponseGenerator::generateErrorResponse([
                "data"  => $errorInfo["data"] ?? null,
                "statusCode" => StatusCodes::VALIDATION_ERROR,
                "message" => $errorInfo["message"] ?? "Unprocessable Entity",
            ])
        );
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public static function error(array $errorInfo = [])
    {
        return new ExceptionHelper(
            ResponseGenerator::generateErrorResponse([
                "statusCode" =>  $errorInfo["statusCode"] ?? StatusCodes::INTERNAL_SERVER_ERROR,
                "data"  => $errorInfo["data"] ?? null,
                "message" => $errorInfo["message"] ?? "Someting went Wrong. Try Again"
            ])
        );
    }
}
