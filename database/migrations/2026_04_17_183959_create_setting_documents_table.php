<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
{
    Schema::create('setting_documents', function (Blueprint $table) {
        $table->id();

        // ✅ correct types (no foreign keys)
        $table->unsignedBigInteger('user_id')->nullable();
        $table->unsignedBigInteger('company_id')->nullable();

        $table->string('document_type')->nullable(); // workers_comp, contract, invoice
        $table->string('file_path')->nullable();
        $table->string('file_type')->nullable();

        $table->timestamps();
        $table->softDeletes();
    });
}

public function down()
{
    Schema::dropIfExists('setting_documents');
}
}
