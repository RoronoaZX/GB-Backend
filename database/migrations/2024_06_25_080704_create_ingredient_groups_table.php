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
        Schema::create('ingredient_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_recipe_id');
            $table->unsignedBigInteger('ingredient_id');
            $table->integer('quantity');
            $table->foreign('branch_recipe_id')->references('id')->on('branch_recipes')->onDelete('cascade');
            $table->foreign('ingredient_id')->references('id')->on('raw_materials')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_groups');
    }
};
