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
        Schema::create('cake_ingredient_reports', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('cake_reports_id')->unsigned();
            $table->foreign('cake_reports_id')->references('id')->on('cake_reports');
            $table->bigInteger('branch_raw_materials_reports_id')->unsigned();
            $table->foreign('branch_raw_materials_reports_id')->references('id')->on('branch_raw_materials_reports');
            $table->integer('quantity');
            $table->string('unit', 255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cake_ingredient_reports');
    }
};
