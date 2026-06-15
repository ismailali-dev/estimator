<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppUpdatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_updates', function (Blueprint $table) {
            $table->id();
            $table->enum('update_type', ['android', 'ios']); // Platform
            $table->string('version'); // Version e.g. 2.0.0
            $table->boolean('force_update')->default(true); // Force update always true
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
        Schema::dropIfExists('app_updates');
    }
}
