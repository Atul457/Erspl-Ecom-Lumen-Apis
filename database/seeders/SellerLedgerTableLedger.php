<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\SellerLeger;
use App\Models\Shop;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class SellerLedgerTableLedger extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonFilePath = resource_path("../data/tbl_seller_ledger.json");
        $jsonContent = File::get($jsonFilePath);
        $items_ = json_decode($jsonContent, true);
        $items = [];

        foreach ($items_ as $item) {
            $shop = Shop::inRandomOrder()->first()->toArray();
            $city = City::inRandomOrder()->first()->toArray();

            $item["shop_id"] = $shop["id"];
            $item["shop_name"] = $shop["name"];
            $item["shop_city_id"] = $city["id"];

            $items[] = $item;
        }

        SellerLeger::insert($items);
    }
}
