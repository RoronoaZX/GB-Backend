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
        Schema::create('initial_ingredients_bakerreports', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('initial_bakerreports_id')->unsigned();
            $table->foreign('initial_bakerreports_id')->references('id')->on('initial_bakerreports');
            $table->bigInteger('ingredients_id')->unsigned();
            $table->foreign('ingredients_id')->references('id')->on('products');
            $table->integer('quantity');
            $table->string('unit');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('initial_ingredients_bakerreports');
    }
};
