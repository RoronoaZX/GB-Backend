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
        Schema::create('branch_rm_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_material_id')->references('id')->on('raw_materials');
            $table->foreignId('branch_id')->references('id')->on('branches');
            $table->decimal('price_per_price', 10,3)->nullable();
            $table->decimal('quantity', 10,3)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_rm_stocks');
    }
};
