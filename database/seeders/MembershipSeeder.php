<?php

namespace Database\Seeders;

use App\Models\Membership;
use App\Models\Subscription;
use Illuminate\Database\Seeder;

class MembershipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        // Truncate the table before seeding to avoid duplicate data
        Membership::truncate();

        // Seed data
        Membership::create([
            'title' => '3x Code Template',
            'description' => 'Allow for more 3+ code template',
            'slug' => 'template',
            'amount' => 50
        ]);

        Membership::create([
            'title' => 'Customize Invoice',
            'description' => 'Customize invoice with logo and color',
            'slug' => 'invoice',
            'amount' => 25
        ]);

        Membership::create([
            'title' => 'Multiple User Access',
            'description' => 'Add up to 3 users',
            'slug' => 'multi_user_access',
            'amount' => 100
        ]);

        Membership::create([
            'title' => 'Credit Card Processing',
            'description' => 'Credit Card Processing with EZ Estimator',
            'slug' => 'credit_card_support',
            'amount' => 25
        ]);

        Membership::create([
            'title' => 'Code Book Report',
            'description' => 'Download/email a PDF Code Book',
            'slug' => 'code_book_support',
            'amount' => 10
        ]);

        Membership::create([
            'title' => 'Built-in Supplier',
            'slug' => 'home_depot_support',
            'description' => 'Home Depot or Lowes',
            'amount' => 350
        ]);

        Membership::create([
            'title' => 'Global Setting',
            'slug' => 'global_setting_support',
            'description' => 'Global Setting',
            'amount' => 10
        ]);

    }
}
