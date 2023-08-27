<?php

namespace App\Http\Controllers;

use App\Helpers\ExceptionHelper;
use App\Helpers\RequestValidator;
use App\Models\Product;
use App\Models\SubCategory;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function subCategoryList(Request $req)
    {
        $ACTIVE = 1;
        $urlToPrepend = url("subcategorys");
        $subCategoryList = [];

        try {

            $data = RequestValidator::validate(
                $req->input(),
                [],
                [
                    "shopId" => "required|numeric",
                    "categoryId" => "required|numeric",
                ]
            );

            $baseQuery = Product::where([
                "status" => $ACTIVE,
                "shop_id" => $data["shopId"]
            ]);

            $distinctSubCategories = $baseQuery
                ->where(["category_id" => $data["categoryId"]])
                ->select("subcategory_id")
                ->distinct("product.subcategory_id")
                ->orderBy("product.subcategory_id")
                ->get()
                ->toArray();

            if (!count($distinctSubCategories))
                throw ExceptionHelper::nonFound([
                    "message" => "Sub Category List Not Found."
                ]);

            foreach ($distinctSubCategories as $dscKey => $dscValue) {

                $count = Product::where(["subcategory_id" => $dscValue])->count();
                $subCategory = SubCategory::select("id as subcategoryId", "name", DB::raw("CONCAT('$urlToPrepend/', image) as image"))
                    ->where("id", $dscValue)
                    ->get()
                    ->toArray();

                if (count($subCategory)) {
                    $subCategoryList[] = array_merge($subCategory[0], [
                        "count" => $count
                    ]);
                }

                $columns = array_column($subCategoryList, 'count');
                array_multisort($columns, SORT_DESC, $subCategoryList);
            }

            return response([
                "data" => [
                    "subCategoryList" => $subCategoryList
                ],
                "status" =>  true,
                "statusCode" => 200,
                "messsage" => null
            ], 200);
        } catch (ValidationException $e) {

            return response([
                "data" => null,
                "status" => false,
                "statusCode" => 422,
                "message" => $e->getMessage(),
            ], 422);
        } catch (ExceptionHelper $e) {
            return response([
                "data" => $e->data,
                "status" => $e->status,
                "statusCode" => $e->statusCode,
                "message" => $e->getMessage(),
            ], $e->statusCode);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
