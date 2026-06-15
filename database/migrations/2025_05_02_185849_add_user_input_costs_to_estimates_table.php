<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserInputCostsToEstimatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
    {
        Schema::table('estimate_sheets', function (Blueprint $table) {
            $table->decimal('actual_user_input_labor_cost', 15, 2)->nullable();
            $table->decimal('actual_user_input_material_cost', 15, 2)->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropColumn(['actual_user_input_labor_cost', 'actual_user_input_material_cost']);
        });
    }
}
