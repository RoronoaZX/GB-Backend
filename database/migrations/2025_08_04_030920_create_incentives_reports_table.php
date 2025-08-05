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
        Schema::create('incentives_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('initial_bakerreports_id')->references('id')->on('initial_bakerreports');
            $table->foreignId('user_employee_id')->references('id')->on('employees');
            $table->decimal('over_kilo')->nullable();
            $table->integer('total_employees')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incentives_reports');
    }
};
