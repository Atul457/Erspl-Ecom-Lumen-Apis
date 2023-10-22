<?php

namespace App\Services;

use App\Constants\StatusCodes;
use App\Helpers\ExceptionHelper;
use App\Helpers\RequestValidator;
use App\Helpers\ResponseGenerator;
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
            return ResponseGenerator::generateResponseWithStatusCode(
                ResponseGenerator::generateSuccessResponse([
                    "data" => [
                        "refundList" => $refundList
                    ],
                ])
            );
        } else {
            throw ExceptionHelper::error([
                "message" => "tbl_refund row not found where order_id: $orderId"
            ]);
        }
    }
}
