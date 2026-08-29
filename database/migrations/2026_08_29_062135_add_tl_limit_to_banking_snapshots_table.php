<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banking_snapshots', function (Blueprint $table) {
            $table->decimal('tl_limit', 14, 2)->default('13500000.00')->after('term_loan');
        });
    }

    public function down(): void
    {
        Schema::table('banking_snapshots', function (Blueprint $table) {
            $table->dropColumn('tl_limit');
        });
    }
};
