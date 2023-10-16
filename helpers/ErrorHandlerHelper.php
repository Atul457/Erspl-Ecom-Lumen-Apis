<?php

namespace App\Helpers;
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

use App\Constants\StatusCodes;
use App\Helpers\ExceptionHelper;
// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Throwable;

/**
 * @info TODO Document this
 */
class ErrorHandlerHelper
{
    public $data = [];
    public $message = null;
    public $payload = null;
    public $fileName = null;
    public $lineNumber = null;
    public bool $status = false;
    public $functionName = null;
    public $errorSnapshot = null;
    public int $statusCode = StatusCodes::INTERNAL_SERVER_ERROR;
    public $originalMessage = null;


    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @info TODO Document this
     */
    public function __construct($req, Throwable $e)
    {
        $this->payload = $req->input();
        $this->handleThrowableError($e);

        switch (true) {

            case $e instanceof ExceptionHelper:
                $this->handleExceptionHelperError($e);
                break;

            case $e instanceof ValidationException:
                $this->handleValidationError($e);
                break;

            default:
                $this->handleThrowableError($e);
                break;
        }

        $this->originalMessage = $this->message;

        if ($this->statusCode === StatusCodes::INTERNAL_SERVER_ERROR)
            $this->message = "something went wrong";

        Log::error("\nFunction name: $this->functionName\nFile name: $this->fileName\nLine number: $this->lineNumber\nMessage: $this->originalMessage\nPayload: " . json_encode($this->payload) . "\nUser id: " . ($req?->user()?->id ?? null) . "\nResponse data: " . json_encode($this->data) . "\nError snap: $this->errorSnapshot\n\n");
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @info TODO Document this
     */
    private function handleExceptionHelperError(ExceptionHelper $e)
    {
        $this->data = is_string($e->data) ? (array) $e->data : $e->data;
        $this->status = $e->status;
        $this->message = $e->getMessage();
        $this->statusCode = $e->statusCode;
        return;
    }


    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @info TODO Document this
     */
    private function handleValidationError(ValidationException $e)
    {
        $this->data = [];
        $this->status = false;
        $this->message = $e->getMessage();
        $this->statusCode = $e->status;
        return;
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @info TODO Document this
     */
    private function handleThrowableError(Throwable $e)
    {
        $str = $e->getTraceAsString();
        $regexForLineNumber = '/\.\w+:(\d+)/m';
        $regexForValidationTypeLineNumber = '/php\((\d+)/m';
        $regex = '/(App\\\\[\w\d\-.\\\\]+)->([\w\d\-.]+)/m';

        $this->data = [];
        $this->status = false;
        $this->statusCode = StatusCodes::INTERNAL_SERVER_ERROR;
        $this->message = $e->getMessage();

        preg_match_all($regex, $str, $matches, PREG_SET_ORDER, 0);

        if (!($e instanceof ValidationException) && !($e instanceof ExceptionHelper)) {

            preg_match_all($regexForLineNumber, $e, $lineNumberMatches, PREG_SET_ORDER, 0);
            if (count($lineNumberMatches) && isset($lineNumberMatches[0][1]))
                $this->lineNumber = $lineNumberMatches[0][1];
        } else if ($e instanceof ValidationException) {

            preg_match_all($regexForValidationTypeLineNumber, $e, $lineNumberMatches, PREG_SET_ORDER, 0);
            if (count($lineNumberMatches) > 2 && isset($lineNumberMatches[1][1]))
                $this->lineNumber = $lineNumberMatches[1][1];
        } else if ($e instanceof ExceptionHelper) {

            preg_match_all($regexForValidationTypeLineNumber, $e, $lineNumberMatches, PREG_SET_ORDER, 0);
            if (count($lineNumberMatches) && isset($lineNumberMatches[0][1]))
                $this->lineNumber = $lineNumberMatches[0][1];
        }

        if (!isset($matches[0])) {
            $this->errorSnapshot = $str;
            return;
        }

        if (!count($matches[0])) {
            $this->errorSnapshot = $str;
            return;
        }

        if (count($matches[0]) > 2) {
            $this->functionName = $matches[0][2];
            $this->fileName = $matches[0][1];
        } else if (count($matches[0]) > 1) {
            $this->fileName = $matches[0][0];
        } else
            $this->errorSnapshot = $e;
        return;
    }
}
