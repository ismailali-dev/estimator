<?php

namespace Database\Seeders;

use App\Models\ProductGroup;
use Illuminate\Database\Seeder;

class ProductGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ProductGroup::create([
            "group_name" => "Apartment",
            "status" => 1,
        ]);
        ProductGroup::create([
            "group_name" => "House",
            "status" => 1,
        ]);
        ProductGroup::create([
            "group_name" => "Room",
            "status" => 1,
        ]);
        ProductGroup::create([
            "group_name" => "Bed Room",
            "status" => 1,
        ]);
        ProductGroup::create([
            "group_name" => "Kitchen",
            "status" => 1,
        ]);
        ProductGroup::create([
            "group_name" => "Bathroom",
            "status" => 1,
        ]);
    }
}
