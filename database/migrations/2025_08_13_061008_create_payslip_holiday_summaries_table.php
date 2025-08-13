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
        Schema::create('payslip_holiday_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_id')->references('id')->on('payslips');
            $table->string('label')->nullable();
            $table->string('additional_pay')->nullable();
            $table->string('date')->nullable();
            $table->string('holiday_rate')->nullable();
            $table->string('holiday_type')->nullable();
            $table->string('type')->nullable();
            $table->string('worked_hours')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_holiday_summaries');
    }
};
