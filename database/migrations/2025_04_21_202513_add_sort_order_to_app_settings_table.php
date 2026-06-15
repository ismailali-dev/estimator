<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSortOrderToAppSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
            Schema::table('app_settings', function (Blueprint $table) {
                $table->integer('sort_order')->default(0)->after('value');
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
}
