<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RequestValidator
{
    /**
     * Validate the given data based on provided rules and messages.
     *
     * @param array $data The data to be validated.
     * @param array $messages The custom error messages.
     * @param array $validations The validation rules.
     * @return array The validated data.
     * @throws ValidationException
     */
    public static function validate(array $data, array $messages, array $validations)
    {
        $validator = Validator::make($data, $validations, $messages);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $error = $errors->first() ?? "Something went wrong";
            throw ValidationException::withMessages([
                'error' => $error,
            ]);
        }

        return $data;
    }
}
