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
        Schema::create('payslip_deduction_uniforms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_deduction_id')->references('id')->on('payslip_deductions');
            $table->foreignId('uniform_id')->references('id')->on('uninforms');
            $table->foreignId('employee_id')->references('id')->on('employees');
            $table->integer('number_of_payments')->nullable();
            $table->decimal('payments_per_payroll', 10,2)->nullable();
            $table->decimal('remaining_payments', 10,2)->nullable();
            $table->decimal('total_amount', 10,2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_deduction_uniforms');
    }
};
