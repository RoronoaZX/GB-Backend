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
        Schema::create('initial_bakerreports', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('branch_id')->unsigned();
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');
            $table->bigInteger('branch_recipe_id')->unsigned();
            $table->foreign('branch_recipe_id')->references('id')->on('branch_recipes');
            $table->string('recipe_category');
            $table->string('status');
            $table->integer('kilo');
            $table->integer('short');
            $table->integer('over');
            $table->integer('target');
            $table->integer('actual_target');
            $table->string('remark', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('initial_bakerreports');
    }
};
