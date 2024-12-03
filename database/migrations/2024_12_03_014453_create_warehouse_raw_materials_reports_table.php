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
        Schema::create('warehouse_raw_materials_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->references('id')->on('warehouses');
            // $table->foreignId('recipe_id')->references('id')->on('recipes');
            // $table->foreignId('baker_report_id')->references('id')->on('baker_reports');
            $table->foreignId('raw_material_id')->references('id')->on('raw_materials');
            $table->integer('total_quantity')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_raw_materials_reports');
    }
};
