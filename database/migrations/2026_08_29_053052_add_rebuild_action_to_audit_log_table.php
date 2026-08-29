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
        DB::statement("ALTER TABLE audit_log MODIFY action ENUM('CREATE', 'UPDATE', 'DELETE', 'REBUILD') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE audit_log MODIFY action ENUM('CREATE', 'UPDATE', 'DELETE') NOT NULL");
    }
};
