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
        Schema::table('supplier_ingredients', function (Blueprint $table) {
            $table->decimal('price_per_gram', 15, 4)->change();
            $table->decimal('price_per_unit', 15, 4)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_ingredients', function (Blueprint $table) {
            $table->decimal('price_per_gram', 10, 9)->change();
            $table->decimal('price_per_unit', 10, 9)->change();
        });
    }
};
