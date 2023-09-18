<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Employee;
use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class EmployeeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonFilePath = resource_path("../data/tbl_employee.json");
        $jsonContent = File::get($jsonFilePath);
        $employees = json_decode($jsonContent, true);
        $employees_ = [];

        foreach ($employees as $employee) {
            $state = State::inRandomOrder()->first()->toArray();
            $city = City::inRandomOrder()->first()->toArray();

            $employee["state_id"] = $state["id"];
            $employee["city_id"] = $city["id"];
            $employees_[] = $employee;
        }

        Employee::insert($employees_);
    }
}
