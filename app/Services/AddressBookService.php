<?php

namespace App\Services;

use App\Constants\StatusCodes;
use App\Helpers\ExceptionHelper;
use App\Helpers\RequestValidator;
use App\Models\AddressBook;
use Laravel\Lumen\Http\Request;

// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @todo Document this
 */
class AddressBookService
{

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function addAddress(Request $req)
    {

        $dataToInsert = [];
        $userId = $req->user()->id;
        $addressIdBelongsToUser = false;
        $whereQuery = ["customer_id" => $userId];
        $message = "Address Successfully Updated.";

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
                return [
                    "response" => [
                        "data" => null,
                        "status" => false,
                        "statusCode" => 400,
                        "message" => "Address Not Added. Try Again",
                    ],
                    "statusCode" => StatusCodes::NOT_FOUND
                ];

            unset($dataToInsert["id"]);
            unset($dataToInsert["address_id"]);
            unset($dataToInsert["customer_id"]);

            $updated = AddressBook::where($whereQuery)
                ->update($dataToInsert);

            if ($updated)
                return [
                    "response" => [
                        "data" => null,
                        "status" => true,
                        "statusCode" => StatusCodes::OK,
                        "message" => $message,
                    ],
                    "statusCode" => StatusCodes::OK
                ];
            else
                throw ExceptionHelper::error();
        }

        $dataToInsert["customer_id"] = $userId;
        $dataToInsert["default_status"] = !$addressCount ?
            1 : ($dataToInsert["default_status"] ?? 0);
        $insertedAddressId =  AddressBook::create($dataToInsert)->id ?? null;
        $message = "Address Successfully Saved.";

        if (!$insertedAddressId)
            throw ExceptionHelper::error();

        return [
            "response" => [
                "data" => [
                    "addressId" => $insertedAddressId
                ],
                "status" => true,
                "statusCode" => StatusCodes::OK,
                "message" => $message,
            ],
            "statusCode" => StatusCodes::OK
        ];
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function addressBook(Request $req)
    {
        $userId = $req->user()->id;

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
            throw ExceptionHelper::error([
                "statusCode" => StatusCodes::NOT_FOUND,
                "message" => "Address not found.",
            ]);

        return [
            "response" => [
                "status" => true,
                "message" => null,
                "statusCode" => StatusCodes::OK,
                "data" => $addresses,
            ],
            "statusCode" => StatusCodes::OK
        ];
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function defaultAddress(Request $req)
    {
        $userId = $req->user()->id;

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
                throw ExceptionHelper::error();
        }

        $updated = AddressBook::where("id", $data["addressId"])
            ->update([
                "default_status" => 1
            ]);

        if (!$updated)
            throw ExceptionHelper::error();

        return [
            "response" => [
                "data" => null,
                "status" => true,
                "statusCode" => StatusCodes::OK,
                "message" => "Default address updated.",
            ],
            "statusCode" => StatusCodes::OK
        ];
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function editAddress(Request $req)
    {
        $userId = $req->user()->id;
        $addressIdBelongsToUser = false;
        $message = "Address Successfully Updated.";

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
            return [
                "response" => [
                    "data" => null,
                    "status" => false,
                    "statusCode" => StatusCodes::BAD_REQUEST,
                    "message" => "Address Not Added. Try Again",
                ],
                "statusCode" => StatusCodes::BAD_REQUEST
            ];

        unset($data["id"]);
        unset($data["address_id"]);
        unset($data["customer_id"]);
        AddressBook::where($whereQuery)->update($data);

        return [
            "response" => [
                "data" => null,
                "status" => true,
                "statusCode" => StatusCodes::OK,
                "message" => $message,
            ],
            "statusCode" => StatusCodes::OK
        ];
    }



    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public function removeAddress(Request $req)
    {
        $userId = $req->user()->id;
        $addressBook = AddressBook::where("customer_id", $userId)->first();

        if ($addressBook) {
            $addressBook->delete();
            return [
                "response" => [
                    "data" => null,
                    "status" => true,
                    "statusCode" => StatusCodes::OK,
                    "message" => "Address Deleted Successfully.",
                ],
                "statusCode" => StatusCodes::OK
            ];
        } else
            return [
                "response" => [
                    "data" => null,
                    "status" => false,
                    "statusCode" => StatusCodes::BAD_REQUEST,
                    "message" => "Address Not Deleted. Try Again.",
                ],
                "statusCode" => StatusCodes::BAD_REQUEST
            ];
    }
}
