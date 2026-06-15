<?php

namespace Database\Seeders;

use App\Models\HomeCards;
use Illuminate\Database\Seeder;

class HomeCardTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        HomeCards::create([
            "title" => "50% INCREASE SALES",
            "link" => "https://ez-estimater.techidara.com",
            "short_detail" => "Lorem ipsum dolor sit amet consectetur. Integer enim quis mollis",
            "type" => 3,
            "status" => 1
        ]);
        HomeCards::create([
            "title" => "75% INCREASE SALES",
            "link" => "https://ez-estimater.techidara.com",
            "short_detail" => "Lorem ipsum dolor sit amet consectetur. Integer enim quis mollis",
            "type" => 2,
            "status" => 1
        ]);
        HomeCards::create([
            "title" => "60% INCREASE SALES",
            "link" => "https://ez-estimater.techidara.com",
            "short_detail" => "Lorem ipsum dolor sit amet consectetur. Integer enim quis mollis",
            "type" => 1,
            "status" => 1
        ]);
    }
}
