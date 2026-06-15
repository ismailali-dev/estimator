<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ChangeColDefaultTypeToZeroCodeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('codes', function () {
            DB::statement("ALTER TABLE codes MODIFY labor_cost DOUBLE(10,2) DEFAULT 0");
            DB::statement("ALTER TABLE codes MODIFY material_cost DOUBLE(10,2) DEFAULT 0");
        
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('codes', function () {
            // Use raw SQL to revert the column types and default values to their previous state
            DB::statement("ALTER TABLE codes MODIFY labor_cost INT DEFAULT NULL");
            DB::statement("ALTER TABLE codes MODIFY material_cost INT DEFAULT NULL");
        });
    }
}
