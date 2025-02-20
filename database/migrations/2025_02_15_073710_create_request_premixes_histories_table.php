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
        Schema::create('request_premixes_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_premixes_id')->references('id')->on('request_premixes');
            $table->foreignId('branch_premix_id')->references('id')->on('branch_premix');
            $table->foreignId('warehouse_id')->references('id')->on('warehouses');
            $table->foreignId('recipe_id')->references('id')->on('recipe_id');
            $table->foreignId('change_by')->references('id')->on('employees');
            $table->decimal('quantity', 10,2)->nullable();
            $table->string('status', 255)->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_premixes_histories');
    }
};
