<?php

namespace App\Services;

use App\Constants\StatusCodes;
use App\Helpers\CommonHelper;
use App\Helpers\ExceptionHelper;
use App\Helpers\RequestValidator;
use App\Models\Cart;
use App\Models\OfferPriceBundling;
use App\Models\Product;
use Laravel\Lumen\Http\Request;

// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @todo Document this
 */
class OfferPriceBundlingService
{
    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function offerAvailableList(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [
                'numeric' => ':attribute must contain only numbers',
                'shopId.exists' => "shop with provided id doesn\'t exist",
            ],
            [
                "shopId" => "required|numeric|exists:shop,id",
                "cartTotal" => "required|numeric",
            ]
        );

        $userId    = $req->user()->id;
        $shopId    = $data['shopId'];
        $cartTotal = $data['cartTotal'];
        $currentTime = date('Y-m-d H:i:s');

        $offer = 0;
        $shopTotalList = array();

        $sqlCart = Cart::select("shop_id")
            ->where("user_id", $userId)
            ->groupBy("shop_id");

        if ($sqlCart->count() > 0) {

            $offerList = array();
            $notAvailCount = 0;

            $sqlCart = $sqlCart
                ->get()
                ->toArray();

            foreach ($sqlCart as $cartData) {

                $shopTotal = 0;
                $sqlShopCart = Cart::select("*")
                    ->where([
                        "user_id" => $userId,
                        "shop_id" =>  $cartData['shop_id']
                    ])
                    ->get()
                    ->toArray();

                foreach ($sqlShopCart as $shopCartData) {

                    $productId = $shopCartData['product_id'];
                    $sqlProduct1 = Product::select("*")
                        ->where("id", $productId)
                        ->where("status", 1);

                    $count = $sqlProduct1->count();

                    if ($count > 0) {

                        $productData1 =  $sqlProduct1
                            ->first()
                            ->toArray();

                        if ($productData1['price'] == 0) {
                            $price = $productData1['sellingprice'];
                        } else {
                            $price = $productData1['price'];
                        }

                        $shopTotal = $shopTotal + ($price * $shopCartData['qty']);
                    }
                }

                $shopTotalList[] = array(
                    'shopId' => $cartData['shop_id'],
                    'shopTotal' => $shopTotal
                );

                $sqlPriceOffer1 = OfferPriceBundling::select("*")
                    ->where("minimum_offer_value", "<=", $shopTotal)
                    ->where("shop_id", $cartData['shop_id'])
                    ->where("live_date", "<", $currentTime)
                    ->where("end_date", ">", $currentTime)
                    ->where("status", 1)
                    ->orderBy("minimum_offer_value", "desc");

                $sqlPriceOffer1_ = $sqlPriceOffer1
                    ->get()
                    ->toArray();

                foreach ($sqlPriceOffer1_ as $priceOfferData1) {

                    $added = 0;
                    $shopId = $cartData['shop_id'];
                    $uniqueCode = $priceOfferData1['offer_unique_id'];

                    $sqlProduct2 = Product::select("*")
                        ->where("unique_code", $uniqueCode)
                        ->where("shop_id", $shopId);

                    $productData2  = $sqlProduct2
                        ->first()
                        ?->toArray();

                    if (!$productData2)
                        throw ExceptionHelper::error([
                            "message" => "product row not found where unique_code: $uniqueCode and shop_id: $shopId"
                        ]);

                    $productImage = "";
                    $directory = '../products/' . $productData2['barcode'] . '/';
                    $partialName = '1.';
                    $files = glob($directory . '*' . $partialName . '*');

                    if ($files !== false) {
                        foreach ($files as $file) {
                            $productImage = basename($file);
                        }
                    } else {
                        $productImage = "";
                    }

                    $unitList = array();
                    $price = $priceOfferData1['offer_amount'];

                    if ($price == $priceOfferData1['sellingprice']) {
                        $sellingprice = "";
                    } else {
                        $sellingprice = "₹" . round($productData2['sellingprice']);
                    }

                    $f  = $productData2['sellingprice'];
                    $g  = $price;
                    $h  = $f - $g;
                    $i  = $f / 100;
                    $j1 = $h / $i;
                    $roundOffDiscount1 = explode('.', number_format($j1, 2));
                    $j = $roundOffDiscount1[0];
                    $discount = "";

                    if ($j > 0) {
                        $discount = round($j) . "% OFF";
                    }

                    $sqlCartCheck = Cart::select("id")
                        ->where([
                            "product_id" => $productData2['id'],
                            "offer_type" => 2,
                            "user_id" => $userId
                        ]);

                    if ($sqlCartCheck->count() > 0) {
                        $added = 1;
                    } else {
                        $notAvailCount++;
                    }

                    array_push(
                        $unitList,
                        array(
                            'variantId' => $productData2['id'],
                            'weight' => $productData2['weight'] . " " . CommonHelper::uomName($productData2['unit_id']),
                            "price" => round($price),
                            "sellingPrice" => $sellingprice,
                            "stock" => "",
                            "discount" => $discount
                        )
                    );

                    $offerList[] = array(
                        'offerId' => $priceOfferData1['id'],
                        "offerTitle" => $priceOfferData1['description'],
                        "productId" => $productData2['id'],
                        "shopId" => $productData2['shop_id'],
                        "shopName" => CommonHelper::shopName($productData2['shop_id']),
                        "productName" => ucwords(
                            ucfirst(
                                strtolower(
                                    mb_convert_encoding(
                                        $productData2['name'],
                                        'UTF-8'
                                    )
                                )
                            )
                        ),
                        "productImage" => url("products") . "/" . $productData2['barcode'] . '/' . $productImage,
                        "unit" => $unitList,
                        "added" => $added
                    );
                    $offer++;
                }
            }
        }

        $columns = array_column($shopTotalList, 'shopTotal');
        array_multisort($columns, SORT_DESC, $shopTotalList);
        $shopId = $shopTotalList[0]['shopId'];

        $sqlPriceOffer = OfferPriceBundling::select("*")
            ->where("minimum_offer_value", "<=", $cartTotal)
            ->where("offer_by", 1)
            ->where("live_date", "<", $currentTime)
            ->where("end_date", ">", $currentTime)
            ->where("status", 1)
            ->orderBy("minimum_offer_value", "desc");

        $count = $sqlPriceOffer->count();

        if ($count > 0) {

            $sqlPriceOffer = $sqlPriceOffer
                ->get()
                ->toArray();

            foreach ($sqlPriceOffer as $priceOfferData) {

                $added = 0;
                $uniqueCode = $priceOfferData['offer_unique_id'];

                $sqlProduct = Product::select("*")
                    ->where("unique_code", $uniqueCode)
                    ->where("shop_id", $shopId);

                $productData =  $sqlProduct
                    ->first()
                    ?->toArray();

                if (!$productData)
                    throw ExceptionHelper::error([
                        "message" => "product row not found where unique_code: $uniqueCode and shop_id: $shopId"
                    ]);

                $productImage = "";
                $directory = '../products/' . $productData['barcode'] . '/';
                $partialName = '1.';
                $files = glob($directory . '*' . $partialName . '*');

                if ($files !== false) {
                    foreach ($files as $file) {
                        $productImage = basename($file);
                    }
                } else {
                    $productImage = "";
                }

                $unitList = array();
                $price = $priceOfferData['offer_amount'];

                if ($price == $priceOfferData['sellingprice']) {
                    $sellingprice = "";
                } else {
                    $sellingprice = "₹" . round($productData['sellingprice']);
                }

                $f  = $productData['sellingprice'];
                $g  = $price;
                $h  = $f - $g;
                $i  = $f / 100;
                $j1 = $h / $i;
                $roundOffDiscount1 = explode('.', number_format($j1, 2));
                $j = $roundOffDiscount1[0];
                $discount = "";

                if ($j > 0) {
                    $discount = round($j) . "% OFF";
                }

                $sqlCartCheck = Cart::select("id")
                    ->where([
                        "product_id" => $productData['id'],
                        "offer_type" => 2,
                        "user_id" => $userId
                    ]);

                if ($sqlCartCheck->count() > 0) {
                    $added = 1;
                } else {
                    $notAvailCount++;
                }

                array_push(
                    $unitList,
                    array(
                        'variantId' => $productData['id'],
                        'weight' => $productData['weight'] . " " . CommonHelper::uomName($productData['unit_id']),
                        "price" => round($price),
                        "sellingPrice" => $sellingprice,
                        "stock" => "",
                        "discount" => $discount
                    )
                );

                $offerList[] = array(
                    'offerId' => $priceOfferData['id'],
                    "offerTitle" => $priceOfferData['description'],
                    "productId" => $productData['id'],
                    "shopId" => $productData['shop_id'],
                    "shopName" => CommonHelper::shopName($productData['shop_id']),
                    "productName" =>   ucwords(
                        ucfirst(
                            strtolower(
                                mb_convert_encoding(
                                    $productData['name'],
                                    'UTF-8'
                                )
                            )
                        )
                    ),
                    "productImage" => url("products") . "/" . $productData['barcode'] . '/' . $productImage,
                    "unit" => $unitList,
                    "added" => $added
                );

                $offer++;
            }
        } else {
        }

        if ($offer > 0) {
            return [
                "response" => [
                    "status" => true,
                    "statusCode" => StatusCodes::OK,
                    "data" => [
                        "offerList" => $offerList,
                        "notAvailCount" => $notAvailCount
                    ],
                    "message" => null,
                ],
                "statusCode" => StatusCodes::OK
            ];
        } else {
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::NOT_FOUND,
                "message" => "Not Have Any Offer in Current time"
            ]);
        }
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function offersList(Request $req)
    {
        $currentTime = date('Y-m-d H:i:s');
        $offerList = array();

        $sqlPriceOffer = OfferPriceBundling::where('status', 1)
            ->where('live_date', '<', $currentTime)
            ->where('end_date', '>', $currentTime)
            ->orderBy('minimum_offer_value', 'asc')
            ->get();

        $count = $sqlPriceOffer->count();

        if ($count > 0) {

            $sqlPriceOffer = $sqlPriceOffer
                ->get()
                ->toArray();

            foreach ($sqlPriceOffer as $priceOfferData) {

                $added = 0;
                $uniqueCode = $priceOfferData['offer_unique_id'];

                $sqlProduct = Product::select("*")
                    ->where('unique_code', $priceOfferData['offer_unique_id']);

                $productData = $sqlProduct
                    ->first()
                    ?->toArray();

                if ($productData)
                    throw ExceptionHelper::somethingWentWrong([
                        "message" => "product row not found where unique_code: $uniqueCode"
                    ]);

                $productImage = "";
                $directory = '../products/' . $productData['barcode'] . '/';
                $partialName = '1.';
                $files = glob($directory . '*' . $partialName . '*');

                if ($files !== false) {
                    foreach ($files as $file) {
                        $productImage = basename($file);
                    }
                } else {
                    $productImage = "";
                }

                $unitList   = array();
                $price = $priceOfferData['offer_amount'];
                $sellingprice = "";

                array_push(
                    $unitList,
                    array(
                        'variantId' => $productData['id'],
                        'weight' => $productData['weight'] . " " . CommonHelper::uomName($productData['unit_id']),
                        "price" => round($price),
                        "sellingPrice" => $sellingprice,
                        "stock" => "",
                        "discount" => ""
                    )
                );

                $offerList[] = array(
                    'offerId' => $priceOfferData['id'],
                    "offerTitle" => $priceOfferData['description'],
                    "productId" => $productData['id'],
                    "shopId" => $productData['shop_id'],
                    "shopName" => CommonHelper::shopName($productData['shop_id']),
                    "productName" => ucwords(
                        ucfirst(
                            strtolower(
                                mb_convert_encoding(
                                    $productData['name'],
                                    'UTF-8'
                                )
                            )
                        )
                    ),
                    "productImage" => url("products") . "/" . $productData['barcode'] . '/' . $productImage,
                    "unit" => $unitList
                );
            }

            return [
                "response" => [
                    "status" => true,
                    "statusCode" => StatusCodes::OK,
                    "data" => [
                        "offerList" => $offerList
                    ],
                    "message" => null,
                ],
                "statusCode" => StatusCodes::OK
            ];
        }

        throw ExceptionHelper::error([
            "statusCode" => StatusCodes::NOT_FOUND,
            "message" => "No Offers Available"
        ]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function availOffer(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [
                'shopId.exists' => "shop with provided id doesn\'t exist",
                'productId.exists' => "product with provided id doesn\'t exist",
            ],
            [
                "shopId" => "required|numeric|exists:shop,id",
                "productId" => "required|numeric|exists:product,id",
            ]
        );

        $userId = $req->user()->id;
        $shopId  = $data['shopId'];
        $productId = $data['productId'];
        $weight = $data['weight'];
        $qty = "1";

        $sql = Cart::select("*")
            ->where("user_id", $userId)
            ->where("shop_id", $shopId)
            ->where("product_id", $productId);

        if ($sql->count() === 0) {

            $sql  = Cart::create([
                'user_id' => $userId,
                'shop_id' => $shopId,
                'product_id' => $productId,
                'weight' => $weight,
                'qty' => $qty,
                'offer_type' => 2,
            ]);

            if ($sql) {
                return [
                    "response" => [
                        "status" => true,
                        "statusCode" => StatusCodes::OK,
                        "data" => [],
                        "message" => "Offer Applied",
                    ],
                    "statusCode" => StatusCodes::OK
                ];
            } else {
                throw ExceptionHelper::error([
                    "message" => "Unable to add item to cart table"
                ]);
            }
        } else {

            // $data = $sql
            //     ->get()
            //     ->toArray();

            // $qty = $qty + $data['qty'];

            $sql =  Cart::where('user_id', $userId)
                ->where('shop_id', $shopId)
                ->where('product_id', $productId)
                ->update([
                    'offer_type' => 2
                ]);

            if ($sql) {
                return [
                    "response" => [
                        "status" => true,
                        "statusCode" => StatusCodes::OK,
                        "data" => [],
                        "message" => "Offer Applied",
                    ],
                    "statusCode" => StatusCodes::OK
                ];
            } else {
                throw ExceptionHelper::error([
                    "message" => "Unable to update cart row where user_id: $userId, shop_id: $shopId and product_id: $productId"
                ]);
            }
        }
    }
}
