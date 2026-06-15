<?php

namespace Database\Seeders;

use App\Models\JobTypes;
use Illuminate\Database\Seeder;

class JobTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        JobTypes::create([
            "title" => "Contractor"
        ]);
        JobTypes::create([
            "title" => "Sub Contractor"
        ]);
    }
}
