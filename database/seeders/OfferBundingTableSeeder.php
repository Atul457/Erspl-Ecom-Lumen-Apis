<?php

namespace Database\Seeders;

use App\Models\OfferBundling;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class OfferBundingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonFilePath = resource_path("../data/tbl_offer_bundling.json");
        $jsonContent = File::get($jsonFilePath);
        $offerBundles = json_decode($jsonContent, true);
        OfferBundling::insert($offerBundles);
    }
}
