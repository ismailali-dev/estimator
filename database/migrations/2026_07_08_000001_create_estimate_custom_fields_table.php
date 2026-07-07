<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEstimateCustomFieldsTable extends Migration
{
    public function up()
    {
        Schema::create('estimate_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('estimate_id');
            $table->string('custom_string')->nullable();
            $table->integer('custom_integer')->nullable();
            $table->timestamps();

            $table->foreign('estimate_id')->references('id')->on('estimates')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('estimate_custom_fields');
    }
}