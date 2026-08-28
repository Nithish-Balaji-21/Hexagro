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
        Schema::create('banking_snapshots', function (Blueprint $table) {
            $table->increments('id');
            $table->date('as_of_date');
            $table->decimal('cc_limit', 14, 2);
            $table->decimal('cc_utilised', 14, 2);
            $table->decimal('current_balance', 14, 2);
            $table->decimal('term_loan', 14, 2);
            $table->decimal('alam_utilised', 14, 2);
            $table->unsignedInteger('created_by');
            $table->timestamp('created_at')->useCurrent();

            $table->index('as_of_date', 'idx_banking_as_of');
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banking_snapshots');
    }
};
