<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repurpose_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bread_out_id')->constrained('bread_outs')->onDelete('cascade');
            $table->enum('action_type', ['toasted', 'crumbs', 'transfer']);
            
            $table->nullableMorphs('outputable'); 
            
            $table->unsignedBigInteger('destination_branch_id')->nullable();
            $table->foreign('destination_branch_id')->references('id')->on('branches')->onDelete('cascade');
            
            $table->decimal('output_quantity', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repurpose_logs');
    }
};
