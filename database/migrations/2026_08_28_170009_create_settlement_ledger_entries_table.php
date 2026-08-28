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
        Schema::create('settlement_ledger_entries', function (Blueprint $table) {
            $table->increments('id');
            $table->date('txn_date');
            $table->string('unit_scope', 60);
            $table->unsignedInteger('from_entity_id');
            $table->unsignedInteger('to_entity_id');
            $table->decimal('amount', 14, 2);
            $table->string('note', 255)->nullable();
            $table->unsignedInteger('created_by');
            $table->timestamp('created_at')->useCurrent();

            $table->index('unit_scope', 'idx_settle_scope');
            $table->foreign('from_entity_id')->references('id')->on('entities');
            $table->foreign('to_entity_id')->references('id')->on('entities');
            $table->foreign('created_by')->references('id')->on('users');
        });

        DB::statement('ALTER TABLE settlement_ledger_entries ADD CONSTRAINT chk_settle_amount CHECK (amount > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlement_ledger_entries');
    }
};
