<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CityTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        $jsonFilePath = resource_path("../data/tbl_city.json");
        $jsonContent = File::get($jsonFilePath);
        $dataArray = json_decode($jsonContent, true);
        City::insert($dataArray);
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
}
