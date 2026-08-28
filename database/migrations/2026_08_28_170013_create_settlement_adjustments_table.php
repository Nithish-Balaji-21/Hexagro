<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settlement_adjustments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('from_entity_id');
            $table->unsignedInteger('to_entity_id');
            $table->decimal('amount', 14, 2);
            $table->string('note', 255)->nullable();
            $table->unsignedInteger('created_by');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('from_entity_id')->references('id')->on('entities');
            $table->foreign('to_entity_id')->references('id')->on('entities');
            $table->foreign('created_by')->references('id')->on('users');
        });

        DB::statement('ALTER TABLE settlement_adjustments ADD CONSTRAINT chk_adjustment_amount CHECK (amount > 0)');
        DB::statement('ALTER TABLE settlement_adjustments ADD CONSTRAINT chk_adjustment_not_self CHECK (from_entity_id <> to_entity_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlement_adjustments');
    }
};
