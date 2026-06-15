<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParentIdToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Add the parent_id column, nullable for top-level users
            $table->unsignedBigInteger('parent_id')->nullable()->after('id');
            
            // Add a foreign key that references the id on the users table
            $table->foreign('parent_id')->references('id')->on('users')->onDelete('set null');
        });
    }
    
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the foreign key constraint before dropping the column
            $table->dropForeign(['parent_id']);
            
            // Now drop the parent_id column
            $table->dropColumn('parent_id');
        });
    }

}
