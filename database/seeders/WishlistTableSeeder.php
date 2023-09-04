<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class WishlistTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonFilePath = resource_path("../data/wishlist.json");
        $jsonContent = File::get($jsonFilePath);
        $wishlistItems = json_decode($jsonContent, true);

        foreach ($wishlistItems as $wishlistItem) {

            $customerId = User::inRandomOrder()->first()["id"];
            $productId = Product::inRandomOrder()->first()["id"];

            unset($wishlistItem["customer_id"]);
            unset($wishlistItem["product_id"]);
            
            $wishlistItems_[] = array_merge($wishlistItem, [
                "customer_id" => $customerId,
                "product_id" => $productId,
            ]);
        }

        Wishlist::insert($wishlistItems_);
    }
}
