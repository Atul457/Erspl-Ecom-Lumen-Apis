<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\ShopTime;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ShopTimeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonFilePath = resource_path("../data/shop_time.json");
        $jsonContent = File::get($jsonFilePath);
        $dataArray = json_decode($jsonContent, true);

        foreach ($dataArray as $shopTime) {
            $shopId = Shop::inRandomOrder()->first()["id"];

            unset($shopTime["shop_id"]);
            
            $dataArray_[] = array_merge($shopTime, [
                "shop_id" => $shopId,
            ]);
        }

        ShopTime::insert($dataArray_);
    }
}
 