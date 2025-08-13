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
        Schema::create('payslip_deduction_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_deduction_id')->references('id')->on('payslip_deductions');
            $table->foreignId('product_id')->references('id')->on('products');
            $table->integer('pieces')->nullable();
            $table->decimal('price', 10,2)->nullable();
            $table->string('product_name')->nullable();
            $table->decimal('total_price', 10,2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_deduction_credits');
    }
};
