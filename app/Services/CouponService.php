<?php

namespace App\Services;

use App\Constants\StatusCodes;
use App\Helpers\CommonHelper;
use App\Helpers\ExceptionHelper;
use App\Helpers\RequestValidator;
use App\Helpers\ResponseGenerator;
use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Support\Facades\Date;
use Illuminate\Http\Request;

// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @todo Document this
 */
class CouponService
{


    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function applyCoupon(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [
                'numeric' => ':attribute must be a number'
            ],
            [
                "code" => "required",
                "amount" => "required|numeric",
            ]
        );

        $userId = $req->user()->id;
        $coupon = $data['code'];
        $couponCartTotal = $data['amount'];

        $date = date('d-m-Y H:i:s');

        $sqlCoupon = Coupon::select("*")
            ->where([
                "couponcode" => $coupon,
                "status" => 1
            ])
            ->whereNull("user_id");

        $sqlCoupon1 = Coupon::select("*")
            ->where([
                "couponcode" => $coupon,
                "status" => 1
            ])
            ->where("user_id", $userId);

        if ($sqlCoupon->count() > 0) {

            $sqlCouponData = $sqlCoupon
                ->first()
                ->toArray();

            $date2 = date('d-m-Y H:i:s', strtotime($sqlCouponData['expire_date']));
            $currentDate = strtotime($date);
            $couponDate = strtotime($date2);

            if ($currentDate <= $couponDate) {
                if ($sqlCouponData['times_used_coupon'] == 1) {

                    $sqlCouponUseCount = Order::select("order_id")
                        ->where([
                            "coupon" => $coupon,
                            "payment_status" => 1
                        ])
                        ->groupBy("order_id");

                    $couponUseCount = $sqlCouponUseCount->count();

                    if ($couponUseCount >= $sqlCouponData['time_to_use_coupon']) {
                        throw ExceptionHelper::error([
                            "statusCode" => StatusCodes::FORBIDDEN,
                            "message" => "COUPON USE LIMIT EXCEED"
                        ]);
                    } else {

                        if ($sqlCouponData['discount_type'] == 0) {
                            $disType = "%";
                        } else {
                            $disType = "FLAT";
                        }

                        $offerDescrption = "counpon Applied " . $sqlCouponData['discount'] . $disType . " OFF.";

                        if ($sqlCouponData['discount_upto'] > 0) {
                            $offerDescrption = "counpon Applied " . $sqlCouponData['discount'] . $disType . " OFF UPTO " . $sqlCouponData['discount_upto'] . " ) ";
                        }

                        if ($sqlCouponData['times_used'] == 1) {

                            $sqlOrder = Order::select("order_id")
                                ->where([
                                    "customer_id" => $userId,
                                    "coupon" => $coupon,
                                    "payment_status" => 1
                                ])
                                ->groupBy("order_id");

                            $usedCount = $sqlOrder->count();

                            if ($usedCount >= $sqlCouponData['time_to_use']) {
                                throw ExceptionHelper::error([
                                    "statusCode" => StatusCodes::BAD_REQUEST,
                                    "message" => "YOU ARE ALREADY AVAIL THIS OFFER."
                                ]);
                            } else {

                                if ($sqlCouponData['minimum_value'] <= $couponCartTotal) {
                                    if ($sqlCouponData['discount_type'] == 0) {
                                        $couponDiscount = ($couponCartTotal * $sqlCouponData['discount']) / 100;
                                        if ($sqlCouponData['discount_upto'] > 0) {
                                            if ($couponDiscount > $sqlCouponData['discount_upto']) {
                                                $couponDiscount = $sqlCouponData['discount_upto'];
                                            }
                                        }
                                    } else {
                                        $couponDiscount = $sqlCouponData['discount'];
                                    }

                                    return ResponseGenerator::generateResponseWithStatusCode(
                                        ResponseGenerator::generateSuccessResponse([
                                            "data" => [
                                                "discount" => sprintf('%0.2f', $couponDiscount),
                                                "code" => $coupon,
                                                "couponDescription" => $offerDescrption
                                            ],
                                            "message" => "COUPON APPLIED.",
                                        ])
                                    );
                                } else {
                                    throw ExceptionHelper::error([
                                        "statusCode" => StatusCodes::BAD_REQUEST,
                                        "message" => "Minimun Order Value To Apply This Coupon is " . $sqlCouponData['minimum_value'] . "/-"
                                    ]);
                                }
                            }
                        } else {
                            if ($sqlCouponData['minimum_value'] <= $couponCartTotal) {
                                if ($sqlCouponData['discount_type'] == 0) {
                                    $couponDiscount = ($couponCartTotal * $sqlCouponData['discount']) / 100;
                                    if ($sqlCouponData['discount_upto'] > 0) {
                                        if ($couponDiscount > $sqlCouponData['discount_upto']) {
                                            $couponDiscount = $sqlCouponData['discount_upto'];
                                        }
                                    }
                                } else {
                                    $couponDiscount = $sqlCouponData['discount'];
                                }

                                return ResponseGenerator::generateResponseWithStatusCode(
                                    ResponseGenerator::generateSuccessResponse([
                                        "data" => [
                                            "discount" => sprintf('%0.2f', $couponDiscount),
                                            "code" => $coupon,
                                            "couponDescription" => $offerDescrption
                                        ],
                                        "message" => "COUPON APPLIED.",
                                    ])
                                );
                            } else {
                                throw ExceptionHelper::error([
                                    "statusCode" => StatusCodes::BAD_REQUEST,
                                    "message" => "Minimun Order Value To Apply This Coupon is " . $sqlCouponData['minimum_value'] . "/-"
                                ]);
                            }
                        }
                    }
                } else {

                    if ($sqlCouponData['discount_type'] == 0) {
                        $disType = "%";
                    } else {
                        $disType = "FLAT";
                    }

                    $offerDescrption = "counpon Applied " . $sqlCouponData['discount'] . $disType . " OFF.";

                    if ($sqlCouponData['discount_upto'] > 0) {
                        $offerDescrption = "counpon Applied " . $sqlCouponData['discount'] . $disType . " OFF UPTO " . $sqlCouponData['discount_upto'] . " ) ";
                    }

                    if ($sqlCouponData['times_used'] == 1) {

                        $sqlOrder = Order::select("order_id")
                            ->where([
                                "customer_id" => $userId,
                                "coupon" => $coupon,
                                "payment_status" => 1
                            ])
                            ->groupBy("order_id");

                        $usedCount = $sqlOrder->count();

                        if ($usedCount >= $sqlCouponData['time_to_use']) {
                            throw ExceptionHelper::error([
                                "statusCode" => StatusCodes::BAD_REQUEST,
                                "message" => "YOU ARE ALREADY AVAIL THIS OFFER"
                            ]);
                        } else {
                            if ($sqlCouponData['minimum_value'] <= $couponCartTotal) {
                                if ($sqlCouponData['discount_type'] == 0) {
                                    $couponDiscount = ($couponCartTotal * $sqlCouponData['discount']) / 100;
                                    if ($sqlCouponData['discount_upto'] > 0) {
                                        if ($couponDiscount > $sqlCouponData['discount_upto']) {
                                            $couponDiscount = $sqlCouponData['discount_upto'];
                                        }
                                    }
                                } else {
                                    $couponDiscount = $sqlCouponData['discount'];
                                }

                                return ResponseGenerator::generateResponseWithStatusCode(
                                    ResponseGenerator::generateSuccessResponse([
                                        "data" => [
                                            "discount" => sprintf('%0.2f', $couponDiscount),
                                            "code" => $coupon,
                                            "couponDescription" => $offerDescrption
                                        ],
                                        "message" => "COUPON APPLIED.",
                                    ])
                                );
                            } else {
                                throw ExceptionHelper::error([
                                    "statusCode" => StatusCodes::BAD_REQUEST,
                                    "message" => "Minimun Order Value To Apply This Coupon is " . $sqlCouponData['minimum_value'] . "/-"
                                ]);
                            }
                        }
                    } else {

                        if ($sqlCouponData['minimum_value'] <= $couponCartTotal) {
                            if ($sqlCouponData['discount_type'] == 0) {
                                $couponDiscount = ($couponCartTotal * $sqlCouponData['discount']) / 100;
                                if ($sqlCouponData['discount_upto'] > 0) {
                                    if ($couponDiscount > $sqlCouponData['discount_upto']) {
                                        $couponDiscount = $sqlCouponData['discount_upto'];
                                    }
                                }
                            } else {
                                $couponDiscount = $sqlCouponData['discount'];
                            }

                            return ResponseGenerator::generateResponseWithStatusCode(
                                ResponseGenerator::generateSuccessResponse([
                                    "data" => [
                                        "discount" => sprintf('%0.2f', $couponDiscount),
                                        "code" => $coupon,
                                        "couponDescription" => $offerDescrption
                                    ],
                                    "message" => "COUPON APPLIED.",
                                ])
                            );
                        } else {
                            throw ExceptionHelper::error([
                                "statusCode" => StatusCodes::BAD_REQUEST,
                                "message" => "Minimun Order Value To Apply This Coupon is " . $sqlCouponData['minimum_value'] . "/-"
                            ]);
                        }
                    }
                }
            } else {
                throw ExceptionHelper::error([
                    "statusCode" => StatusCodes::BAD_REQUEST,
                    "message" => "This coupon code is invalid or has expired."
                ]);
            }
        } else if ($sqlCoupon1->count() > 0) {

            $sqlCouponData1 = $sqlCoupon1
                ->first()
                ->toArray();

            $date2 = date('d-m-Y H:i:s', strtotime($sqlCouponData1['expire_date']));
            $currentDate = strtotime($date);
            $couponDate = strtotime($date2);

            if ($currentDate <= $couponDate) {

                if ($sqlCouponData1['discount_type'] == 0) {
                    $disType = "%";
                } else {
                    $disType = "FLAT";
                }

                $offerDescrption = "counpon Applied " . $sqlCouponData1['discount'] . $disType . " OFF.";

                if ($sqlCouponData1['discount_upto'] > 0) {
                    $offerDescrption = "counpon Applied " . $sqlCouponData1['discount'] . $disType . " OFF UPTO " . $sqlCouponData1['discount_upto'] . " ) ";
                }

                if ($sqlCouponData1['times_used'] == 1) {

                    $sqlOrder = Order::select("order_id")
                        ->where([
                            "customer_id" => $userId,
                            "coupon" => $coupon,
                            "payment_status" => 1
                        ])
                        ->groupBy("order_id");

                    $usedCount = $sqlOrder->count();

                    if ($usedCount >= $sqlCouponData1['time_to_use']) {
                        throw ExceptionHelper::error([
                            "statusCode" => StatusCodes::BAD_REQUEST,
                            "message" => "YOU ARE ALREADY AVAIL THIS OFFER."
                        ]);
                    } else {
                        if ($sqlCouponData1['minimum_value'] <= $couponCartTotal) {
                            if ($sqlCouponData1['discount_type'] == 0) {
                                $couponDiscount = ($couponCartTotal * $sqlCouponData1['discount']) / 100;
                                if ($sqlCouponData1['discount_upto'] > 0) {
                                    if ($couponDiscount > $sqlCouponData1['discount_upto']) {
                                        $couponDiscount = $sqlCouponData1['discount_upto'];
                                    }
                                }
                            } else {
                                $couponDiscount = $sqlCouponData1['discount'];
                            }

                            return ResponseGenerator::generateResponseWithStatusCode(
                                ResponseGenerator::generateSuccessResponse([
                                    "data" => [
                                        "discount" => sprintf('%0.2f', $couponDiscount),
                                        "code" => $coupon,
                                        "couponDescription" => $offerDescrption
                                    ],
                                    "message" => "COUPON APPLIED.",
                                ])
                            );
                        } else {
                            throw ExceptionHelper::error([
                                "statusCode" => StatusCodes::BAD_REQUEST,
                                "message" => "Minimun Order Value To Apply This Coupon is " . $sqlCouponData1['minimum_value'] . "/-"
                            ]);
                        }
                    }
                } else {
                    if ($sqlCouponData1['minimum_value'] <= $couponCartTotal) {
                        if ($sqlCouponData1['discount_type'] == 0) {
                            $couponDiscount = ($couponCartTotal * $sqlCouponData1['discount']) / 100;
                            if ($sqlCouponData1['discount_upto'] > 0) {
                                if ($couponDiscount > $sqlCouponData1['discount_upto']) {
                                    $couponDiscount = $sqlCouponData1['discount_upto'];
                                }
                            }
                        } else {
                            $couponDiscount = $sqlCouponData1['discount'];
                        }

                        return ResponseGenerator::generateResponseWithStatusCode(
                            ResponseGenerator::generateSuccessResponse([
                                "data" => [
                                    "discount" => sprintf('%0.2f', $couponDiscount),
                                    "code" => $coupon,
                                    "couponDescription" => $offerDescrption
                                ],
                                "message" => "COUPON APPLIED.",
                            ])
                        );
                    } else {
                        throw ExceptionHelper::error([
                            "statusCode" => StatusCodes::BAD_REQUEST,
                            "message" => "Minimun Order Value To Apply This Coupon is " . $sqlCouponData1['minimum_value'] . "/-"
                        ]);
                    }
                }
            } else {
                throw ExceptionHelper::error([
                    "statusCode" => StatusCodes::BAD_REQUEST,
                    "message" => "This coupon code is invalid or has expired."
                ]);
            }
        } else {
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::BAD_REQUEST,
                "message" => "This coupon code is invalid or has expired."
            ]);
        }
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function couponList(Request $req)
    {
        $couponList = array();
        $userId = $req->user()->id;
        $currentDate = date('Y-m-d H:i:s');

        $sqlCoupon = Coupon::where('status', 1)
            ->where('start_date', '<=', $currentDate)
            ->where('expire_date', '>=', $currentDate)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereNull('user_id');
            })
            ->orderBy('expire_date', 'desc');

        $count = $sqlCoupon->count();

        if ($count > 0) {
            $subtitle = "";
            $couponStatus = "0";

            $sqlCoupon = $sqlCoupon
                ->get()
                ->toArray();

            foreach ($sqlCoupon as $sqlCouponData) {
                if ($sqlCouponData['coupon_subtitle'] != null) {
                    $subtitle = $sqlCouponData['coupon_subtitle'];
                }

                if ($sqlCouponData['times_used'] == 1) {
                    $sqlOrder = Order::select("order_id")
                        ->where([
                            "customer_id" => $userId,
                            "coupon" => $sqlCouponData['couponcode'],
                            "payment_status" => 1
                        ])
                        ->groupBy("order_id");

                    $usedCount = $sqlOrder->count();

                    if ($usedCount < $sqlCouponData['time_to_use']) {
                        $couponStatus = "1";
                    }
                } else if ($sqlCouponData['times_used'] == 0) {
                    $couponStatus = "1";
                }

                $couponList[] = array(
                    'title' => $sqlCouponData['coupon_title'],
                    'subtitle' => $subtitle,
                    "coupon" => $sqlCouponData['couponcode'],
                    "expireDate" => date('d-m-Y', strtotime($sqlCouponData['expire_date'])),
                    "couponStatus" => $couponStatus
                );
            }

            return ResponseGenerator::generateResponseWithStatusCode(
                ResponseGenerator::generateSuccessResponse([
                    "data" => [
                        "couponList" => $couponList
                    ],
                ])
            );
        } else {
            throw ExceptionHelper::error([
                "message" => "Coupon List Not Found.",
                "statusCode" => StatusCodes::NOT_FOUND
            ]);
        }
    }
}
