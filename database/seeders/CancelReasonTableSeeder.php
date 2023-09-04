<?php

namespace Database\Seeders;

use App\Models\CancelReason;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CancelReasonTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonFilePath = resource_path("../data/cancel_reason.json");
        $jsonContent = File::get($jsonFilePath);
        $cancelReasonsList = json_decode($jsonContent, true);
        CancelReason::insert($cancelReasonsList);
    }
}
