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
        Schema::table('bread_outs', function (Blueprint $table) {
            $table->foreignId('bread_sales_report_id')->nullable()->after('product_id')->constrained('bread_sales_reports')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bread_outs', function (Blueprint $table) {
            $table->dropForeign(['bread_sales_report_id']);
            $table->dropColumn('bread_sales_report_id');
        });
    }
};
