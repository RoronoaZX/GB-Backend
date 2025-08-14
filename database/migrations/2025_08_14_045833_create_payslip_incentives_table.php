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
        Schema::create('payslip_incentives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_earning_id')->references('id')->on('payslip_earnings');
            $table->foreignId('branch_id')->references('id')->on('branches');
            $table->foreignId('employee_id')->references('id')->on('employees');
            $table->string('designation')->nullable();
            $table->decimal('baker_kilo', 10,2)->nullable();
            $table->decimal('excess_kilo', 10,2)->nullable();
            $table->decimal('incentive_value', 10,2)->nullable();
            $table->decimal('multiplier_used', 10,2)->nullable();
            $table->decimal('number_of_employees', 10,2)->nullable();
            $table->decimal('shift_status', 10,2)->nullable();
            $table->decimal('target', 10,2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_incentives');
    }
};
