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
        Schema::create('branch_raw_materials_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->references('id')->on('branches');
            // $table->foreignId('recipe_id')->references('id')->on('recipes');
            // $table->foreignId('baker_report_id')->references('id')->on('baker_reports');
            $table->foreignId('ingredients_id')->references('id')->on('products');
            $table->integer('total_quantity')->nullable();
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
        Schema::dropIfExists('branch_raw_materials_reports');
    }
};
