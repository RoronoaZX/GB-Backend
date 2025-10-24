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
        Schema::create('recipe_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('recipe_id')->constrained('branch_recipes')->onDelete('cascade');
            $table->string('recipe_category')->nullable();
            $table->foreignId('raw_material_id')->constrained('raw_materials')->onDelete('cascade');
            $table->foreignId('initial_bakerreport_id')->constrained('initial_bakerreports')->onDelete('cascade');
            $table->foreignId('branch_recipe_id')->constrained('branch_recipes')->onDelete('cascade');

            $table->decimal('quantity_used', 10,9)->nullable();
            $table->decimal('price_per_gram', 10,9)->nullable();
            $table->decimal('total_cost', 12, 9)->nullable();

            $table->foreignId('branch_rm_stock_id')->nullable()->constrained('branch_rm_stocks')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_costs');
    }
};
