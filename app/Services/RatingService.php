<?php

namespace App\Services;

use App\Constants\StatusCodes;
use App\Helpers\ExceptionHelper;
use App\Helpers\RequestValidator;
use App\Helpers\ResponseGenerator;
use App\Models\Rating;
use Illuminate\Http\Request;

// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @todo Document this
 */
class RatingService
{

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function addRating(Request $req)
    {
        $data = RequestValidator::validate(
            $req->input(),
            [
                'numeric' => ':attribute must be a number',
                'shopId.exists' => 'shop with provided id doesn\'t exist',
            ],
            [
                "shopId" => "required|numeric|exists:tbl_shop,id",
                "orderId" => "required|numeric",
                "rating" => "numeric",
                "deliveryId" => "required|numeric",
                "deliveryRating" => "numeric",
            ]
        );

        $userId = $req->user()->id;
        $shopId = $data['shopId'];
        $orderId = $data['orderId'];
        $rating = $data['rating'] ?? 0;
        $review = $req->input('review');
        $deliveryId = $data['deliveryId'];
        $deliveryRating = $data['deliveryRating'] ?? 0;
        $deliveryReview = $req->input('deliveryReview');

        $baseWhere = [
            "user_id" => $userId,
            "shop_id" => $shopId,
            "delivery_boy_id" => $deliveryId,
            "order_id" => $orderId,
        ];

        $ratingExists = Rating::where($baseWhere)->exists();

        if ($ratingExists) {

            $updated = Rating::where($baseWhere)
                ->update([
                    "rating" => $rating,
                    "review" => $review,
                    "delivery_boy_rating" => $deliveryRating,
                    "delivery_boy_review" => $deliveryReview,
                ]);

            if (!$updated)
                ExceptionHelper::error([
                    "message" => "Can't Update Rating."
                ]);

            return ResponseGenerator::generateResponseWithStatusCode(
                ResponseGenerator::generateSuccessResponse([
                    "message" => "Rating Updated."
                ])
            );
        }

        $inserted = Rating::insert([
            "user_id" => $userId,
            "shop_id" => $shopId,
            "order_id" => $orderId,
            "delivery_boy_id" => $deliveryId,
            "delivery_boy_rating" => $deliveryRating,
            "rating" => $rating,
            "review" => $review,
            "delivery_boy_review" => $deliveryReview,
            "date" => date('Y-m-d H:i:s'),
        ]);

        if (!$inserted)
            ExceptionHelper::error([
                "message" => "Something went wrong. Try Again."
            ]);

        return ResponseGenerator::generateResponseWithStatusCode(
            ResponseGenerator::generateSuccessResponse([
                "message" => "Rating Submitted."
            ])
        );
    }
}
