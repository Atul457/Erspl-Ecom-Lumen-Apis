<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\Slider;
use App\Models\SliderProduct;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class SliderProductsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonFilePath = resource_path("../data/tbl_slider_products.json");
        $jsonContent = File::get($jsonFilePath);
        $dataArray = json_decode($jsonContent, true);

        foreach ($dataArray as $data) {
            $shopId = Shop::inRandomOrder()->first()["id"];
            $sliderId = Slider::inRandomOrder()->first()["id"];
            $data["shop_id"] = $shopId;
            $data["slider_id"] = $sliderId;
            $dataArray_[] = $data;
        }

        SliderProduct::insert($dataArray_);
    }
}
