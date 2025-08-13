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
        Schema::create('payslip_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_id')->references('id')->on('payslips');
            $table->decimal('benefits_total', 10, 2)->nullable();
            $table->decimal('cash_advance_total', 10, 2)->nullable();
            $table->decimal('credit_total', 10, 2)->nullable();
            $table->decimal('employee_charge_total', 10, 2)->nullable();
            $table->decimal('total_deduction', 10, 2)->nullable();
            $table->decimal('uniform_total', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_deductions');
    }
};
