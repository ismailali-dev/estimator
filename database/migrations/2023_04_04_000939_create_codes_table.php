<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('codes', function (Blueprint $table) {
            $table->id();
            $table->string("code_name");
            $table->date("code_date");
            $table->unsignedBigInteger("product_group_id");
            $table->integer("sort_order");
            $table->unsignedBigInteger("supplier_id");
            $table->string("product_name");
            $table->string("sku_no")->nullable();
            $table->integer("model_no")->nullable();
            $table->enum("unit_of_measure", ["Sqft", "Ft"]);
            $table->integer("labor_cost");
            $table->integer("material_cost");
            $table->integer("misc_cost")->nullable();
            $table->string("description")->nullable();
            $table->string("image")->default(0);
            $table->unsignedBigInteger("user_id");
            $table->foreign("product_group_id")->references("id")->on("product_groups");
            $table->foreign("user_id")->references("id")->on("users");
            $table->foreign("supplier_id")->references("id")->on("suppliers");
            $table->tinyInteger("status")->default(1);
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
        Schema::dropIfExists('codes');
    }
}
