<?php

namespace App\Http\Controllers;

use App\Helpers\ExceptionHelper;
use App\Models\AddressBook;

use App\Helpers\RequestValidator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Monolog\Logger;

class AddressBookController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @param  \Illuminate\Http\Request  $req
     * @return \Illuminate\Http\Response
     */
    public function addAddress(Request $req)
    {

        $dataToInsert = [];
        $userId = $req->user()->id;
        $addressIdBelongsToUser = false;
        $whereQuery = ["customer_id" => $userId];
        $message = "Address Successfully Updated.";

        try {
            $data = RequestValidator::validate(
                $req->input(),
                [
                    'string' => ':attribute must be a string',
                    'digits' => ':attribute must be of :digits digits',
                    'numeric' => ':attribute must contain only numbers',
                    'min' => ':attribute must be of at least :min characters',
                    'in' => ':attribute must be one of the following values: :values',
                ],
                [
                    'flat' => 'string',
                    'pincode' => 'numeric',
                    'd_status' => 'in:0,1',
                    'landmark' => 'string',
                    'latitude' => 'numeric',
                    "mobile" => "digits:10",
                    'city' => 'string|min:1',
                    'longitude' => 'numeric',
                    'name' => 'string|min:2',
                    'address_type' => 'in:1,2,3',
                    'address' => 'string|min:1',
                    'address_id' => 'numeric',
                ]
            );

            $keysToInsert = [
                "flat",  "name",  "city",  "state", "mobile",  "address",  "pincode", "landmark",  "latitude",  "longitude", "default_status", "address_type"
            ];

            foreach ($keysToInsert as $key) {
                if (isset($data[$key])) {
                    $dataToInsert[$key] = $data[$key];
                }
            }

            $addressCount = AddressBook::where($whereQuery)->count();

            if (!empty($data["address_id"])) {

                $dataToInsert["id"] = $data["address_id"];
                $addressIdBelongsToUser = (bool)AddressBook::where("id", $data["address_id"])
                    ->where($whereQuery)
                    ->first();

                if (!$addressIdBelongsToUser)
                    return response([
                        "data" => null,
                        "status" => false,
                        "statusCode" => 400,
                        "message" => "Address Not Added. Try Again",
                    ], 400);

                unset($dataToInsert["id"]);
                unset($dataToInsert["address_id"]);
                unset($dataToInsert["customer_id"]);

                $updated = AddressBook::where($whereQuery)
                    ->update($dataToInsert);

                if ($updated)
                    return response([
                        "data" => null,
                        "status" => true,
                        "statusCode" => 200,
                        "message" => $message,
                    ], 200);
                else
                    throw ExceptionHelper::somethingWentWrong();
            }

            $dataToInsert["customer_id"] = $userId;
            $dataToInsert["default_status"] = !$addressCount ?
                1 : ($dataToInsert["default_status"] ?? 0);
            $insertedAddressId =  AddressBook::create($dataToInsert)->id ?? null;
            $message = "Address Successfully Saved.";

            if (!$insertedAddressId)
                throw ExceptionHelper::somethingWentWrong();

            return response([
                "data" => [
                    "addressId" => $insertedAddressId
                ],
                "status" => true,
                "statusCode" => 200,
                "message" => $message,
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
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function addressBook(Request $req)
    {
        $userId = $req->user()->id;

        try {
            $addresses = AddressBook::select(
                "id as addressId",
                "name",
                "mobile",
                "address",
                "city",
                "state",
                "pincode",
                "flat as plot",
                "landmark",
                "latitude",
                "longitude",
                "address_type as addressType",
                "default_status as defaultStatus"
            )
                ->where("customer_id", $userId)
                ->get()
                ->toArray();

            if (!count($addresses))
                throw ExceptionHelper::notFound([
                    "message" => "Address not found.",
                ]);

            return response([
                "status" => true,
                "message" => null,
                "statusCode" => 200,
                "data" => $addresses,
            ], 200);
        } catch (ExceptionHelper $e) {

            Log::error($e->getMessage());

            return response([
                "data" => $e->data,
                "status" => $e->status,
                "statusCode" => $e->statusCode,
                "message" => $e->getMessage()
            ], $e->statusCode);
        }
    }



    /**
     * Update the sepecified record.
     *
     * @return \Illuminate\Http\Request
     */
    public function defaultAddress(Request $req)
    {
        $userId = $req->user()->id;

        try {
            $data = RequestValidator::validate(
                $req->input(),
                [
                    "exists" => "Address not found.",
                    'string' => ':attribute must be a string',
                    "required" => ":attribute is a required field"
                ],
                ['addressId' => 'string|required|exists:address_book,id']
            );

            $addressBookCount = AddressBook::where("customer_id", $userId)
                ->count();

            if ($addressBookCount > 1) {
                $updated = AddressBook::where("customer_id", $userId)
                    ->update([
                        "default_status" => 0
                    ]);
                if (!$updated)
                    throw ExceptionHelper::somethingWentWrong();
            }

            $updated = AddressBook::where("id", $data["addressId"])
                ->update([
                    "default_status" => 1
                ]);

            if (!$updated)
                throw ExceptionHelper::somethingWentWrong();

            return response([
                "data" => null,
                "status" => true,
                "statusCode" => 200,
                "message" => "Default address updated.",
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $req
     * @param  \App\Models\AddressBook  $addressBook
     * @return \Illuminate\Http\Response
     */
    public function editAddress(Request $req)
    {
        $userId = $req->user()->id;
        $addressIdBelongsToUser = false;
        $message = "Address Successfully Updated.";

        try {
            $data = RequestValidator::validate(
                $req->input(),
                [
                    'string' => ':attribute must be a string',
                    'digits' => ':attribute must be of :digits digits',
                    'numeric' => ':attribute must contain only numbers',
                    'min' => ':attribute must be of at least :min characters',
                    'in' => ':attribute must be one of the following values: :values',
                ],
                [
                    'flat' => 'string',
                    'pincode' => 'numeric',
                    'd_status' => 'in:0,1',
                    'landmark' => 'string',
                    'latitude' => 'numeric',
                    "mobile" => "digits:10",
                    'city' => 'string|min:1',
                    'longitude' => 'numeric',
                    'name' => 'string|min:2',
                    'address_type' => 'in:1,2,3',
                    'address' => 'string|min:1',
                    'address_id' => 'numeric|required',
                ]
            );

            $data["customer_id"] = $userId;
            $data["id"] = $data["address_id"];
            $data["default_status"] = $data["d_status"] ?? 1;
            unset($data["d_status"]);

            $whereQuery = [
                "customer_id" => $userId,
                "id" => $data["address_id"]
            ];

            $addressIdBelongsToUser = (bool)AddressBook::where($whereQuery)->first();

            if (!$addressIdBelongsToUser)
                return response([
                    "data" => null,
                    "status" => false,
                    "statusCode" => 400,
                    "message" => "Address Not Added. Try Again",
                ], 400);

            unset($data["id"]);
            unset($data["address_id"]);
            unset($data["customer_id"]);
            AddressBook::where($whereQuery)->update($data);

            return response([
                "data" => null,
                "status" => true,
                "statusCode" => 200,
                "message" => $message,
            ], 200);
        } catch (ValidationException $e) {

            return response([
                "data" => null,
                "status" => false,
                "statusCode" => 422,
                "message" => $e->getMessage(),
            ], 422);
        }
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AddressBook  $addressBook
     * @return \Illuminate\Http\Response
     */
    public function removeAddress(Request $req)
    {
        $userId = $req->user()->id;
        $addressBook = AddressBook::where("customer_id", $userId)->first();

        if ($addressBook) {
            $addressBook->delete();
            return response([
                "data" => null,
                "status" => true,
                "statusCode" => 200,
                "message" => "Address Deleted Successfully.",
            ], 200);
        } else {
            return response([
                "data" => null,
                "status" => true,
                "statusCode" => 400,
                "message" => "Address Not Deleted. Try Again.",
            ], 400);
        }
    }
}
