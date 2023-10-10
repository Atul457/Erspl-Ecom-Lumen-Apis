<?php

namespace Database\Seeders;

use App\Models\Registration;
use App\Models\UserSearchLog;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class UserSearchLogTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonFilePath = resource_path("../data/tbl_user_search_logs.json");
        $jsonContent = File::get($jsonFilePath);
        $dataArray = json_decode($jsonContent, true);

        foreach ($dataArray as $item) {
            $userId = Registration::inRandomOrder()->first()["id"];
            $item["user_id"] =  $userId;
            $dataArray_[] = $item;
        }

        UserSearchLog::insert($dataArray_);
    }
}
