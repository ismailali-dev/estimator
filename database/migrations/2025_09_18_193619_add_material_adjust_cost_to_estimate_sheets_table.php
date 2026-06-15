<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMaterialAdjustCostToEstimateSheetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('estimate_sheets', function (Blueprint $table) {
            $table->decimal('material_adjust_cost', 15, 2)->nullable()->after('actual_user_input_material_cost');
        });
    }
    
    public function down()
    {
        Schema::table('estimate_sheets', function (Blueprint $table) {
            $table->dropColumn('material_adjust_cost');
        });
    }
}
