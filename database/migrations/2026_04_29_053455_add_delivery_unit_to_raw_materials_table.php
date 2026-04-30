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
        Schema::table('raw_materials', function (Blueprint $table) {
            $table->string('delivery_unit')->nullable()->after('unit'); // sack, can, box, etc.
            $table->decimal('unit_weight', 10, 2)->nullable()->after('delivery_unit'); // grams per unit
            $table->integer('unit_pcs')->nullable()->after('unit_weight'); // pcs per unit (for boxes)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('raw_materials', function (Blueprint $table) {
            $table->dropColumn(['delivery_unit', 'unit_weight', 'unit_pcs']);
        });
    }
};
