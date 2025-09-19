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
        Schema::create('recipe_rm_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_cost_id')->references('id')->on('recipe_costs');
            $table->foreignId('raw_materials_cost_id')->references('id')->on('raw_material_costs');
            $table->decimal('price_per_unit', 10,3)->nullable();
            $table->string('unit')->nullable();
            $table->decimal('quantity', 10,3)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_rm_costs');
    }
};
