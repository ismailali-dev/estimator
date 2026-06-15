<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGroupOrderToProductGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        
         if (!Schema::hasColumn('product_groups', 'group_order')) {
            Schema::table('product_groups', function (Blueprint $table) {
                $table->integer('group_order')->default(0)->nullable()->after('company_id');
            });
        }
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_groups', function (Blueprint $table) {
            // Drop the 'group_order' column if this migration is rolled back
            $table->dropColumn('group_order');
        });
    }
}