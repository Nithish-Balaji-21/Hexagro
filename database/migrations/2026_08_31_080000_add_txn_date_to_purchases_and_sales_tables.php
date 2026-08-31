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
        Schema::table('purchases', function (Blueprint $table) {
            $table->date('txn_date')->nullable()->after('cost_center_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->date('txn_date')->nullable()->after('cost_center_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('txn_date');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('txn_date');
        });
    }
};
