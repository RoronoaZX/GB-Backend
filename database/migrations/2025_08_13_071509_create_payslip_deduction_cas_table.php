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
        Schema::create('payslip_deduction_cas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_deduction_id')->references('id')->on('payslip_deductions');
            $table->foreignId('cash_advance_id')->references('id')->on('cash_advances');
            $table->foreignId('employee_id')->references('id')->on('employees');
            $table->string('date')->nullable();
            $table->decimal('amount', 10,2)->nullable();
            $table->integer('number_of_payment')->nullable();
            $table->decimal('payment_per_payroll', 10,2)->nullable();
            $table->decimal('remaining_payments', 10,2)->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_deduction_cas');
    }
};
