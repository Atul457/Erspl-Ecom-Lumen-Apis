<?php

namespace App\Http\Controllers;

use App\Helpers\ExceptionHelper;
use App\Helpers\RequestValidator;
use App\Models\Cart;
use App\Models\OfferPriceBundling;
use App\Models\Product;
use App\Models\SubCategory;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use UConverter;

class ProductController extends Controller
{

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function subCategoryList(Request $req)
    {
        $ACTIVE = 1;
        $urlToPrepend = url("subcategorys");
        $subCategoryList = [];

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
            throw ExceptionHelper::notFound([
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
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function searchShopProduct(Request $req)
    {
        $productlist = array();

        $data = RequestValidator::validate(
            $req->input(),
            [
                'numeric' => ':attribute must contain only numbers',
            ],
            [
                "userId" => "required|numeric",
                "shopId" => "required|numeric",
            ]
        );

        $userId = $data["userId"] ?? "";
        $shopId = $data["shopId"] ?? "";
        $search = $req->input("search") ?? "";
        $categoryId = $req->input("categoryId") ?? "";

        if (empty($search))
            throw ExceptionHelper::notFound([
                "message" => "Product Not Found."
            ]);

        $baseQuery = Product::select("product.*", "shop.name as shop_name")
            ->where([
                "product.status" => 1,
                "shop_id" => $shopId,
            ])
            ->where(function ($query) use ($search) {
                $query->where("keywords", "LIKE", "%$search %")
                    ->orWhere("keywords", "LIKE", "% $search%")
                    ->orWhere("keywords", "LIKE", "% $search,%")
                    ->orWhere("keywords", "LIKE", "%,$search%");
            })
            ->join("shop", "shop.id", "product.shop_id");

        if (!empty($categoryId))
            $baseQuery = $baseQuery
                ->where("category_id", $categoryId);

        $sqlProduct = $baseQuery
            ->get()
            ->toArray();

        if (!count($sqlProduct))
            throw ExceptionHelper::notFound([
                "message" => "Product Not Found."
            ]);

        foreach ($sqlProduct as $sqlProductData) {

            $added = 0;
            $price = 0;
            $productImage = "";

            $directory = '../products/' . $sqlProductData['barcode'] . '/';
            $partialName = '1.';
            $files = glob($directory . '*' . $partialName . '*');

            if ($files !== false)
                foreach ($files as $file) {
                    $productImage = basename($file);
                }

            $unitList = [];
            $sqlCartItemCount = Cart::select("*")
                ->where([
                    "shop_id" => $shopId,
                    "user_id" => $userId,
                    "product_id" => $sqlProductData["id"]
                ])
                ->count();

            if ($sqlCartItemCount)
                $added = 1;

            if ($sqlProductData['price'] == 0)
                $price = $sqlProductData['sellingprice'];
            else
                $price = $sqlProductData['price'];


            $f  = $sqlProductData['sellingprice'];
            $g  = $price;
            $h  = $f - $g;
            $i  = $f / 100;
            $j1 = $h / $i;
            $roundOffDiscount1 = explode('.', number_format($j1, 2));
            $j = $roundOffDiscount1[0];
            $discount = "";

            if ($j > 0)
                $discount = round($j) . "% OFF";

            $sqlPriceBundleCount = OfferPriceBundling::select("*")
                ->where([
                    "offer_unique_id" => $sqlProductData['unique_code'],
                    "status" => 1
                ])
                ->count();

            if ($sqlPriceBundleCount)
                $priceOffer = "1";
            else
                $priceOffer = "0";

            array_push($unitList, array(
                'variantId' => $sqlProductData['id'],
                'weight' => $sqlProductData['weight'],
                // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
                // Here uonName function is unknown -- [SKIPPING]
                // 'weight' => $sqlProductData['weight'] . " " . uomName($sqlProductData['unit_id']), 
                // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
                "price" => round($price),
                "sellingPrice" => round($sqlProductData['sellingprice']),
                "stock" => "", "discount" => $discount
            ));

            $productlist[] = array(
                "added" => $added,
                "productId" => $sqlProductData['id'],
                "shopId" => $sqlProductData['shop_id'],
                "shopName" => $sqlProductData['shop_name'],
                "productName" => ucwords(
                    ucfirst(
                        strtolower(
                            mb_convert_encoding($sqlProductData["name"], 'UTF-8')
                        )
                    )
                ),
                "small_description" => $sqlProductData['small_description'],
                "productImage" => url("products") . "/" . $sqlProductData['barcode'] . '/' . $productImage,
                "priceOffer" => $priceOffer,
                "unit" => $unitList
            );
        }

        return response([
            "data" => [
                "productlist" => $productlist
            ],
            "status" =>  true,
            "statusCode" => 200,
            "messsage" => null
        ], 200);
    }
}
