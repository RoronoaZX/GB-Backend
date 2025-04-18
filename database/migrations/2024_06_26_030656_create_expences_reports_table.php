<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('expences_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_report_id')->references('id')->on('sales_reports');
            $table->foreignId('branch_id')->references('id')->on('branches');
            $table->foreignId('user_id')->references('id')->on('users');
            // $table->foreignId('expense_user_id')->references('id')->on('users');
            $table->string('name')->nullable();
            $table->decimal('amount',10,2)->nullable();
            $table->string('description')->nullable();
            $table->string('category')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('expences_reports');
    }
};
