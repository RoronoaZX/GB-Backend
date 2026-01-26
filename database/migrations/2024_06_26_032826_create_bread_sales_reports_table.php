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
        Schema::create('bread_sales_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->references('id')->on('branches');
            $table->foreignId('user_id')->references('id')->on('users');
            $table->foreignId('product_id')->references('id')->on('products');
            $table->foreignId('sales_report_id')->references('id')->on('sales_reports');
            $table->integer('beginnings');
            $table->integer('new_production');
            $table->integer('remaining');
            $table->integer('price');
            $table->integer('bread_sold');
            $table->integer('bread_out');
            $table->integer('total');
            $table->integer('bread_over');
            $table->integer('sales');
            $table->string('status')->nullable();
            $table->foreignId('handled_by')->references('id')->on('employee');
            $table->string('reason')->nullable();
            $table->dateTime('handled_at')->nullable();
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
        Schema::dropIfExists('bread_sales_reports');
    }
};
