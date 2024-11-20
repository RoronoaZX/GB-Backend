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
        Schema::create('cake_sales_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_report_id')->references('id')->on('sales_reports');
            $table->foreignId('cake_report_id')->references('id')->on('cake_reports');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cake_sales_reports');
    }
};
