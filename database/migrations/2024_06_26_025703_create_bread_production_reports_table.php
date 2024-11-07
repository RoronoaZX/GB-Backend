<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bread_production_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->references('id')->on('branches');
            $table->foreignId('user_id')->references('id')->on('users');
            $table->foreignId('branch_recipe-id')->references('id')->on('branch_recipes');
            $table->foreignId('initial_bakerreports_id')->references('id')->on('initial_bakerreports');
            $table->foreignId('bread_id')->references('id')->on('products');
            $table->integer('bread_new_production')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bread_production_reports');
    }
};
