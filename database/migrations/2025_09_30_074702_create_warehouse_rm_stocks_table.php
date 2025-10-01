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
        Schema::create('warehouse_rm_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->references('id')->on('warehouse');
            $table->foreign('raw_materials_id')->references('id')->on('raw_materials');
            $table->decimal('price_per_price', 10,3)->nullable();
            $table->decimal('quantity', 10,3)->nullable();
            $table->decimal('gram', 10,3)->nullable();
            $table->decimal('kilo', 10,3)->nullable();
            $table->decimal('pcs', 10,3)->nullable();
            $table->decimal('total_grams', 10,3)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_rm_stocks');
    }
};
