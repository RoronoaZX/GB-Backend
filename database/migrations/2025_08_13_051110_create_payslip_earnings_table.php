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
        Schema::create('payslip_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_id')->references('id')->on('payslips');
            $table->decimal('allowances_pay', 10,2)->nullable();
            $table->decimal('holidays_pay', 10,2)->nullable();
            $table->decimal('incentives_pay', 10,2)->nullable();
            $table->decimal('night_diff_pay', 10,2)->nullable();
            $table->decimal('overtime_pay', 10,2)->nullable();
            $table->decimal('undertime_pay', 10, 2)->nullable();
            $table->decimal('working_hours_pay', 10,2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_earnings');
    }
};
