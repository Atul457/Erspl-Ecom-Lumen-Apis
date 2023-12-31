<?php

namespace App\Services;

use App\Constants\StatusCodes;
use App\Helpers\CommonHelper;
use App\Helpers\ExceptionHelper;
use App\Helpers\RequestValidator;
use App\Helpers\ResponseGenerator;
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

        return ResponseGenerator::generateResponseWithStatusCode(
            ResponseGenerator::generateSuccessResponse([
                "data" => $data_,
                "status" => $status,
                "message" => $message,
            ])
        );
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

        $baseQuery = Wallet::select("tbl_wallet.amount", "tbl_wallet.remark", "tbl_wallet.order_id", "tbl_wallet.referral_by",  DB::raw("CONCAT_WS(' ', NULLIF(TRIM(tbl_registration.first_name), ''), NULLIF(TRIM(tbl_registration.middle_name), ''), NULLIF(TRIM(tbl_registration.last_name), '')) as invitedTo"), DB::raw("DATE_FORMAT(tbl_wallet.date, '%d-%M-%Y') as date"))
            ->where("tbl_wallet.customer_id", $userId)
            ->where("tbl_wallet.remark", 'LIKE', '%Referral Bonus%')
            ->orderBy("tbl_wallet.date", "desc")
            ->join("tbl_registration", "tbl_wallet.referral_code", "tbl_registration.referral_code");

        $referralList = $baseQuery
            ->get()
            ->toArray();

        $resultCount = count($referralList);

        if ($resultCount === 0)
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::NOT_FOUND,
                "message" => "list not found."
            ]);

        return ResponseGenerator::generateResponseWithStatusCode(
            ResponseGenerator::generateSuccessResponse([
                "data" => [
                    "referralList" => $referralList
                ],
            ])
        );
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
            throw ExceptionHelper::error([
                "message" => "Something went wrong. Try Again"
            ]);

        return ResponseGenerator::generateResponseWithStatusCode(
            ResponseGenerator::generateSuccessResponse([
                "data" => [
                    "orderId" => round($invoiceId)
                ],
                "message" => "Request Received"
            ])
        );
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

            return ResponseGenerator::generateResponseWithStatusCode(
                ResponseGenerator::generateSuccessResponse([
                    "data" => [
                        "walletList" => $walletList,
                        "walletBalance" => round($balance)
                    ]
                ])
            );
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

                return ResponseGenerator::generateResponseWithStatusCode(
                    ResponseGenerator::generateSuccessResponse([
                        "message" => $msg,
                    ])
                );
            } else {
                throw ExceptionHelper::error([
                    "message" => "Unable to update tbl_wallet row where invoid_id: $orderId"
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
