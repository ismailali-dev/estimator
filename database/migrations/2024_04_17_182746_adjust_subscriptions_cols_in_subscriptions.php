<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AdjustSubscriptionsColsInSubscriptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('memberships', function (Blueprint $table) {
            //
            $table->string("slug")->after("title");
            $table->dropColumn("template_support");
            $table->dropColumn("templates");
            $table->dropColumn("customize_invoice");
            $table->dropColumn("multi_user_access");
            $table->dropColumn("multi_user");
            $table->dropColumn("credit_card_support");
            $table->dropColumn("code_book_support");
            $table->dropColumn("home_depot_support");
            $table->dropColumn("profit_budget_support");

        });

        Schema::table('subscriptions', function (Blueprint $table) {
            //
            $table->string("slug")->after("membership_id");
            $table->dropColumn("template_support");
            $table->dropColumn("templates");
            $table->dropColumn("customize_invoice");
            $table->dropColumn("multi_user_access");
            $table->dropColumn("multi_user");
            $table->dropColumn("credit_card_support");
            $table->dropColumn("code_book_support");
            $table->dropColumn("home_depot_support");
            $table->dropColumn("profit_budget_support");

        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('memberships', function (Blueprint $table) {
            //
            $table->dropColumn("slug");
            $table->boolean('template_support')->default(false);
            $table->tinyInteger('templates')->default(0);
            $table->boolean('customize_invoice')->default(false);
            $table->boolean('multi_user_access')->default(false);
            $table->tinyInteger('multi_user')->default(0);
            $table->boolean('credit_card_support')->default(false);
            $table->boolean('code_book_support')->default(false);
            $table->boolean('home_depot_support')->default(false);
            $table->boolean('profit_budget_support')->default(false);

        });

        Schema::table('subscriptions', function (Blueprint $table) {
            //
            $table->dropColumn("slug");
            $table->boolean('template_support')->default(false);
            $table->tinyInteger('templates')->default(0);
            $table->boolean('customize_invoice')->default(false);
            $table->boolean('multi_user_access')->default(false);
            $table->tinyInteger('multi_user')->default(0);
            $table->boolean('credit_card_support')->default(false);
            $table->boolean('code_book_support')->default(false);
            $table->boolean('home_depot_support')->default(false);
            $table->boolean('profit_budget_support')->default(false);

        });
    }
}
