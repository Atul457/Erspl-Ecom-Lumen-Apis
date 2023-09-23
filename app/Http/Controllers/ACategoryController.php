<?php

namespace App\Http\Controllers;

use App\Services\ACategoryService;
use Illuminate\Http\Request;

class ACategoryController extends Controller
{

    private ACategoryService $service;

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function __construct()
    {
        $this->service = new ACategoryService();
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @TODO Document this
     */
    public function categoryList(Request $req)
    {
        $res = $this->service->categoryList($req);
        return response($res["response"], $res["statusCode"]);
    }
}
