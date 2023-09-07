<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class OrderTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonFilePath = resource_path("../data/order.json");
        $jsonContent = File::get($jsonFilePath);
        $orderTableItems = json_decode($jsonContent, true);
        $orderTableItems_ = [];

        foreach ($orderTableItems as $order) {
            $shop = Shop::inRandomOrder()->first()->toArray();
            $productId = Product::inRandomOrder()->first()["id"];
            $customer = User::inRandomOrder()->first()->toArray();

            $order["shop_id"] = $shop["id"];
            $order["customer_id"] = $customer["id"];
            $order["product_id"] = $productId;
            $order["shop_city_id"] = $shop["city_id"];
            $order["name"] = $customer["first_name"]." ".$customer["middle_name"]." ".$customer["last_name"];
            $order["email"] = $customer["email"];
            $order["mobile"] = $customer["mobile"];

            $orderTableItems_[] = $order;
        }

        Order::insert($orderTableItems_);
    }
}
