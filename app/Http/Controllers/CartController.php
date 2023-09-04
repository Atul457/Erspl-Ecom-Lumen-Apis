<?php

namespace App\Http\Controllers;

use App\Helpers\CommonHelper;
use App\Helpers\ExceptionHelper;
use App\Helpers\RequestValidator;
use App\Models\Cart;
use App\Models\Home;
use App\Models\OfferBundling;
use App\Models\OfferPriceBundling;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class CartController extends Controller
{

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function addCart(Request $req)
    {

        try {

            $data = RequestValidator::validate(
                $req->input(),
                [
                    'numeric' => ':attribute must contain only numbers',
                    'shop.exists' => "shop with provided id doesn\'t exist",
                    'product.exists' => "product with provided id doesn\'t exist",
                ],
                [
                    "shopId" => "required|numeric|exists:shop,id",
                    "productId" => "required|numeric|exists:product,id",
                    "qty" => "required|numeric",
                    "weight" => "required",
                ]
            );

            $userId    = $req->user()->id;
            $shopId    = $data['shopId'];
            $productId = $data['productId'];
            $weight    = $data['weight'];
            $qty       = $data['qty'];

            $sqlhomeData = Home::select("weight_capping")->first();

            if ($sqlhomeData)
                $sqlhomeData = $sqlhomeData->toArray();
            else
                $sqlhomeData = [];

            $sql = Cart::where([
                "user_id" => $userId,
                "shop_id" => $shopId,
                "product_id" => $productId,
            ])->count();

            if ($sql)
                throw ExceptionHelper::alreadyExists([
                    "message" => "Product Already into Cart"
                ]);

            $addedWeightData = Product::select("weight_in_gram")
                ->where([
                    "id" => $productId,
                    "status" => 1
                ])
                ->first()
                ->toArray();

            $shopWeight = 0;
            $shopWeight = $shopWeight + $addedWeightData['weight_in_gram'];

            $sqlCartShop = Cart::select("product_id", "qty")
                ->where([
                    "user_id" => $userId,
                    "shop_id" => $shopId
                ])
                ->get()
                ->toArray();

            foreach ($sqlCartShop as $cartShopData) {

                $productWeightData = Product::select("weight_in_gram")
                    ->where([
                        "id" => $cartShopData["product_id"],
                        "status" => 1
                    ])
                    ->first();

                if (!$productWeightData)
                    throw ExceptionHelper::somethingWentWrong();

                $productWeightData = $productWeightData->toArray();

                $shopWeight = $shopWeight + ($productWeightData['weight_in_gram'] * $cartShopData['qty']);
            }

            if ($shopWeight > $sqlhomeData['weight_capping'])
                throw ExceptionHelper::unprocessable([
                    "message" => "You can't add more than " . ($sqlhomeData['weight_capping'] / 1000) . " KG from single shop",
                    "data" => [
                        "status" => 3
                    ]
                ]);

            $inserted = Cart::insert([
                "user_id" => $userId,
                "shop_id" => $shopId,
                "product_id" => $productId,
                "weight" => $weight,
                "qty" => $qty,
                "offer_type" => 0
            ]);

            if (!$inserted)
                throw ExceptionHelper::somethingWentWrong([
                    "data" => [
                        "status" => 2
                    ]
                ]);

            $productData = Product::select("unique_code")
                ->where([
                    "id" => $productId
                ])
                ->first();

            if ($productData)
                $productData = $productData->toArray();
            else
                $productData = [];

            $sqlOfferBundle = OfferBundling::select("offer_unique_id")
                ->where([
                    "primary_unique_id" => $productData['unique_code'],
                    "status" => 1
                ]);

            if (!$sqlOfferBundle->count())
                return response([
                    "data" => null,
                    "status" =>  true,
                    "statusCode" => 200,
                    "messsage" => "Item added to cart"
                ], 200);

            $offerBundleData = $sqlOfferBundle
                ->get()
                ->toArray();

            if (!count($offerBundleData))
                throw ExceptionHelper::somethingWentWrong();
            else
                $offerBundleData = $offerBundleData[0];

            $sqlShopProduct = Product::select("id", "weight", "price", "sellingprice", "unit_id")
                ->where([
                    "shop_id" => $shopId,
                    "unique_code" => $offerBundleData['offer_unique_id'],
                    "status" => 1
                ]);

            if (!$sqlShopProduct->count())
                throw ExceptionHelper::somethingWentWrong();

            $shopProductData = $sqlShopProduct
                ->first()
                ->toArray();

            // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
            /**
             * Remove code since not being in use
             */
            // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

            // if ($shopProductData['price'] <= 0)
            //     $realPrice = $shopProductData['sellingprice'];
            // else
            //     $realPrice = $shopProductData['price'];

            $sqlCartCheck = Cart::where([
                "product_id" => $shopProductData['id'],
                "shop_id" => $shopId,
                "user_id" => $userId
            ])->count();

            if (!$sqlCartCheck) {
                $sqlInsert = Cart::insert([
                    "product_id" => $shopProductData['id'],
                    "shop_id" => $shopId,
                    "user_id" => $userId,
                    "qty" => 1,
                    "weight" => $shopProductData['weight'] . " " . CommonHelper::uomName($shopProductData['unit_id']),
                    "offer_type" => 1
                ]);

                if ($sqlInsert) {

                    $sqlOfferCart =  Cart::where([
                        "user_id" => $userId,
                        "offer_type" => 2
                    ]);

                    if ($sqlOfferCart->count()) {
                        $offerCartData = $sqlOfferCart->first();
                        Cart::find($offerCartData['id'])->deleted();
                    }
                }
            } else {

                Cart::where([
                    "product_id" => $shopProductData['id'],
                    "shop_id" => $shopId,
                    "user_id" => $userId,
                ])
                    ->update([
                        "offer_type" => 1
                    ]);

                $sqlOfferCart = Cart::where([
                    "user_id" => $userId,
                    "offer_type" => 2
                ]);

                if ($sqlOfferCart->count()) {

                    $offerCartData = $sqlOfferCart
                        ->first()
                        ->toArray();

                    Cart::find($offerCartData["id"])
                        ->delete();
                }
            }

            return response([
                "sqlhomeData" => $sqlhomeData
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
}
