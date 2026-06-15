<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string("first_name");
            $table->string("last_name");
            $table->string('phone');
            $table->string("company_name");
            $table->string("company_address");
            $table->string("license_no")->nullable();
            $table->string("job_type")->default(0);
            $table->string("verification_code")->nullable();
            $table->tinyInteger('status')->default('1');
            $table->tinyInteger('is_archive')->default(0);
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
            $table->dropColumn("first_name");
            $table->dropColumn("last_name");
            $table->dropColumn('phone');
            $table->dropColumn("company_name");
            $table->dropColumn("company_address");
            $table->dropColumn("license_no");
            $table->dropColumn("job_type");
            $table->dropColumn("verification_code");
            $table->dropColumn('status');
            $table->dropColumn('is_archive');
        });
    }
}
