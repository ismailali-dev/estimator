<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRawPasswordToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
     public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Add a new column for raw password
            $table->string('raw_password')->nullable()->after('password'); // Adjust type as necessary
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
            // Drop the raw_password column
            $table->dropColumn('raw_password');
        });
    }
}
