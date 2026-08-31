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
        Schema::table('purchases', function (Blueprint $table): void {
            $table->date('as_of_date')->nullable()->after('notes');
            $table->index(['cost_center_id', 'as_of_date'], 'idx_purchase_cc_as_of');
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->date('as_of_date')->nullable()->after('notes');
            $table->index(['cost_center_id', 'as_of_date'], 'idx_sale_cc_as_of');
        });

        $today = now()->toDateString();

        DB::table('purchases')->whereNull('as_of_date')->update(['as_of_date' => $today]);
        DB::table('sales')->whereNull('as_of_date')->update(['as_of_date' => $today]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table): void {
            $table->dropIndex('idx_purchase_cc_as_of');
            $table->dropColumn('as_of_date');
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->dropIndex('idx_sale_cc_as_of');
            $table->dropColumn('as_of_date');
        });
    }
};
