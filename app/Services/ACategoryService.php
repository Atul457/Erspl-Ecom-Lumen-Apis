<?php

namespace App\Services;

use App\Constants\StatusCodes;
use App\Helpers\RequestValidator;
use App\Models\ACategory;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Http\Request;

// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @todo Document this
 */
class ACategoryService
{

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function categoryList(Request $req)
    {
        $urlToPrepend = url('categorys/');

        $data = RequestValidator::validate(
            $req->input(),
            [],
            [
                "industriesId" => "required|numeric"
            ]
        );

        $categories = ACategory::select("name", "id as categoryId", DB::raw("CONCAT('$urlToPrepend/', icon) as image", "status as categoryStatus"))
            ->where([
                "industries_id" => $data["industriesId"],
                "status" => 1
            ])
            ->orderBy("category_order", "desc")
            ->get()
            ->toArray();

        return [
            "response" => [
                "data" => [
                    "categories" => $categories
                ],
                "status" =>  true,
                "statusCode" => StatusCodes::OK,
                "messsage" => null
            ],
            "statusCode" => StatusCodes::OK
        ];
    }
}
