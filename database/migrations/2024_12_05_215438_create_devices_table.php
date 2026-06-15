<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
     public function up()
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deviceable_id'); // ID of the related model
            $table->string('deviceable_type');          // Type of the related model
            $table->string('device_token')->unique();   // Device token (unique)
            $table->enum('device_type', ['ios', 'android', 'web']); // Device type
            $table->json('device_info')->nullable();    // Optional device information
            $table->softDeletes();                     // Adds `deleted_at` column for soft deletes
            $table->timestamps();                      // Adds `created_at` and `updated_at` columns

            $table->index(['deviceable_id', 'deviceable_type']); // Composite index for polymorphic relation
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('devices');
    }
}
