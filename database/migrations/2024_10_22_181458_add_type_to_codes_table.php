<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeToCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::table('codes', function (Blueprint $table) {
            // Add 'type' column with two possible values: 'supplier' or 'customer'
            $table->enum('type', ['supplier', 'customer','both'])->nullable()->after('company_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
       Schema::table('codes', function (Blueprint $table) {
            // Drop the 'type' column if the migration is rolled back
            $table->dropColumn('type');
        });
    }
}
