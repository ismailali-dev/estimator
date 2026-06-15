<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPlatformAndStatusToSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->enum('platform', ['stripe', 'google', 'apple'])->default('stripe')->after('is_active');
            $table->string('purchase_token')->nullable()->after('platform');
            $table->enum('status', ['active', 'cancelled', 'expired', 'pending'])->default('pending')->after('purchase_token');
        });
    }

    public function down()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['platform', 'purchase_token', 'status']);
        });
    }
}
