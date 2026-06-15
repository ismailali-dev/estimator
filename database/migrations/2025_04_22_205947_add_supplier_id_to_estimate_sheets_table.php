<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSupplierIdToEstimateSheetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('estimate_sheets', function (Blueprint $table) {
            $table->string('supplier_name')->nullable()->after('contract_price');
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
            $table->dropColumn('supplier_name');
        });
    }
}
