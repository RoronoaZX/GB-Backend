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
        Schema::create('denominations', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('sales_report_id')->unsigned();
            $table->foreign('sales_report_id')->references('id')->on('sales_reports');
            $table->integer('oneThousandBills')->nullable();
            $table->integer('fiveHundredBills')->nullable();
            $table->integer('twoHundredBills')->nullable();
            $table->integer('oneHundredBills')->nullable();
            $table->integer('fiftyBills')->nullable();
            $table->integer('twentyBills')->nullable();
            $table->integer('twentyCoins')->nullable();
            $table->integer('tenCoins')->nullable();
            $table->integer('fiveCoins')->nullable();
            $table->integer('oneCoins')->nullable();
            $table->integer('twentyFiveCents')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('denominations');
    }
};
