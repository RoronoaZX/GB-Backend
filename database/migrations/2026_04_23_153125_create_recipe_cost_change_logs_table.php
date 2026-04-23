<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_cost_change_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recipe_cost_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->bigInteger('user_id');
            $table->string('changed_field');
            $table->decimal('old_value', 15, 4)->nullable();
            $table->decimal('new_value', 15, 4)->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_cost_change_logs');
    }
};
