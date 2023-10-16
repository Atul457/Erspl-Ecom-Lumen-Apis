<?php

namespace App\Services;

use App\Constants\StatusCodes;
use App\Helpers\CommonHelper;
use App\Helpers\ExceptionHelper;
use App\Helpers\RequestValidator;
use App\Models\Cart;
use App\Models\OfferBundling;
use App\Models\OfferPriceBundling;
use App\Models\Product;
use App\Models\Shop;
use App\Models\SubCategory;
use App\Models\Wishlist;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @todo Document this
 */
class ProductService
{

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    function subCategoryList(Request $req)
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

        return [
            "response" => [
                "data" => [
                    "subCategoryList" => $subCategoryList
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
                'weight' => $sqlProductData['weight'] . " " . CommonHelper::uomName($sqlProductData['unit_id']),
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
    public function productDetail(Request $req)
    {
        $productlist = array();

        $data = RequestValidator::validate(
            $req->input(),
            [
                'numeric' => ':attribute must contain only numbers',
                'productId.exists' => 'Product Detail Not Found.',
                'shopId.exists' => "Shop with provided id doesn\'t exist",
            ],
            [
                "shopId" => "required|exists:shop,id",
                "productId" => "required|exists:product,id",
            ]
        );

        $currentTime = date('H:i:s');
        $userId = $req->user()->id;
        $productId = $_POST['productId'];
        $shopId = $_POST['shopId'];

        $sqlProduct = Product::select("*")
            ->where([
                "id" => $productId,
                "shop_id" => $shopId
            ]);

        $count = $sqlProduct->count();

        $sqlShop = Shop::select("name", "store_status", "time_to")
            ->where("id", $shopId);

        $sqlShopData = $sqlShop
            ->first()
            ->toArray();

        if ($sqlShopData['store_status'] == 1) {
            $storeStatus = 1;
            if ($sqlShopData['time_to'] < $currentTime) {
                $storeStatus = 0;
            }
        }

        if ($sqlShopData['store_status'] == 2) {
            $storeStatus = 0;
        }

        $sqlWishlist = Wishlist::select("id")
            ->where([
                "customer_id" => $userId,
                "product_id" => $productId
            ]);

        if ($sqlWishlist->count()) {
            $wishStatus = "0";
        } else {
            $wishStatus = "1";
        }

        if ($count > 0) {

            $productImage   = array();
            $sqlProductData = $sqlProduct
                ->first()
                ->toArray();

            if (
                !empty($sqlProductData['image'])
                &&
                file_exists("../products/" . $sqlProductData['barcode'] . '/' . $sqlProductData['image'])
            ) {
                array_push($productImage, url("/products") . "/" . $sqlProductData['barcode'] . '/' . $sqlProductData['image']);
            }

            if (
                !empty($sqlProductData['back_image'])
                &&
                file_exists("../products/" . $sqlProductData['barcode'] . '/' . $sqlProductData['back_image'])
            ) {
                array_push($productImage, url("/products") . "/" . $sqlProductData['barcode'] . '/' . $sqlProductData['back_image']);
            }

            if (
                !empty($sqlProductData['ingredient_image'])
                &&
                file_exists("../products/" . $sqlProductData['barcode'] . '/' . $sqlProductData['ingredient_image'])
            ) {
                array_push($productImage, url("/products") . "/" . $sqlProductData['barcode'] . '/' . $sqlProductData['ingredient_image']);
            }

            if (
                !empty($sqlProductData['nutrition_image'])
                &&
                file_exists("../products/" . $sqlProductData['barcode'] . '/' . $sqlProductData['nutrition_image'])
            ) {
                array_push($productImage, url("/products") . "/" . $sqlProductData['barcode'] . '/' . $sqlProductData['nutrition_image']);
            }

            if (
                !empty($sqlProductData['fssai_image'])
                &&
                file_exists("../products/" . $sqlProductData['barcode'] . '/' . $sqlProductData['fssai_image'])
            ) {
                array_push($productImage, url("/products") . "/" . $sqlProductData['barcode'] . '/' . $sqlProductData['fssai_image']);
            }

            $unitList = array();

            if ($sqlProductData['price'] == 0) {
                $price = $sqlProductData['sellingprice'];
            } else {
                $price = $sqlProductData['price'];
            }

            $f  = $sqlProductData['sellingprice'];
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

            array_push($unitList, array(
                'weight' => $sqlProductData['weight'] . " " . CommonHelper::uomName($sqlProductData['unit_id']),
                "price" => $price,
                "sellingPrice" => $sqlProductData['sellingprice'],
                "discount" => $discount
            ));

            $sqlProduct1 = Product::select('id', 'name', 'image', 'shop_id', 'price', 'sellingprice', 'unique_code', 'hsn_code', 'weight', 'unit_id', 'small_description', 'barcode', 'sort_order')
                ->where('shop_id', $shopId)
                ->where('category_id', $sqlProductData['category_id'])
                ->where('subcategory_id', $sqlProductData['subcategory_id'])
                ->where('id', '!=', $productId)
                ->where('status', 1)
                ->limit(10)
                ->get()
                ->toArray();

            $productlist = array();

            foreach ($sqlProduct1 as $sqlProductData1) {

                $productImage1 = explode("$", $sqlProductData1['image']);
                $unitList2   = array();

                if ($sqlProductData1['price'] == 0) {
                    $price1 = $sqlProductData1['sellingprice'];
                } else {
                    $price1 = $sqlProductData1['price'];
                }

                $f  = $sqlProductData1['sellingprice'];
                $g  = $price1;
                $h  = $f - $g;
                $i  = $f / 100;
                $j1 = $h / $i;
                $roundOffDiscount1 = explode('.', number_format($j1, 2));
                $j = $roundOffDiscount1[0];
                $discount2 = "";

                if ($j > 0) {
                    $discount2 = round($j) . "% OFF";
                }

                array_push($unitList2, array(
                    'weight' => $sqlProductData1['weight'] . " " . CommonHelper::uomName($sqlProductData1['unit_id']),
                    "price" => $price1,
                    "sellingPrice" => $sqlProductData1['sellingprice'],
                    "discount" => $discount2
                ));

                $sqlPriceBundle1 = OfferPriceBundling::select("id")
                    ->where([
                        "offer_unique_id" => $sqlProductData1['unique_code'],
                        "status" => 1
                    ]);

                if ($sqlPriceBundle1->count()) {
                    $priceOffer1 = "1";
                } else {
                    $priceOffer1 = "0";
                }

                $productlist[] = array(
                    "productId" => $sqlProductData1['id'],
                    "shopId" => $sqlProductData1['shop_id'],
                    "shopName" => CommonHelper::shopName($sqlProductData1['shop_id']),
                    "status" => $storeStatus,
                    "max_qty" => "5",
                    "productName" => ucwords(
                        ucfirst(
                            strtolower(
                                mb_convert_encoding($sqlProductData1["name"], 'UTF-8')
                            )
                        )
                    ),
                    "small_description" => $sqlProductData1['small_description'],
                    "productImage" => url("/products") . "products/" . $sqlProductData1['barcode'] . '/' . $productImage1[0],
                    "priceOffer" => $priceOffer1,
                    "unit" => $unitList2
                );
            }

            if (empty($sqlProductData['small_description'])) {
                $descriptionStatus = "0";
            } else {
                $descriptionStatus = "1";
            }

            if (empty($sqlProductData['company'])) {
                $mStatus = "0";
                $manufacturer = "";
            } else {
                $mStatus = "1";
                $manufacturer = $sqlProductData['company'];
            }

            if (empty($sqlProductData['self_life'])) {
                $expireStatus = "0";
                $expireDate   = "";
            } else {
                $expireStatus = "1";
                $expireDate   = $sqlProductData['self_life'];
            }

            $offerInformation = "";

            $sqlOfferBundle = OfferBundling::select("offer_unique_id", "description")
                ->where([
                    "primary_unique_id" => $sqlProductData['unique_code'],
                    "status" => 1
                ]);

            if ($sqlOfferBundle->count()) {

                $offerBundleData = $sqlOfferBundle
                    ->first()
                    ->toArray();

                $sqlShopProduct =  Product::select("id")
                    ->where([
                        "shop_id" => $shopId,
                        "unique_code" => $offerBundleData['offer_unique_id'],
                        "status" => 1
                    ]);

                if ($sqlShopProduct->count()) {
                    $offerInformation = $offerBundleData['description'];
                }
            }

            $sqlPriceBundle = OfferPriceBundling::select("id")
                ->where([
                    "offer_unique_id" => $sqlProductData['unique_code'],
                    "status" => 1
                ]);

            if ($sqlPriceBundle->count()) {
                $priceOffer = "1";
            } else {
                $priceOffer = "0";
            }

            return [
                "response" => [
                    "status" => true,
                    "statusCode" => StatusCodes::OK,
                    "data" => array(
                        "productId" => $sqlProductData['id'],
                        "shopId" => $sqlProductData['shop_id'],
                        "shopName" => CommonHelper::shopName($sqlProductData['shop_id']),
                        "prostatus" => $storeStatus,
                        "max_qty" => "5",
                        "productName" =>  ucwords(
                            ucfirst(
                                strtolower(
                                    mb_convert_encoding($sqlProductData["name"], 'UTF-8')
                                )
                            )
                        ),
                        "small_description" => $sqlProductData['small_description'],
                        "productImage" => $productImage,
                        'descriptionStatus' => $descriptionStatus,
                        "expiryDate" => $expireDate,
                        'expireStatus' => $expireStatus,
                        'manufacturer' => $manufacturer,
                        'mStatus' => $mStatus,
                        "returnable" => $sqlProductData['returnable'],
                        "wishStatus" => $wishStatus,
                        "offerInformation" => $offerInformation,
                        "priceOffer" => $priceOffer,
                        "unit" => $unitList,
                        "similarProductList" => $productlist
                    ),
                    "message" => null,
                ],
                "statusCode" => StatusCodes::OK
            ];
        } else {
            throw ExceptionHelper::error([
                "message" => "Product Detail Not Found."
            ]);
        }
    }


    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function similarProductList(Request $req)
    {
        $similarProductList = array();

        $userId = $req->user()->id;
        $currentTime = date('H:i:s');

        $sqlCart = Cart::select("shop_id")
            ->where("user_id", $userId)
            ->groupBy("shop_id")
            ->get()
            ->toArray();

        foreach ($sqlCart as $sqlCartData) {

            $shopId = $sqlCartData['shop_id'];

            $sqlShop = Shop::select("*")
                ->where("id", $shopId);

            $sqlShopData = $sqlShop
                ->first()
                ?->toArray();

            if (!$sqlShopData)
                throw ExceptionHelper::error([
                    "message" => "shop row not found where id: $shopId"
                ]);

            if ($sqlShopData['store_status'] == 1) {
                $storeStatus = 1;
                if ($sqlShopData['time_to'] < $currentTime) {
                    $storeStatus = 0;
                }
            }

            if ($sqlShopData['store_status'] == 2) {
                $storeStatus = 0;
            }

            $sqlProduct = Product::select("*")
                ->where([
                    "shop_id" => $shopId,
                    "status" => 1
                ])
                ->limit(10);

            $count = $sqlProduct->count();

            if ($count > 0) {

                $similarProductList = array();

                $sqlProduct = $sqlProduct
                    ->get()
                    ->toArray();

                foreach ($sqlProduct as $sqlProductData) {

                    $productImage = "";
                    $directory = '../products/' . $sqlProductData['barcode'] . '/';
                    $partialName = '1';

                    $files = glob($directory . '*' . $partialName . '*');

                    if ($files !== false) {
                        foreach ($files as $file) {
                            $productImage = basename($file);
                        }
                    } else {
                        $productImage = "";
                    }

                    $unitList = array();

                    if ($sqlProductData['price'] == 0) {
                        $price = $sqlProductData['sellingprice'];
                    } else {
                        $price = $sqlProductData['price'];
                    }

                    $f  = $sqlProductData['sellingprice'];
                    $g  = $price;
                    $h  = $f - $g;
                    $i  = $f / 100;
                    $j1 = $h / $i;

                    $roundOffDiscount1 = explode('.', number_format($j1, 2));

                    $j = $roundOffDiscount1[0];

                    $discount = "";
                    if ($j > 0) {
                        $discount = round($j) . " % OFF";
                    }

                    array_push($unitList, array(
                        'variantId' => $sqlProductData['id'],
                        'weight' => $sqlProductData['weight'] . " " . CommonHelper::uomName($sqlProductData['unit_id']),
                        "price" => $price,
                        "sellingPrice" => $sqlProductData['sellingprice'],
                        "stock" => "",
                        "discount" => $discount
                    ));

                    $similarProductList[] = array(
                        "productId" => $sqlProductData['id'],
                        "shopId" => $sqlProductData['shop_id'],
                        "shopName" => $sqlShopData['name'],
                        "status" => $storeStatus,
                        "max_qty" => $sqlShopData['max_qty'],
                        "productName" =>   ucwords(
                            ucfirst(
                                strtolower(
                                    mb_convert_encoding($sqlProductData["name"], 'UTF-8')
                                )
                            )
                        ),
                        "small_description" => $sqlProductData['small_description'],
                        "productImage" => url("/products") . "/" . $sqlProductData['barcode'] . '/' . $productImage,
                        "unit" => $unitList
                    );
                }

                return [
                    "response" => [
                        "status" => true,
                        "statusCode" => StatusCodes::OK,
                        "data" => [
                            "similarProductList" => $similarProductList
                        ],
                        "message" => null,
                    ],
                    "statusCode" => StatusCodes::OK
                ];
            } else {
                throw ExceptionHelper::error([
                    "statusCode" => StatusCodes::NOT_FOUND,
                    "message" => "There are no Products in this store."
                ]);
            }
        }

        throw ExceptionHelper::error([
            "message" => "Bypassed all cases."
        ]);
    }
}
