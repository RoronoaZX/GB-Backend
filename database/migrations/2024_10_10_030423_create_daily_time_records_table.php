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
        Schema::create('daily_time_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->references('id')->on('employees');
            $table->dateTime('time_in')->nullable();
            $table->dateTime('time_out')->nullable();
            $table->dateTime('lunch_break_start')->nullable();
            $table->dateTime('lunch_break_end')->nullable();
            $table->dateTime('break_start')->nullable();
            $table->dateTime('break_end')->nullable();
            $table->dateTime('overtime_start')->nullable();
            $table->dateTime('overtime_end')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_time_records');
    }
};
