<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRoleColInUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            //
            $table->dropColumn("company_name");
            $table->dropColumn("company_address");
            $table->dropColumn("license_no");
            $table->tinyInteger("is_admin")->default(1)->after("is_archive");
            $table->string("position")->default("admin")->after("is_archive");
            $table->bigInteger("company_id")->nullable()->after("is_archive");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
            $table->string("company_name");
            $table->string("company_address");
            $table->string("license_no");
            $table->dropColumn("is_admin");
            $table->dropColumn("position");
            $table->dropColumn("company_id");
        });
    }
}
