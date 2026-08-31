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
        Schema::dropIfExists('historical_alam_expenses');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('historical_alam_expenses', function (Blueprint $table): void {
            $table->id();
            $table->date('txn_date');
            $table->string('account', 150);
            $table->string('description', 255)->nullable();
            $table->decimal('amount', 14, 2);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent();

            $table->index('txn_date');
        });
    }
};
