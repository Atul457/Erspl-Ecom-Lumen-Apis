<?php

namespace App\Http\Controllers;

use App\Helpers\ExceptionHelper;
use App\Models\Slider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SliderController extends Controller
{

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function sliderList(Request $req)
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

        return response([
            "data" => [
                "sliderList" => $sliderList
            ],
            "status" =>  true,
            "statusCode" => 200,
            "messsage" => null
        ], 200);
    }
}
