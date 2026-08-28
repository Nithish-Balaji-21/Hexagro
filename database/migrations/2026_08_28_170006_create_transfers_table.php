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
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->date('txn_date')->index('idx_transfer_date');
            $table->unsignedInteger('cost_center_id');
            $table->unsignedInteger('from_entity_id');
            $table->unsignedInteger('to_entity_id');
            $table->string('note', 255)->nullable();
            $table->decimal('amount', 14, 2);
            $table->unsignedInteger('created_by');
            $table->timestamp('created_at')->useCurrent();

            $table->index('from_entity_id', 'idx_transfer_from');
            $table->index('to_entity_id', 'idx_transfer_to');
            $table->foreign('cost_center_id')->references('id')->on('cost_centers');
            $table->foreign('from_entity_id')->references('id')->on('entities');
            $table->foreign('to_entity_id')->references('id')->on('entities');
            $table->foreign('created_by')->references('id')->on('users');
        });

        DB::statement('ALTER TABLE transfers ADD CONSTRAINT chk_transfer_amount CHECK (amount > 0)');
        DB::statement('ALTER TABLE transfers ADD CONSTRAINT chk_transfer_not_self CHECK (from_entity_id <> to_entity_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
