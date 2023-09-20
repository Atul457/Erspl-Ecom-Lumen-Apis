<?php

namespace App\Http\Controllers;

use App\Constants\StatusCodes;
use App\Helpers\ExceptionHelper;
use App\Helpers\RequestValidator;
use App\Models\ACategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ACategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function categoryList(Request $req)
    {
        $urlToPrepend = url('categorys/');

        $data = RequestValidator::validate(
            $req->input(),
            [],
            [
                "industriesId" => "required|numeric"
            ]
        );

        $categories = ACategory::select("name", "id as categoryId", DB::raw("CONCAT('$urlToPrepend/', icon) as image", "status as categoryStatus"))
            ->where([
                "industries_id" => $data["industriesId"],
                "status" => 1
            ])
            ->orderBy("category_order", "desc")
            ->get()
            ->toArray();

        return response([
            "data" => [
                "categories" => $categories
            ],
            "status" =>  true,
            "statusCode" => StatusCodes::OK,
            "messsage" => null
        ], StatusCodes::OK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function searchCategoryList()
    {

        $categoryList = ACategory::select("name as categoryName")
            ->where([
                "status" => 1,
                "industries_id" => 1,
            ])
            ->get()
            ->toArray();

        if (!count($categoryList))
            throw ExceptionHelper::notFound([
                "message" => "Category List Not Found."
            ]);

        return response([
            "data" => [
                "categoryList" => $categoryList
            ],
            "status" =>  true,
            "statusCode" => StatusCodes::OK,
            "messsage" => null
        ], StatusCodes::OK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ACategory  $aCategory
     * @return \Illuminate\Http\Response
     */
    public function show(ACategory $aCategory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ACategory  $aCategory
     * @return \Illuminate\Http\Response
     */
    public function edit(ACategory $aCategory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ACategory  $aCategory
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ACategory $aCategory)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ACategory  $aCategory
     * @return \Illuminate\Http\Response
     */
    public function destroy(ACategory $aCategory)
    {
        //
    }
}
