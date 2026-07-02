<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSortOrderToSettingDocumentsTable extends Migration
{
    public function up()
    {
        Schema::table('setting_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('setting_documents', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('document_name');
            }
        });

        if (Schema::hasColumn('setting_documents', 'sort_order')) {
            DB::statement('UPDATE setting_documents SET sort_order = id WHERE sort_order = 0 OR sort_order IS NULL');
        }
    }

    public function down()
    {
        Schema::table('setting_documents', function (Blueprint $table) {
            if (Schema::hasColumn('setting_documents', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
        });
    }
}
