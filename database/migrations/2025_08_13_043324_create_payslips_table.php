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
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->references('id')->on('employees');
            $table->string('from')->nullable();
            $table->string('to')->nullable();
            $table->string('payroll_release_date')->nullable();
            $table->decimal('rate_per_day', 10,2)->nullable();
            $table->integer('total_days')->nullable();
            $table->decimal('uniform_balance', 10,2)->nullable();
            $table->decimal('credit_balance', 10,2)->nullable();
            $table->decimal('cash_advance_balance', 10,2)->nullable();
            $table->decimal('total_earnings', 10,2)->nullable();
            $table->decimal('total_deductions', 10,2)->nullable();
            $table->decimal('net_income', 10,2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
