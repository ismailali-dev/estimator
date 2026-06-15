<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEstimateSheetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('estimate_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->constrained();
            $table->foreignId("estimate_id")->constrained();
            $table->foreignId("code_id")->constrained();
            $table->foreignId("template_id")->nullable()->constrained();
            $table->foreignId("product_group_id")->nullable()->constrained();
            $table->integer("quantity");
            $table->string("unit");
            $table->decimal('labor_cost', 10, 2)->default(0);
            $table->decimal('material_cost', 10, 2)->default(0);
            $table->integer("row_total");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('estimate_sheets');
    }
}
