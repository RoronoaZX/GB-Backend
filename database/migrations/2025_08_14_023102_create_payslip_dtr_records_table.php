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
        Schema::create('payslip_dtr_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_dtr_id')->references('id')->on('payslip_dtrs');
            $table->foreignId('dtr_id')->references('id')->on('daily_time_records');
            $table->string('device_uuid_in')->nullable();
            $table->string('device_uuid_out')->nullable();
            $table->foreignId('employee_id')->references('id')->on('employees');
            $table->string('employee_allowance')->nullable();
            $table->string('time_in')->nullable();
            $table->string('time_out')->nullable();
            $table->string('lunch_break_start')->nullable();
            $table->string('lunch_break_end')->nullable();
            $table->string('break_start')->nullable();
            $table->string('break_end')->nullable();
            $table->string('overtime_start')->nullable();
            $table->string('overtime_end')->nullable();
            $table->string('overtime_reason')->nullable();
            $table->string('ot_status')->nullable();
            $table->bigInteger('approved_by')->nullable();
            $table->string('declined_reason')->nullable();
            $table->string('half_day_reason')->nullable();
            $table->string('shift_status')->nullable();
            $table->string('schedule_in')->nullable();
            $table->string('schedule_out')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_dtr_records');
    }
};
