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
        Schema::create('raw_materials_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_material_id')->references('id')->on('raw_materials');
            $table->bigInteger('from')->unsigned();
            $table->string('from_designation')->nullable();
            $table->bigInteger('to')->unsigned();
            $table->string('to_designation')->nullable();
            $table->decimal('quantity', 10,3)->nullable();
            $table->decimal('price_per_unit', 10,3)->nullable();
            $table->string('remarks')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raw_materials_deliveries');
    }
};
