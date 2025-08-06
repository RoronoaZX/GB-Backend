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
        Schema::create('incentives_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('initial_bakerreports_id')->references('id')->on('initial_bakerreports');
            $table->foreignId('user_employee_id')->references('id')->on('employees');
            $table->foreignId('branch_id')->references('id')->on('branches');
            $table->foreignId('branch_recipe_id')->references('id')->on('branch_recipes');
            $table->decimal('kilo')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incentives_reports');
    }
};
