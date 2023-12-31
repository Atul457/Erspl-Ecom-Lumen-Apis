<?php

namespace App\Services;

use App\Constants\StatusCodes;
use App\Helpers\CommonHelper;
use App\Helpers\ExceptionHelper;
use App\Helpers\RequestValidator;
use App\Helpers\ResponseGenerator;
use App\Helpers\UtilityHelper;
use App\Models\ACategory;
use App\Models\Cart;
use App\Models\FavShop;
use App\Models\Home;
use App\Models\Product;
use App\Models\Rating;
use App\Models\Shop;
use App\Models\ShopTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @todo Document this
 */
class ShopService
{

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function shopList(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [
                'exists' => 'category with provided id doesn\'t exist',
                'numeric' => ':attribute must be a number'
            ],
            [
                "categoryId" => "required|exists:tbl_acategory,id",
                "latitude" => "required|numeric",
                "longitude" => "required|numeric",
            ]
        );

        $currentTime = date('H:i:s');
        $currentDay = date('l');
        $userId = $req->user()->id;
        $categoryId = $data['categoryId'];
        $latitude = $data['latitude'];
        $longitude = $data['longitude'];

        $shops = Product::select("shop_id")
            ->distinct("shop_id")
            ->where([
                "status" =>  1,
                "category_id" =>  $categoryId,
            ])
            ->get()
            ->toArray();

        $sqlHomeData = Home::select("shop_range")
            ->first();

        if ($shops > 0) {
            $loop = 0;

            foreach ($shops as $shop) {
                $baseQuery = Shop::select("id", "name", "mobile", "email", "address", "image", "store_status", "discount_status", "discount_discription", "latitude", "longitude", "delivery_time")
                    ->where([
                        "status" => 1,
                        "id" => $shop["shop_id"],
                    ]);

                $count = $baseQuery->count();

                if ($count > 0) {
                    $data = $baseQuery
                        ->first()
                        ->toArray();

                    if ($data['store_status'] == 1) {

                        $exists = true;
                        $sqlWeelData = ShopTime::select("time_from", "time_to")
                            ->where([
                                "shop_id" => $data['id'],
                                "day" => $currentDay
                            ])
                            ->first();

                        if ($sqlWeelData !== null)
                            $sqlWeelData = $sqlWeelData->toArray();
                        else
                            $exists = false;

                        if ($exists && $sqlWeelData['time_from'] < $currentTime && $sqlWeelData['time_to'] > $currentTime)
                            $storeStatus = 1;
                        else
                            $storeStatus = 0;
                    } else if ($data['store_status'] == 2)
                        $storeStatus = 0;


                    $sqlFav = FavShop::select("id")
                        ->where([
                            "user_id" => $userId,
                            "shop_id" => $data['id']
                        ])
                        ->count();

                    if ($sqlFav)
                        $favStatus = 1;
                    else
                        $favStatus = 0;

                    $sqlRating = Rating::select("id")
                        ->where("shop_id", $data['id'])
                        ->count();

                    $rating = 0;
                    $ratingCount = 0;

                    if ($sqlRating > 0) {
                        $row = Rating::select(DB::raw("AVG(rating) as avg"))
                            ->where("shop_id", $data['id'])
                            ->first();

                        $row1 = Rating::select(DB::raw("COUNT(rating) as avg"))
                            ->where("shop_id", $data['id'])
                            ->first();

                        $rating = $row['avg'];
                        $ratingCount = $row1['avg'];
                    }

                    if ($data['discount_status'] == 1) {
                        $discountStatus = $data['discount_status'];
                        $discountDiscription = $data['discount_discription'] . "% OFF";
                    } else {
                        $discountStatus = $data['discount_status'];
                        $discountDiscription = "";
                    }

                    if (strlen($data['address']) > 100)
                        $address = substr($data['address'], 0, 62) . "...";
                    else
                        $address = $data['address'];

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

                    if ($distance <= $sqlHomeData['shop_range'] || $data['id'] == 46) {
                        $shopList[] = array(
                            'name' => $data['name'],
                            'shopId' => $data['id'],
                            'mobile' => $data['mobile'],
                            'email' => $data['email'],
                            'image' => url("shops/") . "/" . $data['image'],
                            'address' => $address,
                            'deliveryTime' => $data['delivery_time'],
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

                        $columns = array_column(
                            $shopList,
                            'distance2'
                        );

                        array_multisort(
                            $columns,
                            SORT_ASC,
                            $shopList
                        );
                        $loop++;
                    }
                }
            }

            if (!$loop)
                throw ExceptionHelper::error([
                    "statusCode" => StatusCodes::NOT_FOUND,
                    "message" => "Sorry, we couldn't find any store in your area."
                ]);

            return ResponseGenerator::generateResponseWithStatusCode(
                ResponseGenerator::generateSuccessResponse([
                    "data" => [
                        "shopList" => $shopList
                    ]
                ])
            );
        }

        throw ExceptionHelper::error([
            "statusCode" => StatusCodes::NOT_FOUND,
            "message" => "Sorry, we couldn't find any store what you're looking for."
        ]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function shopDetail(Request $req)
    {

        $urlToPrepend = url('categorys/');
        $shopUrlToPrepend = url('shops/');

        $data = RequestValidator::validate(
            $req->input(),
            [
                'exists' => 'shop with provided id doesn\'t exist',
                'numeric' => ':attribute must be a number'
            ],
            [
                "shopId" => "required|exists:tbl_shop,id",
                "latitude" => "required|numeric",
                "longitude" => "required|numeric",
            ]
        );

        $userId    = $req->user()->id;
        $shopId    = $data['shopId'];
        $latitude  = $data['latitude'];
        $longitude = $data['longitude'];

        $homeData  = Home::select("delivery_type")->first();
        $data_ = Shop::select("id", "name", "mobile", "email", "address", "image", "store_status", "latitude", "longitude")
            ->where("id", $shopId)
            ->first();

        if (!$data_)
            $data_ = [];
        else
            $data_ = $data_->toArray();

        $sqlFav = FavShop::select("id")
            ->where([
                "user_id" => $userId,
                "shop_id" => $shopId,
            ])
            ->get()
            ->toArray();

        if (count($sqlFav))
            $favStatus = 1;
        else
            $favStatus = 0;

        $sqlRating = Rating::select("id")
            ->where("shop_id", $shopId)
            ->get()
            ->toArray();

        $rating = 0;
        $row = null;
        $row1 = null;
        $ratingCount = 0;

        if (count($sqlRating)) {

            $row = Rating::select(DB::raw("AVG(rating) as avg"))
                ->where("shop_id", $shopId)
                ->get()
                ->toArray();
            $rating = $row[0]['avg'];

            $row1 = Rating::select(DB::raw("COUNT(rating) as avg"))
                ->where("shop_id", $shopId)
                ->get()
                ->toArray();
            $ratingCount = $row1[0]['avg'];
        }

        $distance = UtilityHelper::getDistanceBetweenPlaces(
            [
                "lat" => $latitude,
                "long" => $longitude,
            ],
            [
                "lat" => $data_['latitude'],
                "long" => $data_['longitude']
            ]
        );

        if (strlen($data_['address']) > 100)
            $address = substr($data_['address'], 0, 62) . "...";
        else
            $address = $data_['address'];

        $shopCategory = Product::select("tbl_product.category_id")
            ->where([
                "tbl_product.shop_id" =>  $shopId,
                "tbl_product.status" => 1
            ])
            ->groupBy("tbl_product.category_id")
            ->get()
            ->toArray();

        $loop = 0;

        while ($loop < count($shopCategory)) {
            $sqlCategoryData = ACategory::select("id as categoryId", "name as categoryName",   DB::raw("CONCAT('$urlToPrepend', icon) as categoryIcon"), "category_order as orderC")
                ->get()
                ->toArray();
            $columns    = array_column($sqlCategoryData, 'orderC');
            array_multisort($columns, SORT_ASC, $sqlCategoryData);
            $loop++;
        }


        if ($homeData['delivery_type'] == 1)
            $delivery = "Free Delivery";
        else
            $delivery = "";

        $productCount = Product::select("id")
            ->where([
                "shop_id" => $shopId,
                "status" => 1
            ])
            ->count();

        $commonData = [
            'name' => $data_['name'],
            'shopId' => $data_['id'],
            'mobile' => $data_['mobile'],
            'image' => $shopUrlToPrepend . $data_['image'],
            'address' => $address,
            'favStatus' => $favStatus,
            'rating' => number_format(($rating), 1),
            'ratingCount' => '(' . $ratingCount . ' Reviews)',
            'distance' => $distance . ' KM',
            'distance2' => $distance,
            'latitude' => $data_['latitude'],
            'longitude' => $data_['longitude'],
            'delivery' => $delivery
        ];

        if (!$loop)
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::NOT_FOUND,
                "data" => array_merge(
                    $commonData,
                    ['productCount' => "0 Products"],
                )
            ]);

        $arr = array_merge(
            $commonData,
            [
                'productCount' => $productCount . " Products",
                'categoryList' => $sqlCategoryData
            ]
        );

        return ResponseGenerator::generateResponseWithStatusCode(
            ResponseGenerator::generateSuccessResponse([
                "data" => [
                    "data" => $arr
                ],
            ])
        );
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function nearestShopList(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [
                'numeric' => ':attribute must be a number'
            ],
            [
                "latitude" => "required|numeric",
                "longitude" => "required|numeric",
            ]
        );

        $userId    = $req->user()->id;
        $latitude  = $data['latitude'];
        $longitude = $data['longitude'];
        $currentDay   = date('l');
        $currentTime  = date('H:i:s');


        $sqlHomeData = Home::select("*")->first();
        $shops = Shop::select("*")
            ->where("status", 1)
            ->get()
            ->toArray();

        if (!count($shops))
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::NOT_FOUND,
                "message" => "Sorry, we couldn't find any store what you're looking for."
            ]);

        $loop = 0;
        $exists = true;
        $matchCount = 0;

        while ($loop < count($shops)) {

            if ($shops[$loop]['store_status'] == 1) {

                $sqlWeelData = ShopTime::where([
                    "shop_id" => $shops[$loop]["id"],
                    "day" => $currentDay
                ])->first();

                if ($sqlWeelData !== null)
                    $sqlWeelData = $sqlWeelData->toArray();
                else
                    $exists = false;

                if (
                    $exists &&
                    $sqlWeelData['time_from'] < $currentTime &&
                    $sqlWeelData['time_to'] > $currentTime
                )
                    $storeStatus = 1;
                else
                    $storeStatus = 0;
            } else if ($shops[$loop]['store_status'] == 2)
                $storeStatus = 0;

            $sqlFav = FavShop::select("*")
                ->where([
                    "shop_id" => $shops[$loop]["id"],
                    "user_id" => $userId
                ])
                ->get()
                ->toArray();

            if (count($sqlFav))
                $favStatus = 1;
            else
                $favStatus = 0;

            $sqlRating = Rating::select("*")
                ->where("shop_id", $shops[$loop]["id"])
                ->get()
                ->toArray();

            if (count($sqlRating)) {

                $row = Rating::select(DB::raw("AVG(rating) as avg"))
                    ->where("shop_id",  $shops[$loop]["id"])
                    ->first();

                $row1 = Rating::select(DB::raw("COUNT(rating) as avg"))
                    ->where("shop_id",  $shops[$loop]["id"])
                    ->first();

                $rating = $row['avg'];
                $ratingCount = $row1['avg'];
            }

            if ($shops[$loop]['discount_status'] == 1) {
                $discountStatus = $shops[$loop]['discount_status'];
                $discountDiscription = $shops[$loop]['discount_discription'] . "% OFF";
            } else {
                $discountStatus = $shops[$loop]['discount_status'];
                $discountDiscription = "";
            }

            $distance = UtilityHelper::getDistanceBetweenPlaces(
                [
                    "lat" => $latitude,
                    "long" => $longitude,
                ],
                [
                    "lat" =>  $shops[$loop]['latitude'],
                    "long" =>  $shops[$loop]['longitude']
                ]
            );

            if (strlen($shops[$loop]['address']) > 100)
                $address = substr($shops[$loop]['address'], 0, 62) . "...";
            else
                $address =  $shops[$loop]['address'];

            if ($distance <= $sqlHomeData['shop_range']) {
                $shopList[] = array(
                    'name' =>  $shops[$loop]['name'],
                    'shopId' =>  $shops[$loop]['id'],
                    'mobile' =>  $shops[$loop]['mobile'],
                    'image' => url("shops/") . "/" .  $shops[$loop]['image'],
                    'address' => $address,
                    'discountStatus' => $discountStatus,
                    'discountDiscription' => $discountDiscription,
                    'favStatus' => $favStatus,
                    'rating' => number_format(($rating), 1),
                    'ratingCount' => '(' . $ratingCount . ')',
                    'distance' => $distance . ' km',
                    'distance2' => $distance,
                    'latitude' =>  $shops[$loop]['latitude'],
                    'longitude' =>  $shops[$loop]['longitude'],
                    'status' => $storeStatus
                );
                $columns    = array_column($shopList, 'distance2');
                array_multisort($columns, SORT_ASC, $shopList);
                $matchCount++;
            }
            $loop++;
        }

        if (!$matchCount)
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::NOT_FOUND,
                "message" => "Sorry, we couldn't find any store what you're looking for."
            ]);

        return ResponseGenerator::generateResponseWithStatusCode(
            ResponseGenerator::generateSuccessResponse([
                "data" => [
                    "shopList" => $shopList
                ],
            ])
        );
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function searchShopDetail(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [
                'numeric' => ':attribute must be a number'
            ],
            [
                "shopId" => "required|numeric",
                "search" => "required"
            ]
        );

        $shopId = $data['shopId'];
        $search = $data['search'];

        $sqlProductData = Product::select("id")
            ->where([
                "shop_id" => $shopId
            ])
            ->where(function ($query) use ($search) {
                $query->where("keywords", "LIKE", "%$search %")
                    ->orWhere("keywords", "LIKE", "% $search%")
                    ->orWhere("keywords", "LIKE", "% $search,%")
                    ->orWhere("keywords", "LIKE", "%,$search%");
            })
            ->first();

        if (!$sqlProductData)
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::NOT_FOUND,
                "message" => "Product Not Found."
            ]);

        return ResponseGenerator::generateResponseWithStatusCode(
            ResponseGenerator::generateSuccessResponse([
                "data" => [
                    "productId" => $sqlProductData["id"]
                ],
            ])
        );
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function searchProductList(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [
                'shopId.exists' => 'Product List Not Found.'
            ],
            [
                "shopId" => "required|exists:tbl_product,shop_id",
            ]
        );

        $shopId = $data['shopId'];

        $sqlProduct = Product::select("*")
            ->where([
                "shop_id" => $shopId,
                "status" => 1
            ])
            ->get()
            ->toArray();

        $productlist = array();

        foreach ($sqlProduct as $sqlProductData) {
            $productlist[] = array(
                "productId" => $sqlProductData['id'],
                "productName" => mb_convert_encoding($sqlProductData['name'], 'UTF-8')
            );
        }

        return ResponseGenerator::generateResponseWithStatusCode(
            ResponseGenerator::generateSuccessResponse([
                "data" => [
                    "categorylist" => $productlist
                ],
            ])
        );
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function checkDistance(Request $req)
    {
        $data = $req->input();
        $arr = array();

        $userId = $req->user()->id;
        $latitude    = $data['latitude'] ?? "";
        $longitude   = $data['longitude'] ?? "";
        $currentTime = date('H:i:s');
        $currentDay  = date('l');

        $sqlActualCart = Cart::where('user_id', $userId)->get();
        $sqlActualCartCount = $sqlActualCart->count();

        $sqlActualActiveCart = Cart::where('user_id', $userId)
            ->whereIn('product_id', Product::where('status', 1)->pluck('id'));
        $sqlActualActiveCartCount = $sqlActualActiveCart->count();

        if (($sqlActualCartCount == $sqlActualActiveCartCount) && $sqlActualActiveCartCount) {

            $dStatus = 1;
            $sStatus = 1;
            $i = 1;

            $sqlhome = Home::select('shop_range')->first();
            $sqlhomeData = $sqlhome ? $sqlhome->toArray() : [];

            $sqlCart = Cart::where('user_id', $userId)
                ->select('shop_id')
                ->groupBy('shop_id')
                ->get();
            $count = $sqlCart->count();

            while (($dStatus == 1 && $sStatus == 1) && $i <= $count) {

                $sqlCartData = Cart::select('shop_id')->where('user_id', $userId)->first();
                $sqlShopData = Shop::select('id', 'name', 'status', 'store_status', 'latitude', 'longitude')
                    ->where('id', $sqlCartData['shop_id'])
                    ->first();
                $sqlTimeData = ShopTime::select('time_from', 'time_to', 'status')
                    ->where('shop_id', $sqlCartData['shop_id'])
                    ->where('day', $currentDay)
                    ->first();

                if (!empty($latitude) && !empty($longitude)) {

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
                        $distance <= $sqlhomeData['shop_range']
                        ||
                        $sqlShopData['id'] == 46
                    ) {
                        $arr = array('status' => true, 'msg' => "Address Changed");
                        $dStatus = 1;
                    } else {
                        $arr = array('status' => false, 'msg' => $sqlShopData['name'] . " Does not deliver to this location", 'msg1' => "Location Unserviceable");
                        $dStatus = 0;
                    }
                }

                if ($dStatus == 1) {
                    if ($sqlShopData['status'] == 2 || $sqlShopData['status'] == 3) {
                        $sStatus = 0;
                        $arr = array('status' => false, 'msg' => $sqlShopData['name'] . " is Currently Not Available", 'msg1' => "Shop Not Available");
                    } else {
                        if ($sqlTimeData['status'] == 0) {
                            $sStatus = 0;
                            $arr = array('status' => false, 'msg' => $sqlShopData['name'] . " is Temporarily Closed", 'msg1' => "Shop Unserviceable");
                        } else if ($sqlShopData['status'] == 1 && $sqlShopData['store_status'] == 1 && $sqlTimeData['time_from'] < $currentTime && $sqlTimeData['time_to'] > $currentTime) {
                            $sStatus = 1;
                            $arr = array('status' => true, 'msg' => "Open");
                        } else if ($sqlShopData['status'] == 1 && $sqlShopData['store_status'] == 1 && $sqlTimeData['time_from'] < $currentTime && $sqlTimeData['time_to'] < $currentTime) {
                            $sStatus = 0;
                            $arr = array('status' => false, 'msg' => $sqlShopData['name'] . " accepts orders\n between " . date('h:i A', strtotime($sqlTimeData['time_from'])) . " To " . date('h:i A', strtotime($sqlTimeData['time_to'])), 'msg1' => "Shop Unserviceable");
                        } else if ($sqlShopData['status'] == 1 && $sqlShopData['store_status'] == 1 && $sqlTimeData['time_from'] > $currentTime && $sqlTimeData['time_to'] > $currentTime) {
                            $sStatus = 0;
                            $arr = array('status' => false, 'msg' => $sqlShopData['name'] . " accepts orders\n between " . date('h:i A', strtotime($sqlTimeData['time_from'])) . " To " . date('h:i A', strtotime($sqlTimeData['time_to'])), 'msg1' => "Shop Unserviceable");
                        } else if ($sqlShopData['status'] == 1 && $sqlShopData['store_status'] == 2) {
                            $sStatus = 0;
                            $arr = array('status' => false, 'msg' => $sqlShopData['name'] . " is Temporarily Closed", 'msg1' => "Shop Unserviceable");
                        }
                    }
                }
                $i++;
            }
        } else {
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::BAD_REQUEST,
                "message" => "Some Products are Out of Stock",
                "data" => [
                    "msg1" => "Products Out Of Stock"
                ]
            ]);
        }

        if (!$arr["status"])
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::BAD_REQUEST,
                "message" => $arr["msg"],
                "data" => [
                    "msg1" => $arr["msg"]
                ]
            ]);

        return ResponseGenerator::generateResponseWithStatusCode(
            ResponseGenerator::generateSuccessResponse([
                "message" => $arr["msg"],
                "data" => [
                    "msg1" => $arr["msg"]
                ]
            ])
        );
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function shopReviewList(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [
                'shopId.exists' => "shop with provided id doesn\'t exist",
            ],
            [
                "shopId" => "required|exists:tbl_shop,id",
            ]
        );

        $shopId  = $_POST['shopId'];
        $count = 0;

        $sql  = Rating::select("*")
            ->where("shop_id", $shopId)
            ->get()
            ->toArray();

        foreach ($sql as $sqlData) {
            if ($sqlData['review'] == NULL) {
                $review = "";
            } else {
                $review = $sqlData['review'];
            }
            $reviewList[]   = array(
                "id" => $sqlData['id'],
                "name" => CommonHelper::customerNameWeb($sqlData['user_id']),
                "rating" => number_format($sqlData['rating'], 1),
                "review" => $review,
                "date" => date(
                    'd-m-Y',
                    strtotime($sqlData['date'])
                )
            );
            $count++;
        }

        if ($count > 0) {
            return ResponseGenerator::generateResponseWithStatusCode(
                ResponseGenerator::generateSuccessResponse([
                    "data" => [
                        "reviewList" => $reviewList
                    ],
                ])
            );
        } else {
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::NOT_FOUND,
                "message" => "No Reviews"
            ]);
        }
    }
}
