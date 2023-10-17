<?php

namespace Database\Seeders;

use App\Models\Industry;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class IndustriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $jsonFilePath = resource_path("../data/tbl_industries.json");
        $jsonContent = File::get($jsonFilePath);
        $dataArray = json_decode($jsonContent, true);
        Industry::insert($dataArray);
    }
}
