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
        Schema::create('employee_benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->references('id')->on('employees');
            $table->decimal('sss', 10,2)->nullable();
            $table->decimal('hdmf', 10,2)->nullable();
            $table->decimal('phic', 10,2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_benefits');
    }
};
