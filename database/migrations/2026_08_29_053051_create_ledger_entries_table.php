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
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('entity_id');
            $table->date('txn_date');
            $table->unsignedInteger('cost_center_id');
            $table->string('particulars', 500);
            $table->decimal('signed_amount', 14, 2);
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->string('source_table', 50);
            $table->unsignedBigInteger('source_id');
            $table->timestamps();

            $table->unique(['entity_id', 'source_table', 'source_id'], 'ledger_entries_entity_source_unique');
            $table->index(['entity_id', 'txn_date', 'id'], 'ledger_entries_entity_date_idx');
            $table->foreign('entity_id')->references('id')->on('entities');
            $table->foreign('cost_center_id')->references('id')->on('cost_centers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
