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
        $duplicateDates = DB::table('banking_snapshots')
            ->select('as_of_date')
            ->groupBy('as_of_date')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('as_of_date');

        foreach ($duplicateDates as $date) {
            $keepId = DB::table('banking_snapshots')
                ->where('as_of_date', $date)
                ->max('id');

            DB::table('banking_snapshots')
                ->where('as_of_date', $date)
                ->where('id', '!=', $keepId)
                ->delete();
        }

        Schema::table('banking_snapshots', function (Blueprint $table): void {
            $table->dropIndex('idx_banking_as_of');
            $table->unique('as_of_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banking_snapshots', function (Blueprint $table): void {
            $table->dropUnique(['as_of_date']);
            $table->index('as_of_date', 'idx_banking_as_of');
        });
    }
};
