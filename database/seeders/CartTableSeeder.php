<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Registration;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CartTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonFilePath = resource_path("../data/cart.json");
        $jsonContent = File::get($jsonFilePath);
        $cartItems = json_decode($jsonContent, true);

        foreach ($cartItems as $cartItem) {
            $shopId = Shop::inRandomOrder()->first()["id"];
            $userId = Registration::inRandomOrder()->first()["id"];
            $productId = Product::inRandomOrder()->first()["id"];

            unset($cartItem["shop_id"]);
            unset($cartItem["user_id"]);
            unset($cartItem["product_id"]);
            
            $cartItems_[] = array_merge($cartItem, [
                "shop_id" => $shopId,
                "user_id" => $userId,
                "product_id" => $productId,
            ]);
        }

        Cart::insert($cartItems_);
    }
}
