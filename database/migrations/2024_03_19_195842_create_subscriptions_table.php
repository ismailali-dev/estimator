<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->text('title');
            $table->bigInteger('user_id');
            $table->bigInteger('company_id')->nullable();
            $table->bigInteger('membership_id');

            $table->boolean('template_support')->default(false);
            $table->tinyInteger('templates')->default(0);
            $table->boolean('customize_invoice')->default(false);
            $table->boolean('multi_user_access')->default(false);
            $table->tinyInteger('multi_user')->default(0);
            $table->boolean('credit_card_support')->default(false);
            $table->boolean('code_book_support')->default(false);
            $table->boolean('home_depot_support')->default(false);
            $table->boolean('profit_budget_support')->default(false);
            $table->double("amount");
            $table->string("renewable_type")->default('year');
            $table->boolean("is_cancelled")->default(false);
            $table->timestamp("cancelled_at")->nullable();
            $table->date('renewable_date');
            $table->string('subscription_id');
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
        Schema::dropIfExists('subscriptions');
    }
}
