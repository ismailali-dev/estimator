<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPurchasingCostToEstimateSheetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
        public function up()
        {
            Schema::table('estimate_sheets', function (Blueprint $table) {
                // Add the purchasing_cost column as double
                $table->double('purchasing_cost')->nullable()->after('extra_work_orders');
            });
        }
    
        /**
         * Reverse the migrations.
         *
         * @return void
         */
        public function down()
        {
            Schema::table('estimate_sheets', function (Blueprint $table) {
                // Remove the purchasing_cost column
                $table->dropColumn('purchasing_cost');
            });
        }
}
