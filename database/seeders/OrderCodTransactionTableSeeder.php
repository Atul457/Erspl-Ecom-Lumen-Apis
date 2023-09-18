<?php

namespace Database\Seeders;

use App\Models\OrderCodTransaction;
use App\Models\Shop;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class OrderCodTransactionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonFilePath = resource_path("../data/tbl_order_cod_transaction.json");
        $jsonContent = File::get($jsonFilePath);
        $items = json_decode($jsonContent, true);
        $items_ = [];

        foreach ($items as $item) {
            $shop = Shop::inRandomOrder()->first()->toArray();

            $item["shop_id"] = $shop["id"];
            $items_[] = $item;
        }

        OrderCodTransaction::insert($items_);
    }
}
