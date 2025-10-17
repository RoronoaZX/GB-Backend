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
        Schema::create('supplier_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_record_id')->references('id')->on('supplier_records');
            $table->foreignId('raw_material_id')->references('id')->on('raw_materials');
            $table->decimal('quantity', 10,3)->nullable();
            $table->decimal('price_per_gram', 10,9)->nullable();
            $table->decimal('price_per_unit', 10,9)->nullable();
            $table->decimal('pcs', 10,3)->nullable();
            $table->decimal('kilo', 10,3)->nullable();
            $table->decimal('gram', 10,3)->nullable();
            $table->string('category')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_ingredients');
    }
};
