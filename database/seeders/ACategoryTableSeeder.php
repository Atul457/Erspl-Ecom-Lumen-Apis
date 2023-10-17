<?php

namespace Database\Seeders;

use App\Models\ACategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ACategoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonFilePath = resource_path("../data/tbl_acategory.json");
        $jsonContent = File::get($jsonFilePath);
        $dataArray = json_decode($jsonContent, true);
        ACategory::insert($dataArray);
    }
}
