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
        Schema::create('branch_products', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('products');
            $table->bigInteger('branches_id')->unsigned();
            $table->foreign('branches_id')->references('id')->on('branches');
            $table->string('category')->nullable();
            $table->decimal('price', 10,2)->nullable();
            $table->integer('beginnings')->nullable();
            $table->integer('new_production')->default(0);
            $table->integer('total_quantity')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_products');
    }
};
