<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('purchases')
            ->whereNull('txn_date')
            ->update(['txn_date' => DB::raw('COALESCE(as_of_date, CURRENT_DATE)')]);

        DB::table('sales')
            ->whereNull('txn_date')
            ->update(['txn_date' => DB::raw('COALESCE(as_of_date, CURRENT_DATE)')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No action needed on rollback
    }
};
