<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOperatorAdjustFieldsToCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('codes', function (Blueprint $table) {
            $table->enum('operator', ['divide', 'multiply'])->nullable()->after('type');
            $table->decimal('adjust_num', 12, 2)->nullable()->after('operator');
            $table->decimal('adjusted_cost', 12, 2)->nullable()->after('adjust_num');
        });
    }

    public function down(): void
    {
        Schema::table('codes', function (Blueprint $table) {
            $table->dropColumn(['operator', 'adjust_num', 'adjusted_cost']);
        });
    }
}
