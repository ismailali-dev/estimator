<?php

namespace Database\Seeders;

use App\Models\HomeCards;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class HomeDepotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $home_depot_support = Supplier::where("subscription_slug","home_depot_support")->first();
        if(!$home_depot_support){
            Supplier::create([
                "full_name" => "Home Depot",
                "phone" => "+1343034343",
                "email" => "homedepot@domain.com",
                "address" => "No address found",
                "city" => "LA",
                "state" => 35,
                "user_id" => NULL,
                "subscription_slug" => "home_depot_support"
            ]);
        }
    }
}
