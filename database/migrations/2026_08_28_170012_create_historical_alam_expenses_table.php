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
        Schema::create('historical_alam_expenses', function (Blueprint $table) {
            $table->increments('id');
            $table->date('txn_date');
            $table->string('account', 150);
            $table->string('description', 255)->nullable();
            $table->decimal('amount', 14, 2);
            $table->unsignedInteger('created_by');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('created_by')->references('id')->on('users');
        });

        DB::statement('ALTER TABLE historical_alam_expenses ADD CONSTRAINT chk_hist_alam_amount CHECK (amount > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historical_alam_expenses');
    }
};
