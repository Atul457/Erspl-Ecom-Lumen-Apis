<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\Slider;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class SliderTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonFilePath = resource_path("../data/slider.json");
        $jsonContent = File::get($jsonFilePath);
        $dataArray = json_decode($jsonContent, true);

        foreach ($dataArray as $shopTime) {
            $shopId = Shop::inRandomOrder()->first()["id"];
            $userId = User::inRandomOrder()->first()["id"];

            unset($shopTime["shop_id"]);
            unset($shopTime["created_by"]);
            
            $dataArray_[] = array_merge($shopTime, [
                "shop_id" => $shopId,
                "created_by" => $userId,
            ]);
        }

        Slider::insert($dataArray_);
    }
}
