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
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('txn_date')->index('idx_credit_date');
            $table->unsignedInteger('cost_center_id');
            $table->enum('credit_type', ['SALES', 'VENDOR_RETURN', 'EMPLOYEE_RETURN', 'OTHER_CREDIT']);
            $table->unsignedInteger('received_to_entity_id');
            $table->string('description', 255)->nullable();
            $table->decimal('amount', 14, 2);
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['cost_center_id', 'txn_date'], 'idx_credit_cc_date');
            $table->index('received_to_entity_id', 'idx_credit_received_to');
            $table->foreign('cost_center_id')->references('id')->on('cost_centers');
            $table->foreign('received_to_entity_id')->references('id')->on('entities');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        DB::statement('ALTER TABLE credit_transactions ADD CONSTRAINT chk_credit_amount CHECK (amount > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
    }
};
