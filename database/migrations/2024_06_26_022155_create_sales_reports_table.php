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
        Schema::create('sales_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->references('id')->on('branches');
            $table->foreignId('user_id')->references('id')->on('users');
            $table->decimal('products_total_sales', 10,2)->nullable();
            $table->decimal('expenses_total', 10,2)->nullable();
            $table->decimal('denomination_total', 10,2)->nullable();
            $table->decimal('charges_amount', 10,2)->nullable();
            $table->decimal('over_total', 10,2)->nullable();
            $table->decimal('credit_total', 10,2)->nullable();
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
        Schema::dropIfExists('sales_reports');
    }
};
