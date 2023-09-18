<?php

namespace Database\Seeders;

use App\Models\HsnCode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class HsnCodeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonFilePath = resource_path("../data/tbl_hsncode.json");
        $jsonContent = File::get($jsonFilePath);
        $dataArray = json_decode($jsonContent, true);
        HsnCode::insert($dataArray);
    }
}
