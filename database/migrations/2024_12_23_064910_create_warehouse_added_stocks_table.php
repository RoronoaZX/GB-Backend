<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('warehouse_added_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_stock_report_id')->references('id')->on('warehouse_stock_reports');
            $table->foreignId('raw_material_id')->references('id')->on('raw_materials');
            $table->integer('quantity')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_added_stocks');
    }
};