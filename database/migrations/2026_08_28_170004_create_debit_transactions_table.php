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
        Schema::create('debit_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('txn_date')->index('idx_debit_date');
            $table->unsignedInteger('cost_center_id');
            $table->enum('category', ['EXPENSE', 'RAW_MATERIALS']);
            $table->string('account', 120);
            $table->unsignedInteger('paid_through_entity_id');
            $table->string('description', 255)->nullable();
            $table->decimal('amount', 14, 2);
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['cost_center_id', 'txn_date'], 'idx_debit_cc_date');
            $table->index('paid_through_entity_id', 'idx_debit_paid_through');
            $table->foreign('cost_center_id')->references('id')->on('cost_centers');
            $table->foreign('paid_through_entity_id')->references('id')->on('entities');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        DB::statement('ALTER TABLE debit_transactions ADD CONSTRAINT chk_debit_amount CHECK (amount > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debit_transactions');
    }
};
