<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        DB::table('users')->insert([
            'name' => 'usama',
            "first_name" => "Usama",
            "last_name" => "Hussain",
            "phone" => "(792) 638-3031 x885",
            "company_name" => "EZ Estimator",
            "company_address" => "131 Bobbie Point Suite 889",
            "type" => "ADMIN",
            'email' => 'admin@domain.com',
            'password' => Hash::make('12345678'),
        ]);
    }
}
