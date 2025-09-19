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
        Schema::create('raw_material_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_cost_id')->references('id')->on('recipe_costs');
            $table->bigInteger('reference_id')->unsigned();
            $table->string('designation')->nullable();
            $table->decimal('quantity', 10,3)->nullable();
            $table->decimal('price_per_unit', 10,3)->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raw_material_costs');
    }
};
