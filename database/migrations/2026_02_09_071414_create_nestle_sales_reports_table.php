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
        Schema::create('nestle_sales_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->references('id')->on('branches');
            $table->foreignId('user_id')->references('id')->on('users');
            $table->foreignId('product_id')->references('id')->on('products');
            $table->foreignId('sales_report_id')->references('id')->on('sales_reports');
            $table->integer('beginnings');
            $table->integer('remaining');
            $table->integer('price');
            $table->integer('sold');
            $table->integer('out');
            $table->integer('sales');
            $table->integer('added_stocks');
            $table->string('status')->nullable();
            $table->foreignId('handled_by')->references('id')->on('employee');
            $table->string('reason')->nullable();
            $table->dateTime('handled_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nestle_sales_reports');
    }
};
