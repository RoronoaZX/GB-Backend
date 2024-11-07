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
        Schema::create('bread_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_recipe_id');
            $table->unsignedBigInteger('bread_id');
            $table->foreign('branch_recipe_id')->references('id')->on('branch_recipes')->onDelete('cascade');
            $table->foreign('bread_id')->references('id')->on('products')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bread_groups');
    }
};
