<?php

namespace App\Services;

use App\Constants\StatusCodes;
use App\Helpers\ExceptionHelper;
use App\Helpers\RequestValidator;
use App\Models\Refund;
use Laravel\Lumen\Http\Request;

// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @todo Document this
 */
class RefundService
{


    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function refundDetails(Request $req)
    {

        $data = RequestValidator::validate(
            $req->input(),
            [
                'numeric' => ':attribute must contain only numbers',
            ],
            [
                "orderId" => "required|numeric",
            ]
        );

        $orderId = round($data['orderId']);

        $sql = Refund::select("*")
            ->where("order_id", $orderId)
            ->get()
            ->toArray();

        $count = 0;

        foreach ($sql as $sqlData) {

            if ($sqlData['status'] == 1) {
                $date = $sqlData['refund_date'];
            } else {
                $date = "";
            }

            $refundList[]   = array(
                "id" => $sqlData['id'],
                "amount" => $sqlData['refund_amount'],
                "remark" => $sqlData['reason'],
                "date" => $date,
                "refundStatus" => $sqlData['status']
            );

            $count++;
        }

        if ($count > 0) {
            return [
                "response" => [
                    "status" => true,
                    "statusCode" => StatusCodes::OK,
                    "data" => [
                        "refundList" => $refundList
                    ],
                    "message" => null,
                ],
                "statusCode" => StatusCodes::OK
            ];
        } else {
            throw ExceptionHelper::error();
        }
    }
}
