<?php

namespace App\Services;

use App\Constants\StatusCodes;
use App\Helpers\ExceptionHelper;
use App\Helpers\RequestValidator;
use App\Helpers\ResponseGenerator;
use App\Models\ACategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

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

        return ResponseGenerator::generateResponseWithStatusCode(
            ResponseGenerator::generateSuccessResponse([
                "data" => [
                    "categories" => $categories
                ],
            ])
        );
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function searchCategoryList(Request $req)
    {

        $sqlProduct = ACategory::select("*")
            ->where([
                "status" => 1,
                "industries_id" => 1
            ]);

        $count = $sqlProduct->count();

        if ($count > 0) {

            $sqlProduct = $sqlProduct
                ->get()
                ->toArray();

            $categorylist = array();

            foreach ($sqlProduct as $sqlProductData) {
                $categorylist[] = array("categoryName" => $sqlProductData['name']);
            }

            return ResponseGenerator::generateResponseWithStatusCode(
                ResponseGenerator::generateSuccessResponse([
                    "data" => [
                        "categorylist" => $categorylist
                    ]
                ])
            );
        } else {
            throw ExceptionHelper::error([
                "message" => "Category List Not Found."
            ]);
        }
    }
}
