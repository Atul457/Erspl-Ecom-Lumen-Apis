<?php

namespace App\Http\Controllers;

use App\Constants\StatusCodes;
use App\Helpers\ExceptionHelper;
use App\Helpers\RequestValidator;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class WalletController extends Controller
{
    /**
     * @return \Illuminate\Http\Response
     */
    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
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

        return response([
            "data" => $data_,
            "status" => $status,
            "statusCode" => StatusCodes::OK,
            "message" => $message,
        ], StatusCodes::OK);
    }



    /**
     * @return \Illuminate\Http\Response
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

        return response([
            "data" => [
                "referralList" => $referralList
            ],
            "status" =>  true,
            "statusCode" => StatusCodes::OK,
            "messsage" => null
        ], StatusCodes::OK);
    }



    /**
     * @param  \Illuminate\Http\Request  $req
     * @return \Illuminate\Http\Response
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

        return response([
            "data" => [
                "orderId" => round($invoiceId)
            ],
            "status" =>  true,
            "statusCode" => StatusCodes::OK,
            "messsage" => "Request Received"
        ], StatusCodes::OK);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Wallet  $wallet
     * @return \Illuminate\Http\Response
     */
    public function show(Wallet $wallet)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Wallet  $wallet
     * @return \Illuminate\Http\Response
     */
    public function edit(Wallet $wallet)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Wallet  $wallet
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Wallet $wallet)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Wallet  $wallet
     * @return \Illuminate\Http\Response
     */
    public function destroy(Wallet $wallet)
    {
        //
    }
}
