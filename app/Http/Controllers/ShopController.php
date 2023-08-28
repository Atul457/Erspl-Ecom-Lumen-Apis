<?php

namespace App\Http\Controllers;

use App\Helpers\ExceptionHelper;
use App\Helpers\RequestValidator;
use App\Helpers\UtilityHelper;
use App\Models\FavShop;
use App\Models\Home;
use App\Models\Product;
use App\Models\Rating;
use App\Models\Shop;
use App\Models\ShopTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShopController extends Controller
{

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function shopList(Request $req)
    {

        try {

            $data = RequestValidator::validate(
                $req->input(),
                [
                    'exists' => 'category with provided id doesn\'t exist',
                    'numeric' => ':attribute must be a number'
                ],
                [
                    "categoryId" => "required|exists:acategory,id",
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
                    throw ExceptionHelper::nonFound([
                        "message" => "Sorry, we couldn't find any store in your area."
                    ]);

                return response([
                    "data" => [
                        "shopList" => $shopList
                    ],
                    "status" =>  true,
                    "statusCode" => 200,
                    "messsage" => null
                ], 200);
            }

            throw ExceptionHelper::nonFound([
                "message" => "Sorry, we couldn't find any store what you're looking for."
            ]);
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
                "message" => $e->getMessage(),
                "statusCode" => $e->statusCode,
            ], $e->statusCode);
        }
    }

}
