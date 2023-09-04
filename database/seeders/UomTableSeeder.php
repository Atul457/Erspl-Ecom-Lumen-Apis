<?php

namespace Database\Seeders;

use App\Models\UomType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class UomTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonFilePath = resource_path("../data/uom_type.json");
        $jsonContent = File::get($jsonFilePath);
        $uonTypes = json_decode($jsonContent, true);
        UomType::insert($uonTypes);
    }
}
