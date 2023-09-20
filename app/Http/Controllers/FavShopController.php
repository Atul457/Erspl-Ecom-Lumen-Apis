<?php

namespace App\Http\Controllers;

use App\Constants\StatusCodes;
use App\Helpers\ExceptionHelper;
use App\Helpers\RequestValidator;
use App\Models\FavShop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class FavShopController extends Controller
{
    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function addFavShop(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [
                'exists' => 'shop with provided id doesn\'t exist'
            ],
            [
                "shopId" => "required|exists:shop,id",
            ]
        );

        $userId = $req->user()->id;
        $shopId = $data['shopId'];
        $currentTime = date('Y-m-d H:i:s');

        $alreadyAdded = FavShop::select("*")
            ->where([
                "shop_id" => $shopId,
                "user_id" => $userId
            ])
            ->count() > 1;

        if ($alreadyAdded)
            throw ExceptionHelper::unAuthorized([
                "message" => "Already added to favourite."
            ]);

        $inserted = FavShop::create([
            "shop_id" => $shopId,
            "user_id" => $userId,
            "datetime" => $currentTime
        ]);

        if (!$inserted)
            throw ExceptionHelper::somethingWentWrong([
                "message" => "Already added to favourite."
            ]);

        return response([
            "data" => null,
            "status" =>  true,
            "statusCode" => StatusCodes::OK,
            "messsage" => "Added To favourite Shops."
        ], StatusCodes::OK);
    }


    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function removeFav(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [
                'exists' => 'shop with provided id doesn\'t exist'
            ],
            [
                "shopId" => "required|exists:shop,id",
            ]
        );

        $userId = $req->user()->id;
        $shopId = $data['shopId'];

        $deleted = FavShop::where([
            "shop_id" => $shopId,
            "user_id" => $userId
        ])
            ->delete();

        if (!$deleted)
            throw ExceptionHelper::somethingWentWrong();

        return response([
            "data" => null,
            "status" =>  true,
            "statusCode" => StatusCodes::OK,
            "messsage" => "Removed From favourite."
        ], StatusCodes::OK);
    }
}
