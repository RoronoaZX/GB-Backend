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
        Schema::create('bread_addeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branches_id')->references('id')->on('branches');
            $table->foreignId('employee_id')->references('id')->on('employees');
            $table->foreignId('product_id')->references('id')->on('products');
            $table->foreignId('from_branch_id')->references('id')->on('branches');
            $table->foreignId('to_branch_id')->references('id')->on('branches');
            $table->decimal('bread_added', 10,2)->nullable();
            $table->integer('bread_added')->nullable();
            $table->string('status', 25)->nullable();
            $table->string('remark', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bread_addeds');
    }
};
