<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('contractor_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contractor_id');
            $table->unsignedBigInteger('estimate_id');
            $table->unsignedBigInteger('customer_id');
            $table->decimal('amount', 10, 2)->nullable();
            $table->decimal('tax', 10, 2)->nullable();
            $table->decimal('grand_total', 10, 2)->nullable();
            $table->string('status'); // e.g., pending, completed, failed
            $table->string('stripe_transaction_id')->nullable();
            $table->timestamps();

            // Foreign keys
            // $table->foreign('contractor_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('estimate_id')->references('id')->on('estimates')->onDelete('cascade');
            // $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('contractor_transactions');
    }
};

