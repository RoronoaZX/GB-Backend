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
        Schema::create('payslip_dtrs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_id')->references('id')->on('payslips');
            $table->decimal('from', 10,2)->nullable();
            $table->decimal('to', 10,2)->nullable();
            $table->decimal('release_date', 10,2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_dtrs');
    }
};
