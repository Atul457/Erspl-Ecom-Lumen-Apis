<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Product;
use App\Models\Registration;
use App\Models\ReturnOrder;
use App\Models\Shop;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ReturnOrderTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonFilePath = resource_path("../data/tbl_return_order.json");
        $jsonContent = File::get($jsonFilePath);
        $items_ = json_decode($jsonContent, true);
        $items = [];

        foreach ($items_ as $item) {
            $customerId = Registration::inRandomOrder()->first()->toArray()["id"];
            $shopId = Shop::inRandomOrder()->first()->toArray()["id"];
            $deliveryBoyId = (Employee::inRandomOrder()->first()->toArray())["id"];
            $product = Product::inRandomOrder()->first();
            $productId = $product["id"];
            $productName = $product["name"];
            
            $item["shop_id"] = $shopId;
            $item["product_id"] = $productId;
            $item["customer_id"] = $customerId;
            $item["product_name"] = $productName;
            $item["deliveryboy_id"] = $deliveryBoyId;

            $items[] = $item;
        }

        ReturnOrder::insert($items);
    }
}
