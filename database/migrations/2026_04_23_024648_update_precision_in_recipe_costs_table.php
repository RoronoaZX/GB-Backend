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
        Schema::table('recipe_costs', function (Blueprint $table) {
            $table->decimal('quantity_used', 15, 2)->change();
            $table->decimal('price_per_gram', 15, 4)->change();
            $table->decimal('total_cost', 15, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipe_costs', function (Blueprint $table) {
            $table->decimal('quantity_used', 10, 9)->change();
            $table->decimal('price_per_gram', 10, 9)->change();
            $table->decimal('total_cost', 12, 9)->change();
        });
    }
};
