<?php

namespace App\Services;

use App\Constants\StatusCodes;
use App\Helpers\CommonHelper;
use App\Helpers\ExceptionHelper;
use App\Helpers\RequestValidator;
use App\Models\Slider;
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
}
