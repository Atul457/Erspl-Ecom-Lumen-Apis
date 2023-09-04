<?php

namespace App\Helpers;

use Exception;
use Illuminate\Http\Request;

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
        $this->data = $errorInfo["data"] ?? null;
        $this->statusCode = $errorInfo["statusCode"] ?? 500;
        $this->message = $errorInfo["message"] ?? "Something went wrong";
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public static function somethingWentWrong(array $errorInfo = [])
    {
        return new ExceptionHelper([
            "status" => false,
            "statusCode" => 500,
            "data"  => $errorInfo["data"] ?? null,
            "message" => $errorInfo["message"] ?? "Something went wrong",
        ]);
    }


    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public static function unAuthorized(array $errorInfo = [])
    {
        return new ExceptionHelper([
            "status" => false,
            "statusCode" => 401,
            "data"  => $errorInfo["data"] ?? null,
            "message" => $errorInfo["message"] ?? "Unauthorized",
        ]);
    }


    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public static function notFound(array $errorInfo = [])
    {
        return new ExceptionHelper([
            "status" => false,
            "statusCode" => 404,
            "data"  => $errorInfo["data"] ?? null,
            "message" => $errorInfo["message"] ?? "Resource not found",
        ]);
    }


    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public static function alreadyExists(array $errorInfo = [])
    {
        return new ExceptionHelper([
            "status" => false,
            "statusCode" => 409,
            "data"  => $errorInfo["data"] ?? null,
            "message" => $errorInfo["message"] ?? "Resource already exists",
        ]);
    }


      // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public static function unprocessable(array $errorInfo = [])
    {
        return new ExceptionHelper([
            "status" => false,
            "statusCode" => 422,
            "data"  => $errorInfo["data"] ?? null,
            "message" => $errorInfo["message"] ?? "Unprocessable Entity",
        ]);
    }
}
