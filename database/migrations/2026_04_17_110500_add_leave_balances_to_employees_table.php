<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->integer('vl_balance')->default(15)->after('status')->comment('Vacation Leave Balance');
            $table->integer('sl_balance')->default(15)->after('vl_balance')->comment('Sick Leave Balance');
            $table->integer('el_balance')->default(5)->after('sl_balance')->comment('Emergency Leave Balance');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['vl_balance', 'sl_balance', 'el_balance']);
        });
    }
};
