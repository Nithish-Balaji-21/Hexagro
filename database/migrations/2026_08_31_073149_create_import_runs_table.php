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
        Schema::create('import_runs', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('kind', 20);
            $table->string('filename', 255);
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('row_count')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['kind', 'created_at'], 'idx_import_runs_kind_created');
            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::table('debit_transactions', function (Blueprint $table): void {
            $table->unsignedInteger('import_run_id')->nullable()->after('created_by');
            $table->foreign('import_run_id')->references('id')->on('import_runs')->nullOnDelete();
        });

        Schema::table('credit_transactions', function (Blueprint $table): void {
            $table->unsignedInteger('import_run_id')->nullable()->after('created_by');
            $table->foreign('import_run_id')->references('id')->on('import_runs')->nullOnDelete();
        });

        Schema::table('transfers', function (Blueprint $table): void {
            $table->unsignedInteger('import_run_id')->nullable()->after('created_by');
            $table->foreign('import_run_id')->references('id')->on('import_runs')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table): void {
            $table->dropForeign(['import_run_id']);
            $table->dropColumn('import_run_id');
        });

        Schema::table('credit_transactions', function (Blueprint $table): void {
            $table->dropForeign(['import_run_id']);
            $table->dropColumn('import_run_id');
        });

        Schema::table('debit_transactions', function (Blueprint $table): void {
            $table->dropForeign(['import_run_id']);
            $table->dropColumn('import_run_id');
        });

        Schema::dropIfExists('import_runs');
    }
};
