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
        Schema::create('baker_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->references('id')->on('branches');
            $table->foreignId('user_id')->references('id')->on('users');
            $table->foreignId('recipe_id')->references('id')->on('recipes');
            $table->string('recipe_category')->nullable();
            $table->string('status')->nullable();
            $table->integer('kilo')->nullable();
            $table->integer('short')->nullable();
            $table->integer('over')->nullable();
            $table->integer('actual_target')->nullable();
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
        Schema::dropIfExists('baker_reports');
    }
};
