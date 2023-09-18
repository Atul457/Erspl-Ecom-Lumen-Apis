<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\OrderDeliveryLogs;
use App\Models\Registration;
use App\Models\Shop;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class OrderDeliveryLogsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonFilePath = resource_path("../data/tbl_order_delivery_logs.json");
        $jsonContent = File::get($jsonFilePath);
        $items = json_decode($jsonContent, true);
        $items_ = [];

        foreach ($items as $item) {

            $shop = Shop::inRandomOrder()->first()->toArray();
            $employee = Employee::inRandomOrder()->first()->toArray();
            $user = Registration::inRandomOrder()->first()->toArray();

            $item["shop_id"] = $item["shop_id"] ?  $shop["id"] : null;
            $item["cust_id"] = $item["cust_id"] ?  $user["id"] : null;
            $item["emp_id"] = $item["emp_id"] ?  $employee["id"] : null;
            $items_[] = $item;
        }

        OrderDeliveryLogs::insert($items_);
    }
}
