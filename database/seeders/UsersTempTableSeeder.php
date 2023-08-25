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
            'first_name' => 'Atul',
            'last_name' => "Singh",
            'mobile' => "8837684275",
            'dob' => date("2000-10-01"),
            'email' => 'as3771083@gmail.com',
            'password' => Hash::make('123456'),
            "attempt" => 1,
            "otp" => $defaultOtp,
            "reg_type" => $defaultRegistrationType,
            "referral_code" => "8837684275".$referralPostFix
        ];

        UsersTemp::create($data);

    }
}
