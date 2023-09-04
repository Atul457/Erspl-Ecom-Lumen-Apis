<?php

namespace Database\Seeders;

use App\Models\OfferPriceBundling;
use App\Models\Shop;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class OfferPriceBundlingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonFilePath = resource_path("../data/offer_price_bundling.json");
        $jsonContent = File::get($jsonFilePath);
        $offerPriceBundlingItems = json_decode($jsonContent, true);

        foreach ($offerPriceBundlingItems as $offerPriceBundlingItem) {
            $shopId = Shop::inRandomOrder()->first()["id"];

            unset($offerPriceBundlingItem["shop_id"]);
            
            $offerPriceBundlingItems_[] = array_merge($offerPriceBundlingItem, [
                "shop_id" => $shopId
            ]);
        }

        OfferPriceBundling::insert($offerPriceBundlingItems_);
    }
}
