<?php

namespace App\Helpers;

use App\Models\Home;
use Illuminate\Support\Facades\Log;

class OTPHelper
{
    /**
     * Send an OTP to the given mobile number.
     *
     * @param string $otp The OTP to send.
     * @param string $mobile The mobile number to send the OTP to.
     * @param string $name The name of the person to sent the OTP to. If you pass name as param then this function works for sending delivery code to the person
     * @return void
     */
    public static function sendOTP($otp, $mobile, $name = null)
    {
        $curlUrl = "";
        $message = "";
        $isForSendingDeliveryCode = !empty($name);

        // Fetch configuration from the database
        $home = Home::first();
        $smsVendor = $home->sms_vendor;

        // Customize message and other variables
        if ($isForSendingDeliveryCode) {
            $message = "Dear " . $name . ",\nYour eRSPL Delivery Code is " . $otp . ". Please share this with our delivery partner only AFTER receiving your order.";
        } else {
            if ($smsVendor == "ZAP") {
                $curlUrl = $home->zap_key;
                $message = "Dear Customer,\nlogin to eRSPL OTP is $otp.Please do not share with anyone.";
            } else {
                $curlUrl = $home->fortius_key;
                $message = "Dear Customer,\nOTP to login to eRSPL is " . $otp . ". Please do not share with anyone.";
            }
        }

        // Replace placeholders in the URL with actual values
        $curlUrl = str_replace("[MESSAGE]", urlencode($message), $curlUrl);
        $curlUrl = str_replace('$message', urlencode($message), $curlUrl);
        $curlUrl = str_replace("[MOBILE_NUMBER]", $mobile, $curlUrl);
        $curlUrl = str_replace('$mobile', $mobile, $curlUrl);

        return CurlRequestHelper::sendRequest([
            "url" => $curlUrl,
            "method" => "Get",
            "additionalSetOptArray" => [
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
            ]
        ]);
    }


    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public static function generateOtp()
    {
        $min = 1000;
        $max = 9999;
        $otp = rand($min, $max);

        return $otp;
    }
}
