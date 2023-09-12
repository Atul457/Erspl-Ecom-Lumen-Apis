<?php

namespace Database\Seeders;

use App\Models\ACategory;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CouponTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonFilePath = resource_path("../data/coupon.json");
        $jsonContent = File::get($jsonFilePath);
        $couponItems = json_decode($jsonContent, true);

        foreach ($couponItems as $coupon) {

            $categoryId = ACategory::inRandomOrder()->first()["id"];
            $userId = User::inRandomOrder()->first()["id"];
            $productId = Product::inRandomOrder()->first()["id"];

            $coupon["user_id"] = $userId;
            $coupon["product_id"] = $productId;
            $coupon["category_id"] = $categoryId;
            
            $couponItems_[] = $coupon;
        }

        Coupon::insert($couponItems_);
    }
}
