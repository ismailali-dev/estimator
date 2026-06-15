<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ModulePermission;

class ModulePermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
     public function run(): void
    {
        $modules = [
            ['name' => 'Branding', 'slug' => 'branding', 'sort_order' => 1],
            ['name' => 'Estimate', 'slug' => 'estimate', 'sort_order' => 2],
            ['name' => 'Profit Budget', 'slug' => 'profit_budget', 'sort_order' => 3],
            ['name' => 'Sales Report', 'slug' => 'sales_report', 'sort_order' => 4],
            ['name' => 'Customer', 'slug' => 'customer', 'sort_order' => 5],
            ['name' => 'Supplier', 'slug' => 'supplier', 'sort_order' => 6],
            ['name' => 'Code', 'slug' => 'code', 'sort_order' => 7],
            ['name' => 'Template', 'slug' => 'template', 'sort_order' => 8],
            ['name' => 'Category', 'slug' => 'category', 'sort_order' => 9],
            ['name' => 'Global Settings', 'slug' => 'global_settings', 'sort_order' => 10],
            ['name' => 'Code Book', 'slug' => 'code_book', 'sort_order' => 11],
            
        ];

        foreach ($modules as $module) {
            ModulePermission::updateOrCreate(
                ['slug' => $module['slug']],
                ['name' => $module['name']]
            );
        }
    }
}


