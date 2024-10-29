<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('warehouse_stocks_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->references('id')->on('warehouses');
            $table->foreignId('raw_materials_id')->references('id')->on('raw_materials');
            $table->integer('available_stocks');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('warehouse_stocks_reports');
    }
};
