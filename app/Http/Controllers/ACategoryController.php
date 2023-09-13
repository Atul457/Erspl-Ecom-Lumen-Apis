<?php

namespace App\Http\Controllers;

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

        try {

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
                "statusCode" => 200,
                "messsage" => null
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
                "statusCode" => $e->statusCode,
                "message" => $e->getMessage(),
            ], $e->statusCode);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function searchCategoryList()
    {

        try {

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
                "statusCode" => 200,
                "messsage" => null
            ], 200);
        } catch (ExceptionHelper $e) {

            Log::error($e->getMessage());

            return response([
                "data" => $e->data,
                "status" => $e->status,
                "statusCode" => $e->statusCode,
                "message" => $e->getMessage(),
            ], $e->statusCode);
        }
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
