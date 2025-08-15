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
        Schema::create('payslip_deduction_shirts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_deduction_uniform_id')->references('id')->on('payslip_deduction_uniforms');
            $table->foreignId('uniform_tshirt_id')->references('id')->on('uniform_tshirts');
            $table->string('date')->nullable();
            $table->integer('pcs')->nullable();
            $table->decimal('price', 10,2)->nullable();
            $table->string('size')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_deduction_shirts');
    }
};
