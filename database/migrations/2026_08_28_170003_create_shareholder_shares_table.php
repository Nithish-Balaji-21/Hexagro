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
        Schema::create('shareholder_shares', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cost_center_id');
            $table->unsignedInteger('entity_id');
            $table->decimal('share_pct', 7, 4);
            $table->date('effective_from');

            $table->unique(['cost_center_id', 'entity_id', 'effective_from'], 'uq_share_unit_entity');
            $table->foreign('cost_center_id')->references('id')->on('cost_centers');
            $table->foreign('entity_id')->references('id')->on('entities');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shareholder_shares');
    }
};
