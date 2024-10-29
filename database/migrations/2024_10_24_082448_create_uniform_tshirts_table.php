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
        Schema::create('uniform_tshirts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uniform_id')->references('id')->on('uniforms');
            $table->string('size', 225)->nullable();
            $table->integer('pcs');
            $table->decimal('price', 10,2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uniform_tshirts');
    }
};
