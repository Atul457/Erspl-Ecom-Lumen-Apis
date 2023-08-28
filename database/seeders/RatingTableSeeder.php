<?php

namespace Database\Seeders;

use App\Models\Rating;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class RatingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonFilePath = resource_path("../data/rating.json");
        $jsonContent = File::get($jsonFilePath);
        $dataArray = json_decode($jsonContent, true);
        foreach ($dataArray as $rating) {
            $shopId = Shop::inRandomOrder()->first()["id"];
            $userId = User::inRandomOrder()->first()["id"];

            unset($rating["shop_id"]);
            unset($rating["user_id"]);
            
            $dataArray_[] = array_merge($rating, [
                "user_id" => $userId,
                "shop_id" => $shopId,
            ]);
        }

        Rating::insert($dataArray_);

    }
}
