<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateProductIdsInMembershipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('memberships', function (Blueprint $table) {
            // Rename existing columns to clarify they are for yearly
            $table->renameColumn('app_store_product_id', 'app_store_yearly_product_id');
            $table->renameColumn('play_store_product_id', 'play_store_yearly_product_id');
        });

        Schema::table('memberships', function (Blueprint $table) {
            // Add new columns for monthly product IDs
            $table->string('app_store_monthly_product_id')->nullable()->after('app_store_yearly_product_id');
            $table->string('play_store_monthly_product_id')->nullable()->after('play_store_yearly_product_id');
        });
    }

    public function down()
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->dropColumn('app_store_monthly_product_id');
            $table->dropColumn('play_store_monthly_product_id');
        });

        Schema::table('memberships', function (Blueprint $table) {
            // Revert renamed columns
            $table->renameColumn('app_store_yearly_product_id', 'app_store_product_id');
            $table->renameColumn('play_store_yearly_product_id', 'play_store_product_id');
        });
    }
    
}
