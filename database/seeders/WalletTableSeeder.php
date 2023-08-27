<?php

namespace Database\Seeders;

use App\Models\Wallet;
use Illuminate\Database\Seeder;

class WalletTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Wallet::insert([
            [
                "customer_id" => 1,
                "amount" => 50.00,
                "payment_status" => 1,
                "referral_code" => "9779755869ERSPL",
                "referral_by" => "8837684275ERSPL",
                "remark" => "Referral Bonus",
            ],
            [
                "customer_id" => 2,
                "amount" => 150.00,
                "payment_status" => 2,
                "referral_code" => "8837684275ERSPL",
                "referral_by" => "9779755869ERSPL",
                "remark" => "Referral Bonus",
            ]
        ]);
    }
}
