<?php

namespace App\Exceptions;

use App\Helpers\ErrorHandlerHelper;
use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
        Throwable::class
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        $errorHelperInstance = new ErrorHandlerHelper($request, $exception);
        $errorHelperInstance =  [
            "statusCode" => $errorHelperInstance->statusCode,
            "response" => [
                "data" => $errorHelperInstance->data,
                "status" => $errorHelperInstance->status,
                "message" => $errorHelperInstance->message,
                "statusCode" => $errorHelperInstance->statusCode,
            ]
        ];

        return response(
            $errorHelperInstance["response"],
            $errorHelperInstance["statusCode"]
        );
        return parent::render($request, $exception);
    }
}
