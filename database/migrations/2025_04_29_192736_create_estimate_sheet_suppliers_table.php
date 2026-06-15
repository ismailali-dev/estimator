<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEstimateSheetSuppliersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('estimate_sheet_suppliers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('estimate_sheet_id')->constrained()->onDelete('cascade');
                $table->enum('type', ['Labor', 'Material']);
                $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
                $table->timestamps();
                $table->softDeletes(); // 👈 Soft delete support
                $table->unique(['estimate_sheet_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('estimate_sheet_suppliers');
    }
}
