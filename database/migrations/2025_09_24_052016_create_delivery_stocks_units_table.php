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
        Schema::create('delivery_stocks_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rm_delivery_id')->references('id')->on('raw_materials_deliveries');
            $table->foreignId('raw_materials_id')->references('id')->on('raw_materials');
            $table->string('unit_type')->nullable();
            $table->string('category')->nullable();
            $table->decimal('quantity', 10,3)->nullable();
            $table->decimal('price_per_unit', 10,3)->nullable();
            $table->decimal('price_per_gram', 10,3)->nullable();
            $table->decimal('gram', 10,3)->nullable();
            $table->decimal('pcs', 10,3)->nullable();
            $table->decimal('kilo', 10,3)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_stocks_units');
    }
};
