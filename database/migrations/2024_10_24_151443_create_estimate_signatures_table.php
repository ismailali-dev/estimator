<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEstimateSignaturesTable extends Migration
{
    public function up()
    {
        Schema::create('estimate_signatures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('estimate_id');
            $table->unsignedBigInteger('user_id'); // For tracking the user who is editing

            // Fields for estimator's signature and date
            $table->text('estimator_signature')->nullable(); 
            $table->timestamp('estimator_signed_at')->nullable();

            // Fields for customer's signature and date
            $table->text('customer_signature')->nullable(); 
            $table->timestamp('customer_signed_at')->nullable();

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('estimate_id')->references('id')->on('estimates')->onDelete('cascade');

            // Ensure there is only one entry per user and estimate
            $table->unique(['estimate_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('estimate_signatures');
    }
}
