<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        Setting::create([
            'user_id' => 1,
            "type" => 1,
            "title" => "Material Markup",
            "value" => "10",
        ]);

        Setting::create([
            'user_id' => 1,
            "type" => 1,
            "title" => "Labour Markup",
            "value" => "10",
        ]);

        Setting::create([
            'user_id' => 1,
            "type" => 1,
            "title" => "Tax",
            "value" => "10",
        ]);


        Setting::create([
            'user_id' => 1,
            "type" => 2,
            "title" => "Sales Manager",
            "value" => "10",
        ]);

        Setting::create([
            'user_id' => 1,
            "type" => 2,
            "title" => "Office Assistant",
            "value" => "10",
        ]);

        Setting::create([
            'user_id' => 1,
            "type" => 2,
            "title" => "Project Manager",
            "value" => "10",
        ]);
    }
}
