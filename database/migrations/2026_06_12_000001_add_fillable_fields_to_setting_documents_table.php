<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFillableFieldsToSettingDocumentsTable extends Migration
{
    public function up()
    {
        Schema::table('setting_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('setting_documents', 'document_name')) {
                $table->string('document_name')->nullable()->after('file_type');
            }

            if (!Schema::hasColumn('setting_documents', 'signature_required')) {
                $table->boolean('signature_required')->default(false)->after('document_name');
            }

            if (!Schema::hasColumn('setting_documents', 'signature_path')) {
                $table->string('signature_path')->nullable()->after('signature_required');
            }

            if (!Schema::hasColumn('setting_documents', 'signed_at')) {
                $table->timestamp('signed_at')->nullable()->after('signature_path');
            }

            if (!Schema::hasColumn('setting_documents', 'fields')) {
                $table->json('fields')->nullable()->after('signed_at');
            }
        });
    }

    public function down()
    {
        Schema::table('setting_documents', function (Blueprint $table) {
            if (Schema::hasColumn('setting_documents', 'fields')) {
                $table->dropColumn('fields');
            }
        });
    }
}
