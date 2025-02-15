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
        Schema::create('request_premixes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_premix_id')->references('id')->on('branch_premixes');
            $table->foreignId('warehouse_id')->references('id')->on('warehouse');
            $table->foreignId('employee_id')->references('id')->on('employees');
            $table->string('name', 255)->nullable();
            $table->string('category', 255)->nullable();
            $table->integer('quantity')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_premixes');
    }
};
