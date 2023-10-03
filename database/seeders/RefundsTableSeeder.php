<?php

namespace Database\Seeders;

use App\Models\Refund;
use App\Models\Registration;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class RefundsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonFilePath = resource_path("../data/tbl_refund.json");
        $jsonContent = File::get($jsonFilePath);
        $items_ = json_decode($jsonContent, true);
        $items = [];

        foreach ($items_ as $item) {
            $customer = Registration::inRandomOrder()->first()->toArray();
            $item["customer_id"] = $customer["id"];
            $item["customer_name"] = $customer["first_name"];
            $items[] = $item;
        }

        Refund::insert($items);
    }
}
