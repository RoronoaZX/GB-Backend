<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_on_leaves', function (Blueprint $table) {
            $table->text('remarks')->nullable()->after('status')->comment('Supervisor remarks or reason for rejection');
            $table->string('attachment_path')->nullable()->after('remarks')->comment('Path to uploaded document');
        });
    }

    public function down(): void
    {
        Schema::table('employee_on_leaves', function (Blueprint $table) {
            $table->dropColumn(['remarks', 'attachment_path']);
        });
    }
};
