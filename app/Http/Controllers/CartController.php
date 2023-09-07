<?php

namespace App\Http\Controllers;

use App\Helpers\CommonHelper;
use App\Helpers\ExceptionHelper;
use App\Helpers\RequestValidator;
use App\Models\Cart;
use App\Models\Home;
use App\Models\OfferBundling;
use App\Models\Order;
use App\Models\OrderEdited;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
                    'shopId.exists' => "shop with provided id doesn\'t exist",
                    'productId.exists' => "product with provided id doesn\'t exist",
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

            throw ExceptionHelper::somethingWentWrong();
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



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function removeCart(Request $req)
    {
        try {
            Cart::where([
                "user_id" => $req->user()->id
            ])->delete();

            return response([
                "data" => null,
                "status" => true,
                "statusCode" => 200,
                "message" => "Cart Cleared.",
            ], 200);
        } catch (ExceptionHelper $e) {
            return response([
                "data" => $e->data,
                "status" => $e->status,
                "statusCode" => $e->statusCode,
                "message" => $e->getMessage(),
            ], $e->statusCode);
        }
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function repeatCart(Request $req)
    {

        try {

            $data = RequestValidator::validate(
                $req->input(),
                [
                    'numeric' => ':attribute must contain only numbers',
                    'orderId.exists' => "order with provided id doesn\'t exist",
                ],
                [
                    "orderId" => "required|numeric|exists:order,id",
                ]
            );

            $orderId = $data['orderId'];
            $userId  = $req->user()->id;

            $sqlCheck = Order::select("*")
                ->where("order_id", $orderId);

            $checkData = $sqlCheck
                ->first()
                ->toArray();

            if ($checkData['edit_status'] == 1) {

                $sql = OrderEdited::where("order_id", $orderId)
                    ->where("qty", ">", 0);

                if (!$sql->count())
                    throw ExceptionHelper::notFound([
                        "message" => "Order Not Found.",
                        "data" => [
                            "status" => 2
                        ]
                    ]);

                $sqlDelete = Cart::where("user_id", $userId);

                if ($sqlDelete->count()) {

                    $data = $sqlDelete->first();
                    return response([
                        "data" => [
                            "status" => 4
                        ],
                        "status" => true,
                        "statusCode" => 200,
                        "message" => "Your cart contains products. Do you want to replace?",
                    ], 200);
                } else {

                    $sql = $sql->get()->toArray();

                    foreach ($sql as $data) {

                        $sqlInsert = Cart::insert([
                            "user_id" => $data["customer_id"],
                            "shop_id" => $data["shop_id"],
                            "product_id" => $data["product_id"],
                            "weight" => $data["weight"],
                            "qty" => $data["qty"],
                        ]);

                        if ($sqlInsert)
                            return response([
                                "data" => [
                                    "status" => 1
                                ],
                                "status" => true,
                                "statusCode" => 200,
                                "message" => "Item add to cart.",
                            ], 200);
                        else
                            throw ExceptionHelper::somethingWentWrong([
                                "message" => "Someting went Wrong. Try Again.",
                                "data" => [
                                    "status" => 2
                                ]
                            ]);
                    }
                }
            } else {

                $sql = $sqlCheck
                    ->first()
                    ->toArray();

                if (!count($sql))
                    throw ExceptionHelper::notFound([
                        "message" => "Order Not Found.",
                        "data" => [
                            "status" => 2
                        ]
                    ]);

                $sqlDelete = Cart::where("user_id", $userId);

                if ($sqlDelete->count()) {

                    $data = $sqlDelete->first();
                    return response([
                        "data" => [
                            "status" => 4
                        ],
                        "status" => true,
                        "statusCode" => 200,
                        "message" => "Your cart contains products. Do you want to replace?",
                    ], 200);
                } else {

                    foreach ($checkData as $data) {

                        $sqlInsert = Cart::insert([
                            "user_id" => $data["customer_id"],
                            "shop_id" => $data["shop_id"],
                            "product_id" => $data["product_id"],
                            "weight" => $data["weight"],
                            "qty" => $data["qty"],
                        ]);

                        if ($sqlInsert)
                            return response([
                                "data" => [
                                    "status" => 1
                                ],
                                "status" => true,
                                "statusCode" => 200,
                                "message" => "Item add to cart.",
                            ], 200);
                        else
                            throw ExceptionHelper::somethingWentWrong([
                                "message" => "Someting went Wrong. Try Again.",
                                "data" => [
                                    "status" => 2
                                ]
                            ]);
                    }
                }
            }

            return response([
                "data" => null,
                "status" => true,
                "statusCode" => 200,
                "message" => null,
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



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function updateCart(Request $req)
    {

        try {

            $data = RequestValidator::validate(
                $req->input(),
                [
                    'numeric' => ':attribute must contain only numbers',
                    'shopId.exists' => "shop with provided id doesn\'t exist",
                    'productId.exists' => "product with provided id doesn\'t exist",
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

            $sql = Cart::where([
                "user_id" => $userId,
                "shop_id" => $shopId,
                "product_id" => $productId,
            ]);

            $sqlhome = Home::select("weight_capping")->first();
            $sqlhomeData = $sqlhome->toArray();

            if ($sql->count() === 0 &&  $qty > 0) {

                $sql = Cart::insert([
                    "user_id" => $userId,
                    "shop_id" => $shopId,
                    "product_id" => $productId,
                    "weight" => $weight,
                    "qty" => $qty,
                ]);

                if ($sql)
                    return response([
                        "data" => [
                            "status" => 1
                        ],
                        "status" => true,
                        "statusCode" => 200,
                        "message" => "Item added to cart",
                    ], 200);
                else
                    throw ExceptionHelper::somethingWentWrong();
            } else {

                $sql = Cart::where([
                    "user_id" => $userId,
                    "shop_id" => $shopId,
                    "product_id" => $productId,
                ]);

                if ($qty > 0) {

                    $shopWeight = 0;

                    $sqlAddedWeight = Product::select("weight_in_gram")
                        ->where([
                            "id" => $productId,
                            "status" => 1
                        ]);

                    $addedWeightData = $sqlAddedWeight
                        ->first();

                    if ($addedWeightData)
                        $addedWeightData = $addedWeightData->toArray();
                    else
                        throw ExceptionHelper::somethingWentWrong();

                    $shopWeight = $shopWeight + ($qty * $addedWeightData['weight_in_gram']);
                    $sqlCartShop = Cart::where([
                        "user_id" => $userId,
                        "shop_id" => $shopId,
                    ])->where("product_id", "!=", $productId)
                        ->get()
                        ->toArray();

                    foreach ($sqlCartShop as $cartShopData) {

                        $sqlProductWeight = Product::where([
                            "id" => $cartShopData['product_id'],
                            "status" => 1
                        ]);

                        $productWeightData =  $sqlProductWeight->first();

                        if ($productWeightData)
                            $productWeightData = $productWeightData->toArray();
                        else
                            throw ExceptionHelper::somethingWentWrong();

                        $shopWeight = $shopWeight + ($productWeightData['weight_in_gram'] * $cartShopData['qty']);

                        if ($shopWeight > $sqlhomeData['weight_capping'])
                            return response([
                                "data" => [
                                    "status" => 2
                                ],
                                "status" => true,
                                "statusCode" => 200,
                                "message" => "You can't add more than " . ($sqlhomeData['weight_capping'] / 1000) . " KG from single shop",
                            ], 200);
                        else {

                            $sql = Cart::where([
                                "user_id" => $userId,
                                "shop_id" => $shopId,
                                "product_id" => $productId,
                            ])
                                ->update([
                                    "qty" => $qty
                                ]);

                            if ($sql) {

                                $sqlOfferProduct = Product::select("unique_code", "weight", "price", "sellingprice")
                                    ->where([
                                        "id" => $productId
                                    ]);

                                $offerProductData = $sqlOfferProduct->first();

                                if (!$offerProductData)
                                    throw ExceptionHelper::somethingWentWrong();
                                else
                                    $offerProductData = $offerProductData->toArray();

                                $sqlOfferBundle = OfferBundling::where([
                                    "primary_unique_id" => $offerProductData['unique_code'],
                                    "status" => 1
                                ]);

                                if ($sqlOfferBundle->count()) {

                                    $offerBundleData = $sqlOfferBundle
                                        ->first()
                                        ->toArray();

                                    $sqlShopProduct = Product::select("id", "weight", "price", "sellingprice")
                                        ->where([
                                            "shop_id" => $shopId,
                                            "unique_code" => $offerBundleData['offer_unique_id'],
                                            "status" => 1
                                        ]);

                                    if ($sqlShopProduct->count()) {

                                        $shopProductData = $sqlShopProduct
                                            ->first()
                                            ->toArray();

                                        if ($shopProductData['price'] == 0)
                                            $realPrice = $shopProductData['sellingprice'];
                                        else
                                            $realPrice = $shopProductData['price'];

                                        $offerDiscount = 0;
                                        $offerDiscount = $offerDiscount + ($realPrice - $offerBundleData['offer_amount']);

                                        $sqlCartCheck = Cart::select("id", "qty")
                                            ->where([
                                                "product_id" => $shopProductData['id'],
                                                "shop_id" => $shopId,
                                                "user_id" => $userId
                                            ]);

                                        if ($sqlCartCheck->count())
                                            Cart::where([
                                                "product_id" => $shopProductData['id'],
                                                "shop_id" => $shopId,
                                                "user_id" => $userId
                                            ])->udpate([
                                                "qty" => $qty
                                            ]);
                                        else
                                            Cart::insert([
                                                "product_id" => $shopProductData['id'],
                                                "shop_id" => $shopId,
                                                "user_id" => $userId,
                                                "weight" => $shopProductData['weight'] . CommonHelper::uomName($shopProductData['unit_id']),
                                                "qty" => $qty
                                            ]);
                                    }
                                }

                                $sql = Cart::select("*")
                                    ->where("user_id", $userId)
                                    ->get()
                                    ->toArray();

                                $subTotal = 0;
                                $cartTotal = 0;

                                foreach ($sql as $sqlData) {

                                    $sqlProduct = Product::select("id", "name", "price", "sellingprice", "image")
                                        ->where([
                                            "id" => $sqlData['product_id'],
                                            "status" => 1
                                        ]);

                                    $count = $sqlProduct->count();

                                    if ($count > 0) {

                                        $productlist = array();
                                        $sqlProduct = $sqlProduct
                                            ->get()
                                            ->toArray();

                                        foreach ($sqlProduct as $sqlProductData) {

                                            $productImage = explode("$", $sqlProductData['image']);

                                            if ($sqlProductData['price'] == 0)
                                                $price = $sqlProductData['sellingprice'];
                                            else
                                                $price = $sqlProductData['price'];

                                            $cartList[] = array(
                                                "shopId" => $sqlData['shop_id'],
                                                "productId" => $sqlProductData['id'],
                                                "productName" => $sqlProductData['name'],
                                                "price" => $price,
                                                "sellingprice" => $sqlProductData['sellingprice'],
                                                "productImage" => url("/products") . "/" . $productImage[0] ?? "",
                                                "qty" => $sqlData['qty'],
                                                "total" => $price * $sqlData['qty']
                                            );

                                            $cartTotal      = $cartTotal + ($price * $sqlData['qty']);

                                            if ($sqlData['offer_type'] == 0)
                                                $subTotal = $subTotal + ($price * $sqlData['qty']);
                                        }
                                    }
                                }

                                return response([
                                    "data" => [
                                        "status" => 3,
                                        "cartSubtotal" => $cartTotal,
                                        "subTotal" => $subTotal,
                                        "subTotal" => $subTotal,
                                    ],
                                    "status" => true,
                                    "statusCode" => 200,
                                    "message" => "CART UPDATED",
                                ], 200);
                            }
                        }
                    }
                } else {

                    $sql = Cart::where([
                        "user_id" => $userId,
                        "shop_id" => $shopId,
                        "product_id" => $productId,
                    ])->delete();

                    if ($sql) {

                        $sqlProduct = Product::select("unique_code", "shop_id")
                            ->where("id", $productId);

                        $productData = $sqlProduct
                            ->first();

                        if (!$productData)
                            throw ExceptionHelper::somethingWentWrong();

                        $sqlOfferBundle = OfferBundling::select("*")
                            ->where([
                                "primary_unique_id" => $productData['unique_code'],
                                "status" => 1
                            ]);

                        if ($sqlOfferBundle->count()) {

                            $offerBundleData = $sqlOfferBundle
                                ->first()
                                ->toArray();

                            $sqlShopProduct = Product::select("id", "weight", "price", "sellingprice", "unit_id")
                                ->where([
                                    "shop_id" => $productData['shop_id'],
                                    "unique_code" => $offerBundleData['offer_unique_id'],
                                    "status" => 1
                                ]);

                            if ($sqlShopProduct->count()) {

                                $shopProductData = $sqlShopProduct
                                    ->first()
                                    ->toArray();

                                $sqlCartCheck  = Cart::select("id")
                                    ->where([
                                        "product_id" => $shopProductData['id'],
                                        "shop_id" => $productData['shop_id'],
                                        "user_id" => $userId
                                    ]);

                                if ($sqlCartCheck->count())
                                    Cart::where([
                                        "product_id" => $shopProductData['id'],
                                        "shop_id" => $productData['shop_id'],
                                        "user_id" => $userId
                                    ])->delete();
                            }
                        }

                        $sql = Cart::select("*")
                            ->where("user_id", $userId)
                            ->get()
                            ->toArray();

                        $cartTotal = 0;
                        $subTotal = 0;

                        foreach ($sql as $sqlData) {

                            $sqlProduct = Product::select("id", "name", "price", "sellingprice", "image")
                                ->where([
                                    "id" => $sqlData['product_id'],
                                    "status" => 1
                                ]);

                            $count = $sqlProduct->count();

                            if ($count > 0) {

                                // $productlist = array();

                                $sqlProduct = $sqlProduct
                                    ->get()
                                    ->toArray();

                                foreach ($sqlProduct as $sqlProductData) {

                                    $productImage   = explode("$", $sqlProductData['image']);

                                    if ($sqlProductData['price'] == 0)
                                        $price = $sqlProductData['sellingprice'];
                                    else
                                        $price = $sqlProductData['price'];

                                    $cartList[] = array(
                                        "shopId" => $sqlData['shop_id'],
                                        "productId" => $sqlProductData['id'],
                                        "productName" => $sqlProductData['name'],
                                        "price" => $price,
                                        "sellingprice" => $sqlProductData['sellingprice'],
                                        "productImage" => url("products/") . "/" . $productImage[0],
                                        "qty" => $sqlData['qty'],
                                        "total" => $price * $sqlData['qty']
                                    );

                                    $cartTotal = $cartTotal + ($price * $sqlData['qty']);
                                    if ($sqlData['offer_type'] == 0)
                                        $subTotal = $subTotal + ($price * $sqlData['qty']);
                                }
                            }
                        }

                        return response([
                            "data" => [
                                "status" => 3,
                                "cartSubtotal" => $cartTotal,
                                "subTotal" => $subTotal
                            ],
                            "status" => true,
                            "statusCode" => 200,
                            "message" => "Product Removed",
                        ], 200);
                    } else
                        throw ExceptionHelper::somethingWentWrong([
                            "data" => [
                                "status" => 2
                            ]
                        ]);
                }
            }
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



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function wishToCart(Request $req)
    {

        try {

            $data = RequestValidator::validate(
                $req->input(),
                [
                    'numeric' => ':attribute must contain only numbers',
                    'shopId.exists' => "shop with provided id doesn\'t exist",
                    'productId.exists' => "product with provided id doesn\'t exist",
                ],
                [
                    "shopId" => "required|numeric|exists:shop,id",
                    "productId" => "required|numeric|exists:product,id",
                    "weight" => "required",
                ]
            );

            $userId    = $req->user()->id;
            $shopId    = $data['shopId'];
            $productId = $data['productId'];
            $weight    = $data['weight'];
            $qty       = 1;

            $sql = Cart::where([
                "user_id" => $userId,
                "shop_id" => $shopId,
                "product_id" => $productId,
            ]);

            if ($sql->count())
                throw ExceptionHelper::alreadyExists([
                    "message" => "Product Already into Cart",
                    "data" => [
                        "status" => 3
                    ]
                ]);

            $sql = Cart::insert([
                "user_id" => $userId,
                "shop_id" => $shopId,
                "product_id" => $productId,
                "weight" => $weight,
                "qty" => $qty,
            ]);

            if (!$sql)
                throw ExceptionHelper::somethingWentWrong();

            return response([
                "data" => [
                    "status" => 1
                ],
                "status" => true,
                "statusCode" => 200,
                "message" => "Item added to cart",
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
