<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColsInEstimateSheetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('estimate_sheets', function (Blueprint $table) {
            //
            $table->double("actual_material_cost")->nullable()->after("row_total");
            $table->double("actual_labor_cost")->nullable()->after("row_total");
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
            //
            $table->dropColumn("actual_material_cost");
            $table->dropColumn("actual_labor_cost");
        });
    }
}
