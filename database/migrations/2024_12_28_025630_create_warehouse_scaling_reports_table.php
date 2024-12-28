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
        Schema::create('warehouse_scaling_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->references('id')->on('warehouses');
            $table->foreignId('employee_id')->references('id')->on('employees');
            $table->foreignId('recipe_id')->references('id')->on('recipes');
            $table->foreignId('branch_id')->references('id')->on('branches');
            $table->string('status', 255)->nullable();
            $table->string('remarks', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_scaling_reports');
    }
};
