<?php

namespace Database\Seeders;

use App\Models\FavShop;
use App\Models\Shop;
use App\Models\Registration;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class FavShopTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonFilePath = resource_path("../data/fav_shop.json");
        $jsonContent = File::get($jsonFilePath);
        $dataArray = json_decode($jsonContent, true);
        foreach ($dataArray as $_) {
            $shopId = Shop::inRandomOrder()->first()["id"];
            $userId = Registration::inRandomOrder()->first()["id"];

            $dataArray_[] = [
                "user_id" => $userId,
                "shop_id" => $shopId,
            ];
        }

        FavShop::insert($dataArray_);
        
    }
}
