<?php

namespace App\Services;

use App\Constants\StatusCodes;
use App\Helpers\CommonHelper;
use App\Helpers\ExceptionHelper;
use App\Helpers\RequestValidator;
use App\Models\Registration;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @todo Document this
 */
class WalletService
{
    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function checkWalletBalance(Request $req)
    {
        $data_ = null;
        $status = false;
        $message = "Success";
        $userId = $req->user()->id;

        $data = RequestValidator::validate(
            $req->input(),
            [
                'numeric' => ':attribute must be be a number',
            ],
            [
                "orderTotal" => "numeric|required"
            ]
        );

        $result = DB::select(
            'CALL check_balance(?, ?)',
            [$userId, $data["orderTotal"]]
        );
        $result = $result[0];

        if ($result->is_insufficient_balance) {
            $message = "Insuficient balance";
            $data_ = [];
            $data_["requiredBalance"] = floatval($result->required_balance);
        } else
            $status = true;

        return [
            "response" => [
                "data" => $data_,
                "status" => $status,
                "statusCode" => StatusCodes::OK,
                "message" => $message,
            ],
            "statusCode" => StatusCodes::OK
        ];
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function referralList(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [
                'numeric' => ':attribute must be be a number',
            ],
            [
                "userId" => "numeric|required"
            ]
        );

        $userId = $data["userId"];

        $baseQuery = Wallet::select("wallet.amount", "wallet.remark", "wallet.order_id", "wallet.referral_by",  DB::raw("CONCAT_WS(' ', NULLIF(TRIM(tbl_registration.first_name), ''), NULLIF(TRIM(tbl_registration.middle_name), ''), NULLIF(TRIM(tbl_registration.last_name), '')) as invitedTo"), DB::raw("DATE_FORMAT(wallet.date, '%d-%M-%Y') as date"))
            ->where("wallet.customer_id", $userId)
            ->where("wallet.remark", 'LIKE', '%Referral Bonus%')
            ->orderBy("wallet.date", "desc")
            ->join("tbl_registration", "wallet.referral_code", "tbl_registration.referral_code");

        $referralList = $baseQuery
            ->get()
            ->toArray();

        $resultCount = count($referralList);

        if ($resultCount === 0)
            throw ExceptionHelper::notFound([
                "message" => "list not found."
            ]);

        return [
            "response" => [
                "data" => [
                    "referralList" => $referralList
                ],
                "status" =>  true,
                "statusCode" => StatusCodes::OK,
                "messsage" => null
            ],
            "statusCode" => StatusCodes::OK
        ];
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function rechargeWallet(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [
                'numeric' => ':attribute must be be a number'
            ],
            [
                "amount" => "numeric|required"
            ]
        );

        $userId = $req->user()->id;
        $amount = $data['amount'];
        $date   = date('Y-m-d H:i:s');
        $invoiceId = time() . $userId;

        $inserted = Wallet::insert([
            "date" => $date,
            "amount" => $amount,
            "customer_id" => $userId,
            "invoice_id" => $invoiceId,
            "remark" => "Wallet Recharge",
            "payment_status" => 1,
        ]);

        if (!$inserted)
            throw ExceptionHelper::somethingWentWrong([
                "message" => "Something went wrong. Try Again"
            ]);

        return [
            "response" => [
                "data" => [
                    "orderId" => round($invoiceId)
                ],
                "status" =>  true,
                "statusCode" => StatusCodes::OK,
                "messsage" => "Request Received"
            ],
            "statusCode" => StatusCodes::OK
        ];
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function walletHistory(Request $req)
    {
        $userId = $req->user()->id;
        $sqlWallet = Wallet::select("*")
            ->where("customer_id", $userId)
            ->orderBy("date", "desc");

        $count = $sqlWallet
            ->count();

        if ($count > 0) {

            $balance = 0;

            $sqlReg = Registration::select("wallet_balance")
                ->where("id", $userId);

            $regData = $sqlReg
                ->first()
                ->toArray();

            if (!empty($regData['wallet_balance'])) {
                $balance = $regData['wallet_balance'];
            }

            $sqlWallet = $sqlWallet
                ->get()
                ->toArray();

            foreach ($sqlWallet as $walletData) {

                $walletList[] = array(
                    'Id' => $walletData['id'],
                    "date" => $walletData['date'],
                    "amount" => $walletData['amount'],
                    "remark" => $walletData['remark'],
                    "paymentStatus" => $walletData['payment_status'],
                    "status" => $walletData['status']
                );
            }

            return [
                "response" => [
                    "status" => true,
                    "statusCode" => StatusCodes::OK,
                    "data" => [
                        "walletList" => $walletList,
                        "walletBalance" => round($balance)
                    ],
                    "message" => null,
                ],
                "statusCode" => StatusCodes::OK
            ];
        } else {
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::NOT_FOUND,
                "message" => "No Transaction Found"
            ]);
        }
    }




    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function walletPaymentTest(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [
                'numeric' => ':attribute must be be a number'
            ],
            [
                "orderId" => "numeric"
            ]
        );

        $userId      = $req->user()->id;
        $orderId     = $data['orderId'] ?? "";
        $txnId       = CommonHelper::generateTXN();
        $txnDate     = date('Y-m-d H:i:s');
        $txnStatus   = $data['txnStatus'] ?? "";
        $paymentMode = "Paytm";

        $sqlWallet = Wallet::select("amount", "status")
            ->where("invoice_id", $orderId);

        $walletData = $sqlWallet
            ->first()
            ?->toArray();

        if (($walletData['status'] ?? "") == 0) {

            if ($txnStatus == 'TXN_SUCCESS') {
                $paymentStatus = 1;
                $msg = 'Recharged Successfully';
            } else {
                $paymentStatus = 2;
                $msg = 'Payment Failed';
            }

            $sqlUpdate = Wallet::where("invoice_id", $orderId)
                ->update([
                    "status" => $paymentStatus,
                    "txn_id" => $txnId,
                    "txn_date" => $txnDate,
                    "payment_mode" => $paymentMode
                ]);

            if ($sqlUpdate) {

                if ($paymentStatus == 1) {

                    $sqlReg = Registration::select("wallet_balance")
                        ->where("id", $userId);

                    $regData = $sqlReg
                        ->first()
                        ->toArray();

                    $updateBalance = $regData['wallet_balance'] + $walletData['amount'];

                    Registration::where("id", $userId)
                        ->update([
                            "wallet_balance" => $updateBalance
                        ]);
                }

                return [
                    "response" => [
                        "status" => true,
                        "statusCode" => StatusCodes::OK,
                        "data" => [],
                        "message" => $msg,
                    ],
                    "statusCode" => StatusCodes::OK
                ];
            } else {
                throw ExceptionHelper::error([
                    "message" => "Unable to update wallet row where invoid_id: $orderId"
                ]);
            }
        } else {
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::RESOURCE_ALREADY_EXISTS,
                "message" => "Already Done"
            ]);
        }
    }
}
