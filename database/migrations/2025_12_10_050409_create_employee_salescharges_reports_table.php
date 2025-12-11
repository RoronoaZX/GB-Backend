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
        Schema::create('employee_salescharges_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_report_id')->references('id')->on('sales_reports');
            $table->foreignId('employee_id')->references('id')->on('employees');
            $table->decimal('charge_amount', 10,6)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_salescharges_reports');
    }
};
