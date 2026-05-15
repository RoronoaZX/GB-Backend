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
        Schema::table('history_logs', function (Blueprint $table) {
            // Fix typo if it exists
            if (Schema::hasColumn('history_logs', 'dsignation_type')) {
                $table->renameColumn('dsignation_type', 'designation_type');
            }
            
            // Add missing column
            if (!Schema::hasColumn('history_logs', 'updated_data')) {
                $table->string('updated_data')->nullable()->after('original_data');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('history_logs', function (Blueprint $table) {
            if (Schema::hasColumn('history_logs', 'designation_type')) {
                $table->renameColumn('designation_type', 'dsignation_type');
            }
            if (Schema::hasColumn('history_logs', 'updated_data')) {
                $table->dropColumn('updated_data');
            }
        });
    }
};
