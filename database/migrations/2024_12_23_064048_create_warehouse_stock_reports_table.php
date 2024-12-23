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
        Schema::create('warehouse_stock_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->references('id')->on('warehouses');
            $table->foreignId('employee_id')->references('id')->on('employees');
            $table->string('suppliers_company_name', 255)->nullable();
            $table->string('suppliers_name', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_stock_reports');
    }
};
