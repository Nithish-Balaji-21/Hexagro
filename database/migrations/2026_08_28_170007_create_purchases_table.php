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
        Schema::create('purchases', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cost_center_id');
            $table->string('vendor_name', 150);
            $table->decimal('total_billed', 14, 2)->nullable();
            $table->decimal('total_paid', 14, 2)->default(0);
            $table->decimal('balance', 14, 2)->storedAs('total_billed - total_paid');
            $table->string('notes', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('cost_center_id', 'idx_purchase_cc');
            $table->index('vendor_name', 'idx_purchase_vendor');
            $table->foreign('cost_center_id')->references('id')->on('cost_centers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
