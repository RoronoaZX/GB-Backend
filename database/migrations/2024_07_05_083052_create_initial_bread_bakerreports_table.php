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
        Schema::create('initial_bread_bakerreports', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('initial_bakerreports_id')->unsigned();
            $table->foreign('initial_bakerreports_id')->references('id')->on('initial_bakerreports');
            $table->bigInteger('bread_id')->unsigned();
            $table->foreign('bread_id')->references('id')->on('products');
            $table->integer('bread_production');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('initial_bread_bakerreports');
    }
};
