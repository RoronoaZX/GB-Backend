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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employment_type_id')->references('id')->on('employment_types');
            $table->string('firstname');
            $table->string('middlename');
            $table->string('lastname');
            $table->date('birthdate')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('sex')->nullable();
            $table->string('position')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
