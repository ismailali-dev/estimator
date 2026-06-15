<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAndAdjustColsEstimationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('estimates', function (Blueprint $table) {
            //
            $table->dropColumn("type");
        });
        Schema::table('estimates', function (Blueprint $table) {
            //
            $table->tinyInteger("is_saved")->default(0);
            $table->string("key");
            $table->tinyInteger("type")->default(1);
        });


        Schema::table('estimate_sheets', function (Blueprint $table) {
            //
            $table->double("misc_cost")->nullable()->after('material_cost');
        });

        //
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::table('estimates', function (Blueprint $table) {
            //
            $table->dropColumn("is_saved");
            $table->dropColumn("key");
            $table->dropColumn("type");
        });
        Schema::table('estimates', function (Blueprint $table) {
            //
            $table->enum('type', ['estimate_order', 'extra_work_order', 'credit_work_order'])
                ->default('estimate_order');
        });

        Schema::table('estimate_sheets', function (Blueprint $table) {
            //
            $table->dropColumn("misc_cost");
        });
    }
}
