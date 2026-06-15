<?php

namespace Database\Seeders;

use App\Models\EstimateType;
use Illuminate\Database\Seeder;

class EstimateTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        EstimateType::create([
            "name" => "Estimate Order"
        ]);
        EstimateType::create([
            "name" => "Extra Work Order"
        ]);
        EstimateType::create([
            "name" => "Credit Work Order"
        ]);
    }
}
