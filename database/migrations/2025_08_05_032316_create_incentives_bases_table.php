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
        Schema::create('incentives_bases', function (Blueprint $table) {
            $table->id();
            $table->integer('number_of_employees')->nullable();
            $table->integer('target')->nullable();
            $table->integer('baker_multiplier')->nullable();
            $table->integer('lamesador_multiplier')->nullable();
            $table->integer('hornero_incentives')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incentives_bases');
    }
};
