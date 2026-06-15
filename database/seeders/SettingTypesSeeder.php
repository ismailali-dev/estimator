<?php

namespace Database\Seeders;

use App\Models\SettingType;
use Illuminate\Database\Seeder;

class SettingTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        SettingType::create([
            "id" => 1,
            "title" => "Global Settings For Estimates"
        ]);

        SettingType::create([
            "id" => 2,
            "title" => "Global Settings For Profit Budget"
        ]);
    }
}
