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
            $table->foreignId('initial_bakerreport_id')->references('id')->on('initial_bakerreports');
            $table->foreign('branch_recipe_id')->references('id')->on('branch_recipes');
            $table->decimal('total_cost', 10,3)->nullable();
            $table->string('status')->nullable();
            $table->decimal('kilo', 10,3)->nullable();
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
