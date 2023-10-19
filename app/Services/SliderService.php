<?php

namespace App\Services;

use App\Constants\StatusCodes;
use App\Helpers\CommonHelper;
use App\Helpers\ExceptionHelper;
use App\Helpers\RequestValidator;
use App\Helpers\UtilityHelper;
use App\Models\Home;
use App\Models\HsnCode;
use App\Models\OfferBundling;
use App\Models\OfferPriceBundling;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Slider;
use App\Models\SliderProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @todo Document this
 */
class SliderService
{

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function sliderList()
    {
        $prefix = url("/slider");

        $sliderList = Slider::select(DB::raw("CONCAT('$prefix', slider) as image"), "link")
            ->where("status", 1)
            ->orderBy("sort_order")
            ->get()
            ->toArray();

        if (!count($sliderList))
            throw ExceptionHelper::notFound([
                "message" => "Slider list not found."
            ]);

        return [
            "response" => [
                "data" => [
                    "sliderList" => $sliderList
                ],
                "status" =>  true,
                "statusCode" => StatusCodes::OK,
                "messsage" => null
            ],
            "statusCode" => StatusCodes::OK
        ];
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function bannerList(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [
                'numeric' => ':attribute must contain only numbers'
            ],
            [
                "latitude" => "numeric",
                "longitude" => "numeric",
            ]
        );

        $bannerList = array();
        $latitude  = $data['latitude'];
        $longitude = $data['longitude'];

        $sqlHomeData = Home::select('shop_range')->first();
        $sqlBanner = Slider::select('id', 'shop_id', 'slider')
            ->where('status', 1)
            ->orderBy('sort_order');

        $count = $sqlBanner->count();

        if ($count > 0) {

            $loop = 0;

            $sqlBanner = $sqlBanner
                ->get()
                ->toArray();

            foreach ($sqlBanner as $bannerData) {

                if (!empty($bannerData['shop_id'])) {

                    $shopData = Shop::select('latitude', 'longitude')
                        ->where('id', $bannerData['shop_id'])
                        ->where('status', 1)
                        ->first();

                    if (!$shopData)
                        throw ExceptionHelper::error([
                            "message" => "shop row not found where id: " . $bannerData["shop_id"] . " and status: 1"
                        ]);

                    $sqlSliderProducts = SliderProduct::select('id')
                        ->where('slider_id', $bannerData['id'])
                        ->get()
                        ->toArray();

                    $distance = UtilityHelper::getDistanceBetweenPlaces(
                        [
                            "lat" => $latitude,
                            "long" => $longitude,
                        ],
                        [
                            "lat" => $shopData['latitude'],
                            "long" => $shopData['longitude']
                        ]
                    );

                    if (
                        $distance <= $sqlHomeData['shop_range']
                        &&
                        $sqlSliderProducts > 0
                    ) {
                        $bannerList[] = array(
                            'id' => $bannerData['id'],
                            'shopId' => $bannerData['shop_id'],
                            'image' => url("slider") . "/" . $bannerData['slider'],
                            'clickStatus' => 1
                        );

                        $loop++;
                    }
                } else {
                    $bannerList[] = array(
                        'id' => $bannerData['id'],
                        'shopId' => "",
                        'image' => url("slider") . "/" . $bannerData['slider'],
                        'clickStatus' => 2
                    );

                    $loop++;
                }
            }

            if ($loop > 0) {
                return [
                    "response" => [
                        "status" => true,
                        "statusCode" => StatusCodes::OK,
                        "data" => [
                            "bannerList" => $bannerList
                        ],
                        "message" => null,
                    ],
                    "statusCode" => StatusCodes::OK
                ];
            } else {
                throw ExceptionHelper::error([
                    "statusCode" => StatusCodes::NOT_FOUND,
                    "message" => "Banner list not found."
                ]);
            }
        } else {
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::NOT_FOUND,
                "message" => "Banner list not found."
            ]);
        }
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function bannerProductsList(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [
                'numeric' => ':attribute must contain only numbers',
                'shopId.exists' => "shop with provided id doesn\'t exist",
                'bannerId.exists' => "slider with provided id doesn\'t exist",
            ],
            [
                "shopId" => "numeric|required|exists:tbl_shop,id",
                "bannerId" => "numeric|required|exists:tbl_slider,id",
            ]
        );

        $productlist = array();
        $shopId   = $data['shopId'];
        $bannerId = $data['bannerId'];
        $page_no = $data['pageNo'] ?? 1;;

        if (!(isset($page_no) && $page_no != "" && $page_no != 0)) {
            $page_no = 1;
        }

        $shopData = Shop::select('id', 'name')
            ->where('id', $shopId)
            ->first();

        $sqlShopData = $shopData->toArray();

        $bannerData = Slider::where('id', $bannerId)
            ->first()
            ?->toArray();

        $sqlBannerP = SliderProduct::where('shop_id', $shopId)
            ->where('slider_id', $bannerId)
            ->orderBy('sort_order');

        $bannerLink = "";
        $count = $sqlBannerP->count();

        if (!empty($bannerData['link'] ?? "")) {
            $bannerLink = $bannerData['link'];
        }

        if ($count > 0) {

            $sqlBannerP = $sqlBannerP
                ->get()
                ->toArray();

            foreach ($sqlBannerP as $bannerPData) {

                $sqlProduct = Product::select('id', 'name', 'image', 'shop_id', 'price', 'sellingprice', 'unique_code', 'hsn_code', 'weight', 'unit_id', 'small_description', 'barcode')
                    ->where('shop_id', $shopId)
                    ->where('unique_code', $bannerPData['unique_code'])
                    ->where('status', 1)
                    ->first()
                    ?->toArray();

                if ($sqlProduct) {

                    $sqlProductData = $sqlProduct;

                    $sqlHsnCheck = HsnCode::select("id")
                        ->where("hsn_code", $sqlProductData['hsn_code']);

                    if ($sqlHsnCheck->count() > 0) {

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

                        if ($sqlProductData['price'] == 0) {
                            $price = $sqlProductData['sellingprice'];
                        } else {
                            $price = $sqlProductData['price'];
                        }

                        if ($price == $sqlProductData['sellingprice']) {
                            $sellingprice = "";
                        } else {
                            $sellingprice = "₹" . round($sqlProductData['sellingprice']);
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

                        $offerInformation = "";

                        $sqlOfferBundle = OfferBundling::select('offer_unique_id', 'description')
                            ->where('primary_unique_id', $sqlProductData['unique_code'])
                            ->where('status', 1)
                            ->get();

                        if ($sqlOfferBundle->count() > 0) {
                            $offerBundleData = $sqlOfferBundle->first();

                            $sqlShopProduct = Product::select('id', 'weight', 'price', 'sellingprice', 'unit_id')
                                ->where('shop_id', $shopId)
                                ->where('unique_code', $offerBundleData['offer_unique_id'])
                                ->where('status', 1)
                                ->get();

                            if ($sqlShopProduct->count() > 0) {
                                $offerInformation = $offerBundleData['description'];
                            }
                        }

                        $sqlPriceBundle = OfferPriceBundling::where('offer_unique_id', $sqlProductData['unique_code'])
                            ->where('status', 1)
                            ->get();

                        if ($sqlPriceBundle->count() > 0) {
                            $priceOffer = "1";
                        } else {
                            $priceOffer = "0";
                        }

                        array_push($unitList, array(
                            'variantId' => $sqlProductData['id'],
                            'weight' => $sqlProductData['weight'] . " " . CommonHelper::uomName($sqlProductData['unit_id']),
                            "price" => round($price),
                            "sellingPrice" => $sellingprice,
                            "discount" => $discount
                        ));

                        $productlist[] = array(
                            "productId" => $sqlProductData['id'],
                            "shopId" => $shopId,
                            "max_qty" => "5",
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
                            "small_description" => $sqlProductData['small_description'],
                            "productImage" => url("products") . "/" . $sqlProductData['barcode'] . '/' . $productImage,
                            "offerInformation" => $offerInformation,
                            "priceOffer" => $priceOffer,
                            "unit" => $unitList
                        );
                    }
                }
            }

            return [
                "response" => [
                    "status" => true,
                    "statusCode" => StatusCodes::OK,
                    "data" => [
                        "bannerImage" => url("slider") . "slider/" . $bannerData['slider'],
                        "bannerTitle" => $bannerData['title'],
                        "bannerSubtitle" => $bannerData['sub_title'],
                        "bannerLink" => $bannerLink,
                        "shopName" => $sqlShopData['name'],
                        "productList" => $productlist
                    ],
                    "message" => null,
                ],
                "statusCode" => StatusCodes::OK
            ];
        } else {
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::NOT_FOUND,
                "message" => "There are no Products in Offer",
                "data" => [
                    "bannerImage" => url("slider") . "slider/" . $bannerData['slider'],
                    "bannerTitle" => $bannerData['title'],
                    "bannerSubtitle" => $bannerData['sub_title'],
                    "bannerLink" => $bannerLink,
                    "shopName" => $sqlShopData['name']
                ]
            ]);
        }
    }
}
