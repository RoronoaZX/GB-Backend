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
        Schema::create('softdrinks_stocks_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branches_id')->references('id')->on('branches');
            $table->foreignId('employee_id')->references('id')->on('employees');
            $table->string('status', 255)->nullable();
            $table->string('remark', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('softdrinks_stocks_reports');
    }
};
