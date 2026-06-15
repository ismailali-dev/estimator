<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemplateCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('template_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("template_id");
            $table->foreign("template_id")->references("id")->on("templates");
            $table->unsignedBigInteger("code_id");
            $table->foreign("code_id")->references("id")->on("codes");
            $table->unsignedBigInteger("user_id");
            $table->foreign("user_id")->references("id")->on("users");
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
        Schema::dropIfExists('template_codes');
    }
}
