<?php

namespace App\Services;

use App\Constants\StatusCodes;

// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @todo Document this
 */
class PaytmService
{

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function paytmConfig()
    {
        $config = array(
            'mid' => env("PAYTM_MID_CONFIG"),
            'mkey' => env("PAYTM_MKEY_CONFIG"),
            'env' => 'test',
            'merchant_website' => 'DEFAULT',
            'channel' => 'WEB',
            'industry_type' => 'Retail'
        );

        return [
            "response" => [
                "status" => true,
                "statusCode" => StatusCodes::OK,
                "data" => $config,
                "message" => null,
            ],
            "statusCode" => StatusCodes::OK
        ];
    }
}
