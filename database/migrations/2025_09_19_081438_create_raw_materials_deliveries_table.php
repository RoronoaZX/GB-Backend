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
            $table->bigInteger('from_id')->unsigned();
            $table->string('from_designation')->nullable();
            $table->string('from_name')->nullable();
            $table->bigInteger('to_id')->unsigned();
            $table->string('to_designation')->nullable();
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
