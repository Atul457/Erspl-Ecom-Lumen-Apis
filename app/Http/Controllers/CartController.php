<?php

namespace App\Http\Controllers;

use App\Helpers\CommonHelper;
use App\Helpers\ExceptionHelper;
use App\Helpers\RequestValidator;
use App\Helpers\UtilityHelper;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Home;
use App\Models\OfferBundling;
use App\Models\OfferPriceBundling;
use App\Models\Order;
use App\Models\OrderEdited;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopTime;
use App\Models\Registration;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

            Log::error($e->getMessage());

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

            Log::error($e->getMessage());

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

            Log::error($e->getMessage());

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

            Log::error($e->getMessage());

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

            Log::error($e->getMessage());

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
    public function cartList(Request $req)
    {

        try {

            $data = RequestValidator::validate(
                $req->input(),
                [
                    'numeric' => ':attribute must contain only numbers'
                ],
                [
                    "discount" => "required|numeric",
                    "latitude" => "numeric",
                    "longitude" => "numeric",
                    "cartSubTotal" => "numeric"
                ]
            );

            $userId = $req->user()->id;
            $currentTime = date('H:i:s');
            $currentDay = date('l');
            $currentDateTime = date('Y-m-d H:i:s');
            $code = $data['code'] ?? "";
            $discount = $data['discount'];
            $cartSubTotal = $data['cartSubTotal'];
            $latitude = $data['latitude'] ?? "";
            $longitude = $data['longitude'] ?? "";
            $distanceStatus = 1;
            $skuOffer = 0;
            $priceOffer  = 0;
            $shopIds = "";
            $dmsg = "";

            $sqlhome = Home::select("shop_range", "weight_capping", "order_capping", "shop_capping", "delivery_type", "delivery_charge", "minimum_value", "wallet_status", "prepaid_status", "packing_charge");

            $sqlhomeData = $sqlhome
                ->first()
                ->toArray();

            $sqlReg = Registration::select("wallet_balance")
                ->where("id", $userId);
            $sqlRegData = $sqlReg->first();

            if ($sqlRegData)
                $sqlRegData = $sqlRegData->toArray();
            else
                throw ExceptionHelper::somethingWentWrong([
                    "data" => [
                        "dev_message" => "Registration wallet details not found"
                    ]
                ]);

            $walletBalance = "0";

            if (!empty($sqlRegData['wallet_balance']))
                $walletBalance = $sqlRegData['wallet_balance'];


            $sql = Cart::select("*")
                ->where("user_id", $userId)
                ->orderBy("shop_id");

            if (!$sql->count())
                throw ExceptionHelper::notFound([
                    "message" => "Cart Not Found",
                    "data" => [
                        "walletBalance" => round($walletBalance),
                        "walletStatus" => $sqlhomeData['wallet_status'],
                    ]
                ]);

            $productDiscount = 0.00;
            $cartTotal = 0.00;
            $subTotal = 0.00;
            $cartCount = 0;
            $tqty = 0;
            $mrpTotal = 0.00;
            $offerTotal = 0.00;
            $priceOfferProductId = "";
            $offerAvailStatus = 0;
            $weightExceed = 0;
            $cartWeight = 0;

            $sql = $sql->get()->toArray();

            foreach ($sql as $sqlData) {

                if ($shopIds == '' || $shopIds != $sqlData['shop_id']) {
                    $shopIds = $sqlData['shop_id'];
                    $cartWeight = 0;
                }

                $sqlShop = Shop::select("id", "name", "status", "store_status", "latitude", "longitude")
                    ->where("id", $sqlData['shop_id']);

                $sqlShopData = $sqlShop->first();

                if (!$sqlShopData)
                    throw ExceptionHelper::somethingWentWrong([
                        "data" => [
                            "dev_message" => "Shop with id:" . $sqlData['shop_id'] . " not found"
                        ]
                    ]);
                else
                    $sqlShopData = $sqlShopData->toArray();

                if ($sqlShopData['status'] == 2 || $sqlShopData['status'] == 3) {
                    $storeStatus = 3;
                    $msg = $sqlShopData['name'] . " is Currently Not Available";
                } else {

                    $sqlTime = ShopTime::select("status", "time_from", "time_to")
                        ->where([
                            "shop_id" => $sqlData['shop_id'],
                            "day" => $currentDay
                        ]);

                    $sqlTimeData = $sqlTime->first();

                    if (!$sqlTimeData)
                        throw ExceptionHelper::somethingWentWrong([
                            "data" => [
                                "dev_message" => "ShopTime with shop id: " . $sqlData['shop_id'] . " not found"
                            ]
                        ]);

                    $sqlTimeData = $sqlTimeData->toArray();

                    if ($sqlTimeData['status'] == 0) {

                        $storeStatus = 3;
                        $msg = $sqlShopData['name'] . " is Temporarily Closed";
                    } else if (
                        $sqlShopData['status'] == 1
                        && $sqlShopData['store_status'] == 1
                        && $sqlTimeData['time_from'] < $currentTime
                        && $sqlTimeData['time_to'] > $currentTime
                    ) {

                        $storeStatus = 1;
                        $msg = "";
                    } else if (
                        $sqlShopData['status'] == 1
                        && $sqlShopData['store_status'] == 1
                        && $sqlTimeData['time_from'] < $currentTime
                        && $sqlTimeData['time_to'] < $currentTime
                    ) {

                        $storeStatus = 2;
                        $msg = $sqlShopData['name'] . " accepts orders\n between " . date('h:i A', strtotime($sqlTimeData['time_from'])) . " To " . date('h:i A', strtotime($sqlTimeData['time_to']));
                    } else if (
                        $sqlShopData['status'] == 1
                        && $sqlShopData['store_status'] == 1
                        && $sqlTimeData['time_from'] > $currentTime
                        && $sqlTimeData['time_to'] > $currentTime
                    ) {

                        $storeStatus = 2;
                        $msg = $sqlShopData['name'] . " accepts orders\n between " . date('h:i A', strtotime($sqlTimeData['time_from'])) . " To " . date('h:i A', strtotime($sqlTimeData['time_to']));
                    } else if (
                        $sqlShopData['status'] == 1
                        && $sqlShopData['store_status'] == 2
                    ) {

                        $storeStatus = 3;
                        $msg = $sqlShopData['name'] . " is Temporarily Closed";
                    }
                    if (
                        !empty($_POST['latitude'])
                        && !empty($_POST['longitude'])
                    ) {

                        $distance = UtilityHelper::getDistanceBetweenPlaces(
                            [
                                "lat" => $latitude,
                                "long" => $longitude,
                            ],
                            [
                                "lat" => $sqlShopData['latitude'],
                                "long" => $sqlShopData['longitude']
                            ]
                        );

                        if ($distance <= $sqlhomeData['shop_range'] || $sqlShopData['id'] == 46) {
                            $distanceStatus = 1;
                            $dmsg = "";
                        } else {
                            $distanceStatus = 0;
                            $dmsg = "Does not deliver to selected location";
                        }
                    }
                }

                $sqlProduct = Product::select("id", "name", "unique_code", "barcode", "image", "price", "sellingprice", "weight_in_gram", "status")
                    ->where("id", $sqlData['product_id']);

                $count = $sqlProduct->count();

                if ($count) {

                    $sqlProduct = $sqlProduct
                        ->get()
                        ->toArray();

                    foreach ($sqlProduct as $sqlProductData) {

                        $cartCount++;
                        $productImage = "";
                        $directory = '../products/' . $sqlProductData['barcode'] . '/';
                        $partialName = '1.';
                        $files = glob($directory . '*' . $partialName . '*');

                        if ($files !== false)
                            foreach ($files as $file) {
                                $productImage = basename($file);
                            }
                        else
                            $productImage = "";

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
                        $discount1 = "";

                        if ($j > 0)
                            $discount1 = round($j) . " % OFF";

                        if ($sqlData['offer_type'] == 2) {

                            $sqlPriceBundle = OfferPriceBundling::select("id", "offer_amount")
                                ->where([
                                    "offer_unique_id" => $sqlProductData['unique_code'],
                                    "status" => 1
                                ]);

                            if ($sqlPriceBundle->count()) {

                                $priceBundleData = $sqlPriceBundle
                                    ->first()
                                    ->toArray();

                                $price = $priceBundleData['offer_amount'];
                                $priceOffer = "1";
                            } else
                                $priceOffer = "0";
                        } else {

                            $sqlPriceBundle = OfferPriceBundling::select("id", "offer_amount")
                                ->where([
                                    "offer_unique_id" => $sqlProductData['unique_code'],
                                    "status" => 1
                                ]);

                            if ($sqlPriceBundle->count())
                                $priceOffer = "1";
                            else
                                $priceOffer = "0";
                        }

                        // $shopId = $sqlData['shop_id'];
                        $mrpTotal = $mrpTotal + ($sqlData['qty'] * $sqlProductData['sellingprice']);
                        $tqty = $tqty + $sqlData['qty'];
                        $shopName = CommonHelper::shopName($sqlData['shop_id']);
                        // $deliveryTime = CommonHelper::shopDeliveryTime($sqlData['shop_id']);
                        $maxQty = "5";
                        $cartWeight = $cartWeight + ($sqlProductData['weight_in_gram'] * $sqlData['qty']);
                        // $primaryStatus = 0;

                        $sqlOfferBundle = OfferBundling::select("offer_unique_id", "offer_amount")
                            ->where([
                                "status" => 1,
                                "primary_unique_id" => $sqlProductData['unique_code'],
                            ]);


                        if ($sqlOfferBundle->count()) {

                            $offerBundleData = $sqlOfferBundle
                                ->first()
                                ->toArray();

                            $sqlShopProduct = Product::select("id", "weight", "price", "sellingprice", "unit_id")
                                ->where([
                                    "shop_id" => $sqlData['shop_id'],
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

                                $sqlCartCheck = Cart::select("id", "qty")
                                    ->where([
                                        "product_id" => $shopProductData['id'],
                                        "shop_id" => $sqlData['shop_id'],
                                        "user_id" => $userId,
                                        "offer_type" => 1
                                    ]);

                                if ($sqlCartCheck->count()) {

                                    $cartCheckData = $sqlCartCheck
                                        ->first()
                                        ->toArray();

                                    if ($cartCheckData['qty'] >= $sqlData['qty']) {
                                        $offerTotal = $offerTotal + (($realPrice * $sqlData['qty']) - ($offerBundleData['offer_amount'] * $sqlData['qty']));
                                    } else {
                                        $offerTotal = $offerTotal + (($realPrice * $cartCheckData['qty']) - ($offerBundleData['offer_amount'] * $cartCheckData['qty']));
                                    }
                                }
                            }
                        }

                        $price = $sqlData['qty'] * $price;

                        if ($sqlData['offer_type'] == 0) {
                            $subTotal = $subTotal + ($price);
                            $productDiscount = sprintf('%0.2f', ($productDiscount + (($sqlData['qty'] * $sqlProductData['sellingprice']) - ($price))));
                        }

                        if ($discount > 0 && $sqlData['offer_type'] == 0) {
                            $offerPrice = sprintf('%0.2f', (($price * $discount) / $cartSubTotal));
                            $price = sprintf('%0.2f', ($price - $offerPrice));
                        }

                        $cartList[] = array(
                            "productId" => $sqlProductData['id'],
                            "shopId" => $sqlData['shop_id'],
                            "shopName" => $shopName,
                            "productName" =>  mb_convert_encoding($sqlProductData['name'], 'UTF-8'),
                            "price" => sprintf(
                                '%0.2f',
                                $price
                            ),
                            "sellingprice" => sprintf(
                                '%0.2f',
                                $sqlData['qty'] * $sqlProductData['sellingprice']
                            ),
                            "stock" => "",
                            "productStatus" => $sqlProductData['status'],
                            "productImage" => url("products") . "/" . $sqlProductData['barcode'] . '/' . $productImage,
                            "qty" => $sqlData['qty'],
                            "weight" => $sqlData['weight'],
                            "total" => $price,
                            "discount" => $discount1,
                            "max_qty" => $maxQty,
                            "storeStatus" => $storeStatus,
                            "message" => $msg,
                            "distanceStatus" => $distanceStatus,
                            "dmsg" => $dmsg,
                            "primaryStatus" => $sqlData['offer_type'],
                            "priceOffer" => $priceOffer
                        );

                        $cartTotal = $cartTotal + $price;
                    }
                }

                if ($cartWeight > $sqlhomeData['weight_capping'])
                    $weightExceed++;
            }

            if ($sqlhomeData['delivery_type'] == 1)
                $deliveryCharge = 0;
            else {
                $deliveryCharge = $sqlhomeData['delivery_charge'];
                if (!empty($sqlhomeData['minimum_value'])) {
                    if ($cartTotal > $sqlhomeData['minimum_value']) {
                        $deliveryCharge = 0;
                    }
                }
            }

            if (!empty($code)) {

                $coupon = $code;
                $date = date('d-m-Y H:i:s');

                $sqlCoupon = Coupon::select("*")
                    ->where([
                        "couponcode" => $coupon,
                        "status" => 1
                    ]);

                $sqlCoupon1 = Coupon::select("*")
                    ->where([
                        "couponcode" => $coupon,
                        "status" => 1,
                        "user_id" => $userId
                    ]);

                if ($sqlCoupon->count()) {

                    $sqlCouponData = $sqlCoupon
                        ->first()
                        ->toArray();

                    $date2 = date('d-m-Y H:i:s', strtotime($sqlCouponData['expire_date']));
                    $couponDate = strtotime($date2);
                    $currentDate = strtotime($date);

                    if ($currentDate <= $couponDate) {

                        if ($sqlCouponData['times_used_coupon'] == 1) {

                            $sqlCouponUseCount = Order::select("id")
                                ->where([
                                    "coupon" => $coupon,
                                    "payment_status" => 1
                                ])
                                ->groupBy("order_id");

                            $couponUseCount = $sqlCouponUseCount->count();

                            if ($couponUseCount >= $sqlCouponData['time_to_use_coupon'])
                                $discount = 0;
                            else {

                                if ($sqlCouponData['minimum_value'] <= $subTotal) {

                                    if ($sqlCouponData['discount_type'] == 0)
                                        $disType = "%";
                                    else
                                        $disType = "FLAT";

                                    $offerDescrption = "counpon Applied " . $sqlCouponData['discount'] . $disType . " OFF.";
                                    if ($sqlCouponData['discount_upto'] > 0) {
                                        $offerDescrption = "counpon Applied " . $sqlCouponData['discount'] . $disType . " OFF UPTO " . $sqlCouponData['discount_upto'] . " ) ";
                                    }

                                    if ($sqlCouponData['times_used'] == 1) {

                                        $sqlOrder = Order::select("id")
                                            ->where([
                                                "customer_id" => $userId,
                                                "coupon" => $coupon,
                                                "payment_status" => 1
                                            ])
                                            ->groupBy("payment_status");

                                        $usedCount = $sqlOrder->count();

                                        if ($usedCount >= $sqlCouponData['time_to_use'])
                                            $discount = 0;
                                        else {

                                            if ($sqlCouponData['discount_type'] == 0) {
                                                $couponDiscount = ($subTotal * $sqlCouponData['discount']) / 100;
                                                if ($sqlCouponData['discount_upto'] > 0) {
                                                    if ($couponDiscount > $sqlCouponData['discount_upto']) {
                                                        $couponDiscount = $sqlCouponData['discount_upto'];
                                                    }
                                                }
                                            } else
                                                $couponDiscount = $sqlCouponData['discount'];

                                            $discount = $couponDiscount;
                                        }
                                    } else {
                                        if ($sqlCouponData['discount_type'] == 0) {
                                            $couponDiscount = ($subTotal * $sqlCouponData['discount']) / 100;
                                            if ($sqlCouponData['discount_upto'] > 0) {
                                                if ($couponDiscount > $sqlCouponData['discount_upto']) {
                                                    $couponDiscount = $sqlCouponData['discount_upto'];
                                                }
                                            }
                                        } else
                                            $couponDiscount = $sqlCouponData['discount'];

                                        $discount = $couponDiscount;
                                    }
                                } else
                                    $discount = 0;
                            }
                        } else {

                            if ($sqlCouponData['minimum_value'] <= $subTotal) {

                                if ($sqlCouponData['discount_type'] == 0)
                                    $disType = "%";
                                else
                                    $disType = "FLAT";
                                $offerDescrption = "counpon Applied " . $sqlCouponData['discount'] . $disType . " OFF.";
                                if ($sqlCouponData['discount_upto'] > 0) {
                                    $offerDescrption = "counpon Applied " . $sqlCouponData['discount'] . $disType . " OFF UPTO " . $sqlCouponData['discount_upto'] . " ) ";
                                }

                                if ($sqlCouponData['times_used'] == 1) {

                                    $sqlOrder = Order::select("id")
                                        ->where([
                                            "customer_id" => $userId,
                                            "coupon" => $coupon,
                                            "payment_status" => 1
                                        ])
                                        ->groupBy("order_id");

                                    $usedCount = $sqlOrder->count();

                                    if ($usedCount >= $sqlCouponData['time_to_use'])
                                        $discount = 0;
                                    else {
                                        if ($sqlCouponData['discount_type'] == 0) {
                                            $couponDiscount = ($subTotal * $sqlCouponData['discount']) / 100;
                                            if ($sqlCouponData['discount_upto'] > 0) {
                                                if ($couponDiscount > $sqlCouponData['discount_upto'])
                                                    $couponDiscount = $sqlCouponData['discount_upto'];
                                            }
                                        } else
                                            $couponDiscount = $sqlCouponData['discount'];

                                        $discount = $couponDiscount;
                                    }
                                } else {
                                    if ($sqlCouponData['discount_type'] == 0) {
                                        $couponDiscount = ($subTotal * $sqlCouponData['discount']) / 100;
                                        if ($sqlCouponData['discount_upto'] > 0) {
                                            if ($couponDiscount > $sqlCouponData['discount_upto'])
                                                $couponDiscount = $sqlCouponData['discount_upto'];
                                        }
                                    } else
                                        $couponDiscount = $sqlCouponData['discount'];

                                    $discount = $couponDiscount;
                                }
                            } else
                                $discount = 0;
                        }
                    } else
                        $discount = 0;
                } else if ($sqlCoupon1->count()) {

                    $sqlCouponData1 = $sqlCoupon1
                        ->first()
                        ->toArray();
                    $date2  = date('d-m-Y H:i:s', strtotime($sqlCouponData1['expire_date']));
                    $currentDate  = strtotime($date);
                    $couponDate  = strtotime($date2);

                    if ($currentDate <= $couponDate) {

                        if ($sqlCouponData1['discount_type'] == 0)
                            $disType = "%";
                        else
                            $disType = "FLAT";

                        $offerDescrption = "counpon Applied " . $sqlCouponData1['discount'] . $disType . " OFF.";

                        if ($sqlCouponData1['discount_upto'] > 0) {
                            $offerDescrption = "counpon Applied " . $sqlCouponData1['discount'] . $disType . " OFF UPTO " . $sqlCouponData1['discount_upto'] . " ) ";
                        }

                        if ($sqlCouponData1['times_used'] == 1) {

                            $sqlOrder = Order::select("id")
                                ->where([
                                    "customer_id" => $userId,
                                    "coupon" => $coupon,
                                    "payment_status" => 1
                                ])
                                ->groupBy("order_id");

                            $usedCount = $sqlOrder->count();

                            if ($usedCount >= $sqlCouponData1['time_to_use'])
                                throw ExceptionHelper::unprocessable([
                                    "message" => "YOU ARE ALREADY AVAIL THIS OFFER."
                                ]);
                            else {
                                if ($sqlCouponData1['minimum_value'] <= $subTotal) {
                                    if ($sqlCouponData1['discount_type'] == 0) {
                                        $couponDiscount = ($subTotal * $sqlCouponData1['discount']) / 100;
                                        if ($sqlCouponData1['discount_upto'] > 0) {
                                            if ($couponDiscount > $sqlCouponData1['discount_upto']) {
                                                $couponDiscount = $sqlCouponData1['discount_upto'];
                                            }
                                        }
                                    } else
                                        $couponDiscount = $sqlCouponData1['discount'];

                                    return response([
                                        "data" => [
                                            "code" => $coupon,
                                            "discount" => $couponDiscount,
                                            "couponDescription" => $offerDescrption
                                        ],
                                        "status" => true,
                                        "statusCode" => 200,
                                        "message" => "COUPON APPLIED.",
                                    ], 200);
                                } else
                                    throw ExceptionHelper::unprocessable([
                                        "message" => "Minimun Order Value To Apply This Coupon is " . $sqlCouponData1['minimum_value'] . "/-"
                                    ]);
                            }
                        } else {
                            if ($sqlCouponData1['minimum_value'] <= $subTotal) {
                                if ($sqlCouponData1['discount_type'] == 0) {
                                    $couponDiscount = ($subTotal * $sqlCouponData1['discount']) / 100;
                                    if ($sqlCouponData1['discount_upto'] > 0) {
                                        if ($couponDiscount > $sqlCouponData1['discount_upto']) {
                                            $couponDiscount = $sqlCouponData1['discount_upto'];
                                        }
                                    }
                                } else
                                    $couponDiscount = $sqlCouponData1['discount'];

                                return response([
                                    "data" => [
                                        "discount" => $couponDiscount,
                                        "code" => $coupon,
                                        "couponDescription" => $offerDescrption
                                    ],
                                    "status" => true,
                                    "statusCode" => 200,
                                    "message" => "COUPON APPLIED.",
                                ], 200);
                            } else
                                throw ExceptionHelper::unprocessable([
                                    "message" => "Minimun Order Value To Apply This Coupon is " . $sqlCouponData1['minimum_value'] . "/-"
                                ]);
                        }
                    } else
                        throw ExceptionHelper::unAuthorized([
                            "message" => "Minimun Order Value To Apply This Coupon is " . $sqlCouponData1['minimum_value'] . "/-"
                        ]);
                }
            }

            $sqlPriceOfferCart = Cart::select("id", "product_id")
                ->where([
                    "user_id" => $userId,
                    "offer_type" => 2
                ]);

            if ($sqlPriceOfferCart->count()) {

                $priceOfferCartData = $sqlPriceOfferCart
                    ->first()
                    ->toArray();

                $sqlPriceProductCheck = Product::select("unique_code", "price", "sellingprice")
                    ->where("id", $priceOfferCartData['product_id']);

                $priceProductCheckData = $sqlPriceProductCheck
                    ->first();

                if (!$priceProductCheckData)
                    throw ExceptionHelper::somethingWentWrong([
                        "data" => [
                            "dev_message" => "Product with id: " . $priceOfferCartData['product_id'] . " not found"
                        ]
                    ]);

                $priceProductCheckData = $priceProductCheckData->toArray();

                $sqlPriceOfferCheck = OfferPriceBundling::select("offer_amount")
                    ->where([
                        "offer_unique_id" => $priceProductCheckData['unique_code'],
                        "status" => 1
                    ]);

                $priceOfferCheckData = $sqlPriceOfferCheck->first();

                if (!$priceOfferCheckData)
                    throw ExceptionHelper::somethingWentWrong([
                        "data" => [
                            "dev_message" => "OfferPriceBundling with offer_unique_id: " .  $priceProductCheckData['unique_code'] . " and status: 1 not found"
                        ]
                    ]);

                $priceOfferCheckData = $priceOfferCheckData->toArray();

                if ($priceProductCheckData['price'] == 0)
                    $offerPrice = $priceProductCheckData['sellingprice'];
                else
                    $offerPrice = $priceProductCheckData['price'];

                $offerTotal = $offerTotal + (($offerPrice * 1) - ($priceOfferCheckData['offer_amount'] * 1));
            }

            $gTotal = ($cartTotal + $deliveryCharge);

            $sqlSkuOffer = Cart::select("id")
                ->where([
                    "user_id" => $userId,
                    "offer_type" => 1
                ]);

            if (!$sqlSkuOffer->count()) {

                $sqlPriceOffer = OfferPriceBundling::select("id")
                    ->where("minimum_offer_value", "<=", $gTotal)
                    ->where("status", 1)
                    ->orderBy("minimum_offer_value", "desc");

                if ($sqlPriceOffer->count())
                    $offerAvailStatus = 1;
            } else {

                $offerAvailStatus = 0;

                $sqlOfferCart = Cart::where([
                    "user_id" => $userId,
                    "offer_type" => 2
                ]);

                if ($sqlOfferCart->count()) {

                    $offerCartData = $sqlOfferCart
                        ->first()
                        ->toArray();

                    Cart::where("id", $offerCartData['id'])
                        ->delete();
                }
            }

            $sqlOfferCart = Cart::select("id", "product_id", "qty")
                ->where([
                    "user_id" => $userId,
                    "offer_type" => 2
                ]);

            if ($sqlOfferCart->count()) {

                $offerCartData = $sqlOfferCart
                    ->first()
                    ->toArray();

                $sqlProductPriceCart = Product::select("unique_code")
                    ->where("id", $offerCartData['product_id']);

                $productPriceCartData = $sqlProductPriceCart->first();

                if (!$productPriceCartData)
                    throw ExceptionHelper::somethingWentWrong([
                        "data" => [
                            "dev_message" => "Product with id: " . $offerCartData['product_id'] . " not found"
                        ]
                    ]);

                $productPriceCartData =  $productPriceCartData->toArray();

                $sqlMinimumCheck = OfferPriceBundling::select("id")
                    ->where("minimum_offer_value", ">", $gTotal)
                    ->where([
                        "offer_unique_id" => $productPriceCartData['unique_code'],
                        "status" => 1
                    ]);

                if ($sqlMinimumCheck->count()) {

                    if ($offerCartData['qty'] > 0)
                        Cart::where("id", $offerCartData['id'])
                            ->update("offer_type", 0);
                }

                $sqlMinimumCheck1 = OfferPriceBundling::select("id")
                    ->where([
                        "offer_unique_id" => $productPriceCartData['unique_code'],
                        "status" => 1
                    ])
                    ->where("end_date", "<", $currentDateTime);

                if ($sqlMinimumCheck1->count()) {

                    if ($offerCartData['qty'] > 0)
                        Cart::where("id", $offerCartData['id'])
                            ->update("offer_type", 0);
                }
            }

            $savings = $productDiscount + $offerTotal + $discount;
            $gTotal = ($mrpTotal + $deliveryCharge) - $savings;

            $msgHead = "";
            $msgInfo = "";
            $shopLimit = 1;
            $orderLimit = 1;
            $weightLimit = 1;

            $sqlRunningOrders = Order::select("id")
                ->where([
                    "customer_id" => $userId,
                    "payment_status" => 1
                ])
                ->where("status", "<", 3)
                ->groupBy("order_id");

            $runningOrders = $sqlRunningOrders
                ->get()
                ->toArray();

            $sqlCartShops = Cart::select("id")
                ->where("user_id", $userId)
                ->groupBy("shop_id");

            $cartShops = $sqlCartShops->count();

            if ($runningOrders >= $sqlhomeData['order_capping']) {
                $orderLimit = 0;
                $msgHead = "Orders Limit Exceed";
                $msgInfo = "You can't place more than " . $sqlhomeData['order_capping'] . " running orders. Wait to deliver them first";
            } else if ($cartShops >= $sqlhomeData['shop_capping']) {
                $shopLimit = 0;
                $msgHead = "Shops Limit Exceed";
                $msgInfo = "You can select products from only " . $sqlhomeData['shop_capping'] . " shops in single order";
            } else if ($weightExceed > 0) {
                $weightLimit = 0;
                $msgHead = "Weight Limit Exceed";
                $msgInfo = "You can't add more than " . ($sqlhomeData['weight_capping'] / 1000) . " KG from single shop";
            }

            if ($cartCount > 0) {

                $cartImage1 = "";
                $cartImage2 = "";

                $arr  = array(
                    "status" => true,
                    "repeatStatus" => '0',
                    "cartCount" => $cartCount,
                    "orderLimit" => $orderLimit,
                    "shopLimit" => $shopLimit,
                    "weightLimit" => $weightLimit,
                    "msgHead" => $msgHead,
                    "msgInfo" => $msgInfo,
                    "walletBalance" => round($walletBalance),
                    "totalQty" => $tqty,
                    "shopName" => $shopName,
                    'deliveryTime' => "10-15 Min",
                    "cartList" => $cartList,
                    "cartSubtotal" => sprintf('%0.2f', $mrpTotal),
                    "subTotal" => sprintf('%0.2f', $subTotal),
                    "deliveryCharge" => round($deliveryCharge),
                    "discount" => sprintf('%0.2f', $discount),
                    "productDiscount" => sprintf('%0.2f', $productDiscount),
                    "offerTotal" => $offerTotal,
                    "offerAvailStatus" => $offerAvailStatus,
                    "grandTotal" => sprintf('%0.2f', $gTotal),
                    "savings" => sprintf('%0.2f', $savings),
                    "paymentStatus" => $sqlhomeData['prepaid_status'],
                    "walletStatus" => $sqlhomeData['wallet_status'],
                    "packingCharge" => $sqlhomeData['packing_charge'],
                    "cartImage1" => $cartImage1,
                    "cartImage2" => $cartImage2
                );
            } else
                return response([
                    "data" => [
                        "walletBalance" => round($walletBalance),
                        "walletStatus" => $sqlhomeData['wallet_status']
                    ],
                    "status" => true,
                    "statusCode" => 200,
                    "message" => "Cart Empty.",
                ], 200);

            throw ExceptionHelper::somethingWentWrong();
        } catch (ValidationException $e) {

            return response([
                "data" => null,
                "status" => false,
                "statusCode" => 422,
                "message" => $e->getMessage(),
            ], 422);
        } catch (ExceptionHelper $e) {

            Log::error($e->getMessage());

            return response([
                "data" => $e->data,
                "status" => $e->status,
                "statusCode" => $e->statusCode,
                "message" => $e->getMessage(),
            ], $e->statusCode);
        }
    }
}
