<?php

namespace App\Http\Controllers;

use App\Constants\StatusCodes;
use App\Helpers\ExceptionHelper;
use App\Models\Slider;
use App\Services\SliderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SliderController extends Controller
{

    private SliderService $service;

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function __construct()
    {
        $this->service = new SliderService();
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function sliderList()
    {
        $res = $this->service->sliderList();
        return response($res["response"], $res["statusCode"]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function bannerList(Request $req)
    {
        $res = $this->service->bannerList($req);
        return response($res["response"], $res["statusCode"]);
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function bannerProductsList(Request $req)
    {
        $res = $this->service->bannerProductsList($req);
        return response($res["response"], $res["statusCode"]);
    }
}
