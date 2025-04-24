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
        Schema::create('history_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('report_id');
            $table->bigInteger('user_id');
            $table->string('type_of_report')->nullable();
            $table->string('name')->nullable();
            $table->string('updated_field')->nullable();
            $table->string('designation')->nullable();
            $table->string('dsignation_type')->nullable();
            $table->string('action')->nullable();
            $table->string('original_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('history_logs');
    }
};
