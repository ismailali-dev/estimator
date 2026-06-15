<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompanyIdInCodesTemplatesAndSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('codes', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('company_id')->after("user_id");
        });

        Schema::table('templates', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('company_id')->after("user_id");
        });

        Schema::table('settings', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('company_id')->after("user_id");
        });

        Schema::table('product_groups', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('company_id')->after("user_id")->nullable();
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
            //
            $table->dropColumn('company_id');
        });
        Schema::table('templates', function (Blueprint $table) {
            //
            $table->dropColumn('company_id');
        });
        Schema::table('settings', function (Blueprint $table) {
            //
            $table->dropColumn('company_id');
        });

        Schema::table('product_groups', function (Blueprint $table) {
            //
            $table->dropColumn('company_id');
        });
    }
}
