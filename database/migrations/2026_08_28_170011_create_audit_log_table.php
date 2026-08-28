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
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->string('table_name', 50);
            $table->unsignedBigInteger('record_id');
            $table->enum('action', ['CREATE', 'UPDATE', 'DELETE']);
            $table->unsignedInteger('changed_by');
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->timestamp('changed_at')->useCurrent();

            $table->index(['table_name', 'record_id'], 'idx_audit_table_record');
            $table->index('changed_at', 'idx_audit_changed_at');
            $table->foreign('changed_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
