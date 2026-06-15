<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColsForProfitBudgets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('estimates', function (Blueprint $table) {
            $table->double('contract_price')->default(0)->nullable();
            $table->double('extra_work_orders')->default(0)->nullable();
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropColumn('contract_price');
            $table->dropColumn('extra_work_orders');
        });
    }
}
