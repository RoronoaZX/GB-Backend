<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spoilage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bread_out_id')->constrained('bread_outs')->onDelete('cascade');
            $table->integer('quantity');
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spoilage_logs');
    }
};
