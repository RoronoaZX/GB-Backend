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
        Schema::create('softdrinks_added_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('softdrinks_stocks_report_id')->references('id')->on('softdrinks_stocks_reports');
            $table->foreignId('products_id')->references('id')->on('products');
            $table->decimal('price', 10,2)->nullable();
            $table->integer('added_stocks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('softdrinks_added_stocks');
    }
};
