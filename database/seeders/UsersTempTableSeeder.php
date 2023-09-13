<?php

namespace Database\Seeders;

use App\Models\UsersTemp;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTempTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $defaultOtp = "0000";
        $referralPostFix = "ERSPL";
        $defaultRegistrationType = "App";

        $data = [
            'dob' => date("2000-10-01"),
            'password' => Hash::make('123456'),
            "attempt" => 1,
            "otp" => $defaultOtp,
            "reg_type" => $defaultRegistrationType,
        ];

        UsersTemp::insert([
            array_merge($data, [
                'first_name' => 'Ravi',
                'last_name' => "Yadav",
                'mobile' => "6280986975",
                'email' => 'raviyadavspn1991@gmail.com',
                "referral_code" => "6280986975" . $referralPostFix
            ]),
            array_merge($data, [
                'first_name' => 'Test',
                'last_name' => "",
                'mobile' => "8837684275",
                'email' => 'atul15235@gmail.com',
                "referral_code" => "8837684275" . $referralPostFix
            ])
        ]);
    }
}
