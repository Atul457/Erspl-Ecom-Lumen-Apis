<?php

namespace App\Services;

use App\Constants\StatusCodes;
use App\Helpers\CommonHelper;
use App\Helpers\ExceptionHelper;
use App\Helpers\RequestValidator;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;

// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @todo Document this
 */
class WishlistService
{

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function wishlist(Request $req)
    {
        $wishCount = 0;
        $productlist = array();
        $userId = $req->user()->id;

        $wishlist = Wishlist::where([
            "customer_id" => $userId
        ])
            ->orderBy("date", "desc")
            ->get()
            ->toArray();

        if (!count($wishlist))
            throw ExceptionHelper::notFound([
                "message" => "No products in wishlist."
            ]);

        foreach ($wishlist as $sqlData) {

            $sqlProduct = Product::select("product.*", "shop.name as shop_name")
                ->where([
                    "product.status" => 1,
                    "product.id" => $sqlData["product_id"]
                ])
                ->join("shop", "shop.id", "product.shop_id")
                ->get()
                ->toArray();

            foreach ($sqlProduct as $sqlProductData) {

                $wishCount++;
                $productImage = "";
                $directory = '../products/' . $sqlProductData['barcode'] . '/';
                $partialName = '1.';
                $files = glob($directory . '*' . $partialName . '*');

                if ($files !== false)
                    foreach ($files as $file) {
                        $productImage = basename($file);
                    }

                if ($sqlProductData['price'] == 0)
                    $price = $sqlProductData['sellingprice'];
                else
                    $price = $sqlProductData['price'];

                $sqlCartCount = Cart::where([
                    "user_id" => $userId,
                    "shop_id" => $sqlProductData['shop_id'],
                    "product_id" => $sqlProductData['id'],
                ])
                    ->count();

                if ($sqlCartCount)
                    $addStatus = 0;
                else
                    $addStatus = 1;

                $productlist[]   = array(
                    "productId" => $sqlProductData['id'],
                    "shopId" => $sqlProductData['shop_id'],
                    "shopName" => $sqlProductData['shop_name'],
                    "productName" => mb_convert_encoding($sqlProductData["name"], 'UTF-8'),
                    "price" => $price,
                    "sellingprice" => $sqlProductData['sellingprice'],
                    "productImage" => url("products") . "/" . $sqlProductData['barcode'] . '/' . $productImage,
                    'weight' => $sqlProductData['weight'] . " " . CommonHelper::uomName($sqlProductData['unit_id']),
                    "weight" => $sqlProductData['weight'],
                    "addStatus" => $addStatus
                );
            }
        }

        if (!$wishCount)
            throw ExceptionHelper::notFound([
                "message" => "Wishlist Empty."
            ]);

        return [
            "response" => [
                "data" => [
                    "productlist" => $productlist
                ],
                "status" =>  true,
                "statusCode" => StatusCodes::OK,
                "messsage" => null
            ],
            "statusCode" => StatusCodes::OK
        ];
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function addWishlist(Request $req)
    {
        $userId = $req->user()->id;

        $data = RequestValidator::validate(
            $req->input(),
            [
                'numeric' => ':attribute must contain only numbers',
                'exists' => 'product with provided id doesn\'t exist'
            ],
            [
                "productId" => "required|numeric|exists:product,id",
            ]
        );

        $productId = $data['productId'];
        $date = date('Y-m-d');

        $alreadyInWishlist = Wishlist::where([
            "customer_id" => $userId,
            "product_id" => $productId,
        ])->exists();

        if ($alreadyInWishlist)
            throw ExceptionHelper::alreadyExists([
                "message" => "Already in wishlist."
            ]);

        $inserted = Wishlist::insert([
            "customer_id" => $userId,
            "product_id" => $productId,
            "date" => $date,
        ]);

        if (!$inserted)
            throw ExceptionHelper::alreadyExists([
                "message" => "Something went wrong. Try Again."
            ]);

        return [
            "response" => [
                "data" => null,
                "status" =>  true,
                "statusCode" => StatusCodes::OK,
                "messsage" => "Added To Wishlist."
            ],
            "statusCode" => StatusCodes::OK
        ];
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function removeWishlist(Request $req)
    {
        $userId = $req->user()->id;

        $data = RequestValidator::validate(
            $req->input(),
            [
                'numeric' => ':attribute must contain only numbers',
                'exists' => 'product with provided id doesn\'t exist'
            ],
            [
                "productId" => "required|numeric|exists:product,id",
            ]
        );

        $productId = $data['productId'];

        $wishlistExists = Wishlist::where([
            "customer_id" => $userId,
            "product_id" => $productId,
        ])->exists();

        if (!$wishlistExists)
            throw ExceptionHelper::notFound([
                "message" => "Not in wishlist."
            ]);

        $deleted = Wishlist::where([
            "customer_id" => $userId,
            "product_id" => $productId,
        ])->delete();

        if (!$deleted)
            throw ExceptionHelper::alreadyExists([
                "message" => "Something went wrong. Try Again."
            ]);

        return [
            "response" => [
                "data" => null,
                "status" =>  true,
                "statusCode" => StatusCodes::OK,
                "messsage" => "Removed From Wishlist."
            ],
            "statusCode" => StatusCodes::OK
        ];
    }
}
