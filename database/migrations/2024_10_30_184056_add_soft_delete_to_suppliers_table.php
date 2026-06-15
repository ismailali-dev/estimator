<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftDeleteToSuppliersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    
     public function up()
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Add the deleted_at column for soft deletes
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Drop the deleted_at column
            $table->dropSoftDeletes();
        });
    }
    
}
