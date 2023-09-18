<?php

namespace Database\Seeders;

use App\Models\NotificationReceiveLogs as ModelsNotificationReceiveLogs;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class NotificationReceiveLogs extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonFilePath = resource_path("../data/tbl_notification_receive_logs.json");
        $jsonContent = File::get($jsonFilePath);
        $dataArray = json_decode($jsonContent, true);
        ModelsNotificationReceiveLogs::insert($dataArray);
    }
}
