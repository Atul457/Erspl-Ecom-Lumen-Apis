<?php

namespace App\Helpers;

class CurlRequestHelper
{
    /**
     * Send an HTTP request using the specified method.
     *
     * @param array $request An associative array containing request details:
     * - 'method' (string): The HTTP request method (e.g., 'GET' or 'POST').
     * - 'url' (string): The URL to send the request to.
     * - 'headers' (array): An array of custom headers to include in the request.
     * - 'data' (array): An associative array of data to send in the request body (for POST requests).
     * - 'additionalSetOptArray' (array): An associative array of curl_setopt_array to add.
     *
     * @return array An array containing the response or error details.
     */
    public static function sendRequest(array $request)
    {
        $method = $request['method'] ?? "Get";
        $url = $request['url'];
        $headers = $request['headers'] ?? [];
        $data = $request['data'] ?? [];
        $additionalSetOptArray = $request['additionalSetOptArray'] ?? [];

        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => $url,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
            )
                +
                $additionalSetOptArray
        );

        if (!empty($headers)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        if ($method === 'POST' && !empty($data)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if (!empty($err))
            ErrorHandlerHelper::logError([
                "data" => $err,
                "fileName" => "CurlRequestHelper",
                "functionName" => "sendRequest",
                "payload" => $request,
            ]);

        // Process the response or handle errors
        return [
            "error" => $err,
            "response" => $response,
        ];
    }
}
