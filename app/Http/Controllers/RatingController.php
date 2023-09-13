<?php

namespace App\Http\Controllers;

use App\Helpers\ExceptionHelper;
use App\Helpers\RequestValidator;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RatingController extends Controller
{

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function addRating(Request $req)
    {

        try {
            $data = RequestValidator::validate(
                $req->input(),
                [
                    'numeric' => ':attribute must be a number',
                    'shopId.exists' => 'shop with provided id doesn\'t exist',
                    'userId.exists' => 'shop with provided id doesn\'t exist'
                ],
                [
                    "shopId" => "required|numeric|exists:shop,id",
                    "userId" => "required|numeric|exists:tbl_registration,id",
                    "orderId" => "required|numeric",
                    "rating" => "numeric",
                    "deliveryId" => "required|numeric",
                    "deliveryRating" => "numeric",
                ]
            );

            $userId = $data['userId'];
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
                    ExceptionHelper::somethingWentWrong([
                        "message" => "Can't Update Rating."
                    ]);

                return response([
                    "data" => null,
                    "status" =>  true,
                    "statusCode" => 200,
                    "messsage" => "Rating Updated."
                ], 200);
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
                ExceptionHelper::somethingWentWrong([
                    "message" => "Something went wrong. Try Again."
                ]);

            return response([
                "data" => null,
                "status" =>  true,
                "statusCode" => 200,
                "messsage" => "Rating Submitted."
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
                "message" => $e->getMessage(),
                "statusCode" => $e->statusCode,
            ], $e->statusCode);
        }
    }
}
