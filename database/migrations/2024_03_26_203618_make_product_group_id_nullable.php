<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeProductGroupIdNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
//        Schema::table('codes', function (Blueprint $table) {
//            //
//            $table->dropForeign(['product_group_id']);
//        });
//
//        Schema::table('codes', function (Blueprint $table) {
//            //
//            $table->bigInteger('product_group_id')->nullable()->change();
//        });


        Schema::table('estimate_sheets', function (Blueprint $table) {
            //
            $table->dropForeign(['product_group_id']);
        });

        Schema::table('estimate_sheets', function (Blueprint $table) {
            //
            $table->bigInteger('product_group_id')->nullable()->change();
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
            $table->bigInteger('product_group_id')->nullable(false)->change();
        });
        Schema::table('codes', function (Blueprint $table) {
            $table->foreign('product_group_id')->references('id')->on('product_groups');
        });

        Schema::table('estimate_sheets', function (Blueprint $table) {
            $table->bigInteger('product_group_id')->nullable(false)->change();
        });
        Schema::table('estimate_sheets', function (Blueprint $table) {
            $table->foreign('product_group_id')->references('id')->on('product_groups');
        });
    }
}
