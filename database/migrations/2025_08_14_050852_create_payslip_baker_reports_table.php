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
        Schema::create('payslip_baker_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_incentive_id')->references('id')->on('payslip_incentives');
            $table->foreignId('branch_id')->references('id')->on('branches');
            $table->foreignId('user_id')->references('id')->on('users');
            $table->foreignId('initial_bakerreport_id')->references('id')->on('initial_bakerreports');
            $table->foreignId('branch_recipe_id')->references('id')->on('branch_recipes');
            $table->string('recipe_category')->nullable();
            $table->string('status')->nullable();
            $table->decimal('kilo', 10,2)->nullable();
            $table->integer('short')->nullable();
            $table->integer('over')->nullable();
            $table->decimal('target', 10,2)->nullable();
            $table->integer('actual_target')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_baker_reports');
    }
};
