<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('template_user_syncs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');        // User who synced the template
            $table->unsignedBigInteger('template_id');    // Original template ID
            $table->enum('status', ['synced', 'updated', 'deleted'])->default('synced');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('template_id')->references('id')->on('templates')->onDelete('cascade');

            // Prevent duplicate syncs
            $table->unique(['user_id', 'template_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_user_syncs');
    }
};
