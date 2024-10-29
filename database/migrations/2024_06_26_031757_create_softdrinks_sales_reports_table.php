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
        Schema::create('softdrinks_sales_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->references('id')->on('branches');
            $table->foreignId('user_id')->references('id')->on('users');
            $table->foreignId('product_id')->references('id')->on('products');
            $table->foreignId('sales_report_id')->references('id')->on('sales_reports');
            $table->integer('beginnings');
            $table->integer('remaining_stocks');
            $table->integer('price');
            $table->integer('softdrinks_sold');
            $table->integer('sales');
            $table->integer('branch_softdrinks_added_stocks');
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
        Schema::dropIfExists('softdrinks_sales_reports');
    }
};
