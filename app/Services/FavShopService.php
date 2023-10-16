<?php

namespace App\Services;

use App\Constants\StatusCodes;
use App\Helpers\ExceptionHelper;
use App\Helpers\RequestValidator;
use App\Helpers\UtilityHelper;
use App\Models\FavShop;
use App\Models\Rating;
use App\Models\Shop;
use App\Models\ShopTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @todo Document this
 */
class FavShopService
{

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function addFavShop(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [
                'exists' => 'shop with provided id doesn\'t exist'
            ],
            [
                "shopId" => "required|exists:shop,id",
            ]
        );

        $userId = $req->user()->id;
        $shopId = $data['shopId'];
        $currentTime = date('Y-m-d H:i:s');

        $alreadyAdded = FavShop::select("*")
            ->where([
                "shop_id" => $shopId,
                "user_id" => $userId
            ])
            ->count() > 1;

        if ($alreadyAdded)
            throw ExceptionHelper::unAuthorized([
                "message" => "Already added to favourite."
            ]);

        $inserted = FavShop::create([
            "shop_id" => $shopId,
            "user_id" => $userId,
            "datetime" => $currentTime
        ]);

        if (!$inserted)
            throw ExceptionHelper::somethingWentWrong([
                "message" => "Already added to favourite."
            ]);

        return [
            "response" => [
                "data" => null,
                "status" =>  true,
                "statusCode" => StatusCodes::OK,
                "messsage" => "Added To favourite Shops."
            ],
            "statusCode" => StatusCodes::OK
        ];
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function removeFav(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [
                'exists' => 'shop with provided id doesn\'t exist'
            ],
            [
                "shopId" => "required|exists:shop,id",
            ]
        );

        $userId = $req->user()->id;
        $shopId = $data['shopId'];

        $deleted = FavShop::where([
            "shop_id" => $shopId,
            "user_id" => $userId
        ])->delete();

        if (!$deleted)
            throw ExceptionHelper::somethingWentWrong();

        return [
            "response" => [
                "data" => null,
                "status" =>  true,
                "statusCode" => StatusCodes::OK,
                "messsage" => "Removed From favourite."
            ],
            "statusCode" => StatusCodes::OK
        ];
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function favShopList(Request $req)
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

        $currentTime = date('H:i:s');
        $currentDay  = date('l');
        $userId = $req->user()->id;
        $latitude = $data['latitude'];
        $longitude = $data['longitude'];

        $sql = FavShop::select("shop_id")
            ->where("user_id", $userId);

        $shopList = array();

        if ($sql->count() > 0) {

            $sql = $sql
                ->get()
                ->toArray();

            foreach ($sql as $row) {

                $address = "";
                $discountStatus = "";
                $discountDiscription = "";

                $sqlShop = Shop::select("id", "name", "mobile", "email", "address", "image", "store_status", "discount_status", "discount_discription", "latitude", "longitude", "delivery_time")
                    ->where([
                        "id" => $row['shop_id'],
                        "status" => 1
                    ]);

                if ($sqlShop->count() > 0) {

                    $data = $sqlShop
                        ->first()
                        ->toArray();

                    if ($data['store_status'] == 1) {

                        $sqlWeek = ShopTime::select("time_from", "time_to")
                            ->where([
                                "shop_id" => $data['id'],
                                "day" => $currentDay
                            ]);

                        $shopId = $data['id'];

                        $sqlWeelData = $sqlWeek
                            ->first()
                            ?->toArray();

                        if (!$sqlWeelData)
                            throw ExceptionHelper::error([
                                "message" => "shop_time row not found where shop_id: $shopId and day: $currentDay"
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
                    } else if ($data['store_status'] == 2) {
                        $storeStatus = 0;
                    }
                    if ($data['discount_status'] == 1) {
                        $discountStatus = $data['discount_status'];
                        $discountDiscription = $data['discount_discription'] . "% OFF";
                    } else {
                        $discountStatus = $data['discount_status'];
                        $discountDiscription = "";
                    }

                    $sqlRating = Rating::select("id")
                        ->where("shop_id", $row['shop_id']);

                    $rating = 0;
                    $ratingCount = 0;

                    if ($sqlRating->count() > 0) {

                        $result = Rating::select(DB::raw("AVG('rating') as avg"))
                            ->where("shop_id", $row['shop_id']);

                        $row1 = $result
                            ->first()
                            ->toArray();

                        $rating = $row1['avg'];

                        $result1 = Rating::select(DB::raw("COUNT('rating') as avg"))
                            ->where("shop_id", $row['shop_id']);

                        $row2 = $result1
                            ->first()
                            ->toArray();

                        $ratingCount = $row2['avg'];
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

                    if (strlen($data['address']) > 100) {
                        $address = substr($data['address'], 0, 62) . "...";
                    } else {
                        $address = $data['address'];
                    }

                    $shopList[] = array(
                        'shopId' => $row['shop_id'],
                        'name' => $data['name'],
                        'mobile' => $data['mobile'],
                        'image' => url("/shops") . "/" . $data['image'],
                        'address' => $address,
                        'discountStatus' => $discountStatus,
                        'discountDiscription' => $discountDiscription,
                        'favStatus' => 1,
                        'rating' => number_format(($rating), 1),
                        'ratingCount' => '(' . $ratingCount . ' Reviews)',
                        'distance' => $distance . ' km',
                        'distance2' => $distance,
                        'latitude' => $data['latitude'],
                        'longitude' => $data['longitude'],
                        'status' => $storeStatus,
                        'storeStatus' => $storeStatus
                    );

                    $columns = array_column($shopList, 'distance2');
                    array_multisort($columns, SORT_ASC, $shopList);

                    return [
                        "response" => [
                            "status" => true,
                            "statusCode" => StatusCodes::OK,
                            "data" => [
                                "shopList" => $shopList
                            ],
                            "message" => null,
                        ],
                        "statusCode" => StatusCodes::OK
                    ];
                }
            }

            if (count($shopList) == 0) {
                throw ExceptionHelper::error([
                    "statusCode" => StatusCodes::NOT_FOUND,
                    "message" => "Follow Store you love"
                ]);
            }
        } else {
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::NOT_FOUND,
                "message" => "Follow Store you love"
            ]);
        }
    }
}
