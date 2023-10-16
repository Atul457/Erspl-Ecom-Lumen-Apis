<?php

namespace App\Services;

use App\Constants\StatusCodes;
use App\Helpers\CommonHelper;
use App\Helpers\ExceptionHelper;
use App\Helpers\RequestValidator;
use App\Helpers\UtilityHelper;
use App\Models\AddressBook;
use App\Models\Cart;
use App\Models\FavShop;
use App\Models\Home;
use App\Models\Product;
use App\Models\Rating;
use App\Models\Shop;
use App\Models\ShopTime;
use App\Models\UserSearchLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @todo Document this
 */
class HomeService
{

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public static function searchHome(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [],
            [
                "barcode" => "required",
                "latitude" => "required|numeric",
                "longitude" => "required|numeric",
                "searchType" => "required|numeric",
                "name" => "required"
            ]
        );

        $currentTime = date('H:i:s');
        $currentDay  = date('l');
        $userId      = $req->user()->id;
        $name        = strip_tags($data['name']);
        $barcode     = $data['barcode'];
        $latitude    = $data['latitude'];
        $longitude   = $data['longitude'];
        $searchType  = (int) $data['searchType'];

        $sqlHome = Home::first();
        $sqlHomeData = $sqlHome
            ->toArray();

        if ($searchType === 1) {

            $sqlShop =  Product::select("*")
                ->where('name', 'like', '%' . $name . '%')
                ->where('barcode', $barcode)
                ->where('status', 1)
                ->groupBy('shop_id');

            UtilityHelper::disableSqlStrictMode();

            $count = $sqlShop->count();

            UtilityHelper::enableSqlStrictMode();

            if ($count) {

                $loop = 0;
                $shopList = array();

                UtilityHelper::disableSqlStrictMode();

                $sqlShop_ = $sqlShop
                    ->get()
                    ->toArray();

                UtilityHelper::enableSqlStrictMode();

                foreach ($sqlShop_ as $sqlShopData) {

                    $shopId = $sqlShopData['shop_id'];
                    $sql = Shop::find($shopId);
                    $data = $sql?->toArray() ?? [];

                    if (!$data)
                        throw ExceptionHelper::error([
                            "message" => "shop not found with id: $shopId"
                        ]);

                    $shopId = $data['id'];

                    if ($data['store_status'] == 1) {


                        $sqlWeek = ShopTime::select("*")
                            ->where([
                                "shop_id" => $shopId,
                                "day" => $currentDay
                            ]);

                        $sqlWeelData = $sqlWeek
                            ->first()
                            ?->toArray();

                        if (!$sqlWeelData)
                            throw ExceptionHelper::error([
                                "message" => "shoptime row not found where shopId: $shopId and day:$currentDay"
                            ]);

                        if ($sqlWeelData['time_from'] < $currentTime && $sqlWeelData['time_to'] > $currentTime) {
                            $storeStatus = 1;
                        } else {
                            $storeStatus = 0;
                        }
                    } else if ($data['store_status'] == 2) {
                        $storeStatus = 0;
                    }

                    $sqlFav = FavShop::where([
                        "user_id" =>  $userId,
                        "shop_id" => $shopId
                    ]);

                    if ($sqlFav->count()) {
                        $favStatus = 1;
                    } else {
                        $favStatus = 0;
                    }

                    $sqlRating = Rating::where([
                        "shop_id" => $shopId
                    ]);

                    $rating      = 0;
                    $ratingCount = 0;

                    if ($sqlRating->count()) {

                        $result = $sqlRating->select(DB::raw("AVG('rating') AS avg"));
                        $row  = $result
                            ->first()
                            ->toArray();

                        $rating = $row['avg'];

                        $result1  = $sqlRating->select(DB::raw("COUNT('rating') AS avg"));
                        $row1  = $result1
                            ->first()
                            ->toArray();

                        $ratingCount = $row1['avg'];
                    }

                    if ($data['discount_status'] == 1) {
                        $discountStatus      = $data['discount_status'];
                        $discountDiscription = $data['discount_discription'] . "% OFF";
                    } else {
                        $discountStatus      = $data['discount_status'];
                        $discountDiscription = "";
                    }

                    if (strlen($data['address']) > 100) {
                        $address = substr($data['address'], 0, 62) . "...";
                    } else {
                        $address = $data['address'];
                    }

                    $distance = UtilityHelper::getDistanceBetweenPlaces(
                        [
                            "lat" => $latitude,
                            "long" => $longitude,
                        ],
                        [
                            "lat" => $data['latitude'],
                            "long" => $data['longitude']
                        ]
                    );

                    if (
                        ($distance <= $sqlHomeData['shop_range'] && $data['status'] == 1)
                        ||
                        $sqlShopData['id'] == 46
                    ) {
                        $shopList[] = array(
                            'productId' => $sqlShopData['id'],
                            'name' => $data['name'],
                            'shopId' => $data['id'],
                            'mobile' => $data['mobile'],
                            'image' => url("/shops") . "/" . $data['image'],
                            'address' => $address,
                            'discountStatus' => $discountStatus,
                            'discountDiscription' => $discountDiscription,
                            'favStatus' => $favStatus,
                            'rating' => number_format(($rating), 1),
                            'ratingCount' => '(' . $ratingCount . ' Reviews)',
                            'distance' => $distance . ' KM',
                            'distance2' => $distance,
                            'latitude' => $data['latitude'],
                            'longitude' => $data['longitude'],
                            'storeStatus' => $storeStatus
                        );
                        $columns    = array_column($shopList, 'distance2');
                        array_multisort($columns, SORT_ASC, $shopList);
                        $loop++;
                    }
                }

                if ($loop > 0) {
                    return [
                        "response" => [
                            "status" => true,
                            "statusCode" => StatusCodes::OK,
                            "data" => $shopList,
                            "message" => null,
                        ],
                        "statusCode" => StatusCodes::OK
                    ];
                } else
                    throw ExceptionHelper::error([
                        "statusCode" => StatusCodes::NOT_FOUND,
                        "message" => "No shops found"
                    ]);
            } else {
                throw ExceptionHelper::error([
                    "statusCode" => StatusCodes::NOT_FOUND,
                    "message" => "Product Not Found."
                ]);
            }
        } else {

            $sqlShop = Shop::select("*")
                ->where('name', 'like', '%' . $name . '%')
                ->where('status', 1);

            $count = $sqlShop->count();

            if ($count) {

                $loop = 0;
                $shopList = array();

                $sqlShop = $sqlShop
                    ->get()
                    ->toArray();

                foreach ($sqlShop as $sqlShopData) {

                    $shopId = $sqlShopData['id'];

                    if ($sqlShopData['store_status'] == 1) {

                        $sqlWeek = ShopTime::select("*")
                            ->where([
                                "shop_id" => $shopId,
                                "day" => $currentDay
                            ]);

                        $sqlWeelData = $sqlWeek
                            ->first()
                            ?->toArray();

                        if (!$sqlWeelData)
                            throw ExceptionHelper::error([
                                "message" => "shoptime row not found where shopId: $shopId and day:$currentDay"
                            ]);

                        if (
                            $sqlWeelData['time_from'] < $currentTime
                            &&
                            $sqlWeelData['time_to'] > $currentTime
                        ) {
                            $storeStatus = 1;
                        } else {
                            $storeStatus = 0;
                        }
                    } else if ($sqlShopData['store_status'] == 2) {
                        $storeStatus = 0;
                    }

                    $sqlFav = FavShop::where([
                        "user_id" =>  $userId,
                        "shop_id" => $shopId
                    ]);

                    if ($sqlFav->count()) {
                        $favStatus = 1;
                    } else {
                        $favStatus = 0;
                    }

                    $sqlRating = Rating::where([
                        "shop_id" => $shopId
                    ]);

                    $rating      = 0;
                    $ratingCount = 0;

                    if ($sqlRating->count()) {

                        $result = $sqlRating->select(DB::raw("AVG('rating') AS avg"));
                        $row  = $result
                            ->first()
                            ->toArray();

                        $rating = $row['avg'];

                        $result1  = $sqlRating->select(DB::raw("COUNT('rating') AS avg"));
                        $row1  = $result1
                            ->first()
                            ->toArray();

                        $ratingCount = $row1['avg'];
                    }

                    if ($sqlShopData['discount_status'] == 1) {
                        $discountStatus      = $sqlShopData['discount_status'];
                        $discountDiscription = $sqlShopData['discount_discription'] . "% OFF";
                    } else {
                        $discountStatus      = $sqlShopData['discount_status'];
                        $discountDiscription = "";
                    }

                    if (strlen($sqlShopData['address']) > 100) {
                        $address = substr($sqlShopData['address'], 0, 62) . "...";
                    } else {
                        $address = $sqlShopData['address'];
                    }

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

                    if (
                        ($distance <= $sqlHomeData['shop_range'] && $sqlShopData['status'] == 1)
                        ||
                        $sqlShopData['id'] == 46
                    ) {
                        $shopList[] = array(
                            'name' => $sqlShopData['name'],
                            'shopId' => $sqlShopData['id'],
                            'mobile' => $sqlShopData['mobile'],
                            'image' => url("shops") . "/" . $sqlShopData['image'],
                            'address' => $address,
                            'discountStatus' => $discountStatus,
                            'discountDiscription' => $discountDiscription,
                            'favStatus' => $favStatus,
                            'rating' => number_format(($rating), 1),
                            'ratingCount' => '(' . $ratingCount . ' Reviews)',
                            'distance' => $distance . ' KM',
                            'distance2' => $distance,
                            'latitude' => $sqlShopData['latitude'],
                            'longitude' => $sqlShopData['longitude'],
                            'storeStatus' => $storeStatus
                        );

                        $columns = array_column($shopList, 'distance2');
                        array_multisort($columns, SORT_ASC, $shopList);
                        $loop++;
                    }
                }

                if ($loop > 0) {
                    return [
                        "response" => [
                            "status" => true,
                            "statusCode" => StatusCodes::OK,
                            "data" => $shopList,
                            "message" => null,
                        ],
                        "statusCode" => StatusCodes::OK
                    ];
                } else
                    throw ExceptionHelper::error([
                        "statusCode" => StatusCodes::NOT_FOUND,
                        "message" => "No shops found"
                    ]);
            } else {
                throw ExceptionHelper::error([
                    "statusCode" => StatusCodes::NOT_FOUND,
                    "message" => "No shop found"
                ]);
            }
        }
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public static function factorial($name, $shopIds)
    {
        $nameData = explode(" ", $name);
        $altQuery = "";
        $altQuerySingle = "";

        if (count($nameData) > 0) {
            foreach ($nameData as $nD) {
                $altQuery .= "%" . $nD;
            }
            $altQuerySingle = "'" . $altQuery . "%'";
            $altQuery =  "'" . $altQuery . ",%'";
        }


        $sqlProduct1 = Product::select('id', 'name', 'barcode', 'shop_id', 'image', 'weight', 'unit_id', 'price', 'sellingprice')
            ->whereIn('shop_id', $shopIds)
            ->where('name', 'like', $altQuerySingle)
            ->where('status', 1)
            ->groupBy('unique_code')
            ->orderBy('name');

        // Base case: If $n is 0 or 1, return 1
        if ($sqlProduct1->count() > 0) {
            return $sqlProduct1
                ->get()
                ->toArray();
        } else {
            // Recursive case: Call the factorial function with a smaller argument
            if (strlen($name) >= 3) {
                $name1 = substr($name, 0, -1);
                return HomeService::factorial($name1, $shopIds);
            }
        }
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public static function searchList(Request $req)
    {

        $data = RequestValidator::validate(
            $req->input(),
            [],
            [
                "name" => "required|string"
            ]
        );

        $name = $data["name"];
        $name = preg_replace('/\s+/', ' ', $name);
        $date = date('Y-m-d H:i:s');
        $userId = $req->user()->id;

        $nameData       = explode(" ", $name);
        $altQuery       = "";
        $altQuerySingle = "";

        if (count($nameData) > 0) {

            foreach ($nameData as $nD) {
                $altQuery .= "%" . $nD;
            }

            $altQuerySingle = "'" . $altQuery . "%'";
            $altQuery =  "'" . $altQuery . ",%'";
        }

        if (strlen($name) > 2 && $name != "\'\'") {

            $sqlHome = Home::select("shop_range")->first();
            $sqlHomeData = $sqlHome
                ->toArray();

            $sqlUserCity = AddressBook::select('city', 'latitude', 'longitude')
                ->where([
                    "customer_id" => $userId,
                    "default_status" => 1
                ]);

            $userCityData = $sqlUserCity
                ->first()
                ?->toArray();

            if (!$userCityData)
                throw ExceptionHelper::error([
                    "message" => "addressbook row not found with customer_id: $userId and default_status:1"
                ]);

            /* search logs*/
            if (!empty($name) && strlen($name) > 2) {
                UserSearchLog::insert([
                    "user_id" => $userId,
                    "keyword" => $name,
                    "city" => $userCityData['city'],
                    "datetime" => $date
                ]);
            }
            /* search logs*/

            /* active shop ids */
            $shopIds = array();

            $sqlActiveShop = Shop::select("id", "latitude", "longitude")
                ->where([
                    "status" => 1,
                    "store_status" => 1
                ])
                ->get()
                ->toArray();

            foreach ($sqlActiveShop as $activeShopData) {
                $distance = UtilityHelper::getDistanceBetweenPlaces(
                    [
                        "lat" => $userCityData["latitude"],
                        "long" => $userCityData["longitude"],
                    ],
                    [
                        "lat" => $activeShopData['latitude'],
                        "long" => $activeShopData['longitude']
                    ]
                );

                if ($distance <= $sqlHomeData['shop_range']) {
                    $shopIds[] = ($activeShopData['id']);
                }
            }

            $shopIds = implode(",", $shopIds);

            /* active shop ids */

            if (strlen($shopIds) > 0) {

                $sqlProduct =  Product::select('id', 'name', 'barcode', 'shop_id', 'image', 'weight', 'unit_id', 'price', 'sellingprice')
                    ->whereIn('shop_id', $shopIds)
                    ->where('keywords', 'like', $altQuery)
                    ->where('status', 1)
                    ->groupBy('unique_code')
                    ->orderBy('name');

                UtilityHelper::enableSqlStrictMode();

                $count = $sqlProduct->count();

                UtilityHelper::disableSqlStrictMode();

                if ($count > 0) {

                    $loop = 0;
                    $productlist = array();

                    UtilityHelper::enableSqlStrictMode();

                    $sqlProduct = $sqlProduct
                        ->get()
                        ->toArray();

                    UtilityHelper::disableSqlStrictMode();

                    foreach ($sqlProduct as $sqlProductData) {

                        $productImage = "";
                        $directory = '../products/' . $sqlProductData['barcode'] . '/';
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

                        $sqlCart = Cart::where('shop_id', $sqlProductData['shop_id'])
                            ->where('user_id', $userId)
                            ->where('product_id', $sqlProductData['id']);

                        if ($sqlCart->count() == 0) {
                            $added  = 0;
                        } else {
                            $added  = 1;
                        }

                        // Added

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

                        array_push(
                            $unitList,
                            array(
                                'variantId' => $sqlProductData['id'],
                                'weight' => $sqlProductData['weight'] . " " . CommonHelper::uomName($sqlProductData['unit_id']),
                                "price" => round($price),
                                "sellingPrice" => round($sqlProductData['sellingprice']),
                                "discount" => $discount
                            )
                        );

                        $productlist[] = array(
                            "added" => $added,
                            "productId" => $sqlProductData['id'],
                            "shopId" => $sqlProductData['shop_id'],
                            "shopName" => CommonHelper::shopName($sqlProductData['shop_id']),
                            "productName" => ucwords(
                                ucfirst(
                                    strtolower(
                                        mb_convert_encoding(
                                            $sqlProductData['name'],
                                            'UTF-8'
                                        )
                                    )
                                )
                            ),
                            "barcode" => $sqlProductData['barcode'],
                            "productImage" => url("/products") . "/" . $sqlProductData['barcode'] . '/' . $productImage,
                            "unit" => $unitList
                        );

                        $loop++;
                    }

                    if ($loop > 0) {

                        return [
                            "response" => [
                                "status" => true,
                                "statusCode" => StatusCodes::OK,
                                "data" => [
                                    "count" => $count . " Product(s) Found",
                                    "productList" => $productlist
                                ],
                                "message" => null,
                            ],
                            "statusCode" => StatusCodes::OK
                        ];
                    } else {
                        throw ExceptionHelper::error([
                            "statusCode" => StatusCodes::NOT_FOUND,
                            "message" => "No Products"
                        ]);
                    }
                } else {

                    $sqlProduct = Product::select('id', 'name', 'barcode', 'shop_id', 'image', 'weight', 'unit_id', 'price', 'sellingprice')
                        ->whereIn('shop_id', $shopIds)
                        ->where('name', 'like', $altQuerySingle)
                        ->where('status', 1)
                        ->groupBy('unique_code')
                        ->orderBy('name');

                    $count = $sqlProduct->count();

                    if ($count == 0) {

                        // Example usage
                        $name = substr($name, 0, -1);

                        if (strlen($name) >= 3) {
                            $sqlProduct = HomeService::factorial($name, $shopIds); // Calculates 5!
                            $count      = mysqli_num_rows($sqlProduct);
                            //$count = 1;
                        }
                    }

                    if ($count > 0) {

                        $loop = 0;
                        $productlist = array();

                        $sqlProduct = $sqlProduct
                            ->get()
                            ->toArray();

                        foreach ($sqlProduct as $sqlProductData) {

                            $productImage = "";
                            $directory = '../products/' . $sqlProductData['barcode'] . '/';
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

                            $sqlCart = Cart::where('shop_id', $sqlProductData['shop_id'])
                                ->where('user_id', $userId)
                                ->where('product_id', $sqlProductData['id'])
                                ->count();

                            $added = ($sqlCart == 0) ? 0 : 1;

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

                            array_push(
                                $unitList,
                                array(
                                    'variantId' => $sqlProductData['id'],
                                    'weight' => $sqlProductData['weight'] . " " . CommonHelper::uomName($sqlProductData['unit_id']),
                                    "price" => round($price),
                                    "sellingPrice" => round($sqlProductData['sellingprice']),
                                    "discount" => $discount
                                )
                            );

                            $productlist[] = array(
                                "added" => $added,
                                "productId" => $sqlProductData['id'],
                                "shopId" => $sqlProductData['shop_id'],
                                "shopName" => CommonHelper::shopName($sqlProductData['shop_id']),
                                "productName" => ucwords(
                                    ucfirst(
                                        strtolower(
                                            mb_convert_encoding(
                                                $sqlProductData['name'],
                                                'UTF-8'
                                            )
                                        )
                                    )
                                ),
                                "barcode" => $sqlProductData['barcode'],
                                "productImage" => url("/products") . "/" . $sqlProductData['barcode'] . '/' . $productImage,
                                "unit" => $unitList
                            );

                            $loop++;

                            if ($loop > 0) {

                                return [
                                    "response" => [
                                        "status" => true,
                                        "statusCode" => StatusCodes::OK,
                                        "data" => [
                                            "count" => $count . " Product(s) Found",
                                            "productList" => $productlist
                                        ],
                                        "message" => null,
                                    ],
                                    "statusCode" => StatusCodes::OK
                                ];
                            } else {
                                throw ExceptionHelper::error([
                                    "statusCode" => StatusCodes::NOT_FOUND,
                                    "message" => "No Products"
                                ]);
                            }
                        }
                    } else {
                        throw ExceptionHelper::error([
                            "statusCode" => StatusCodes::NOT_FOUND,
                            "message" => "Product Not Found."
                        ]);
                    }
                }
            } else {
                throw ExceptionHelper::error([
                    "statusCode" => StatusCodes::NOT_FOUND,
                    "message" => "There are no Seller in Your Area"
                ]);
            }
        } else {
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::NOT_FOUND,
                "message" => "Please Enter Atleast 3 Character"
            ]);
        }
    }
}
