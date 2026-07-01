<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEstimateIdToSettingDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('setting_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('setting_documents', 'estimate_id')) {
                $table->unsignedBigInteger('estimate_id')->nullable()->after('company_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('setting_documents', function (Blueprint $table) {
            if (Schema::hasColumn('setting_documents', 'estimate_id')) {
                $table->dropColumn('estimate_id');
            }
        });
    }
}