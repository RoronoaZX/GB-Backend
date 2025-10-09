<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Type\Decimal;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('branch_rm_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_material_id')->references('id')->on('raw_materials');
            $table->foreignId('branch_id')->references('id')->on('branches');
            $table->foreignId('delivery_su_id')->references('id')->on('delivery_stocks_units');
            $table->decimal('price_per_gram', 10,3)->nullable();
            $table->decimal('quantity', 10,3)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_rm_stocks');
    }
};
