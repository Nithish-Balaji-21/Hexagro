<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('outstanding_batches') && ! Schema::hasTable('outstanding_lines')) {
            Schema::drop('outstanding_batches');
        }

        if (! Schema::hasTable('outstanding_batches')) {
            Schema::create('outstanding_batches', function (Blueprint $table) {
                $table->id();
                $table->string('kind', 20);
                $table->date('batch_date');
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['kind', 'batch_date']);
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });

            Schema::create('outstanding_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('batch_id')->constrained('outstanding_batches')->cascadeOnDelete();
                $table->unsignedInteger('cost_center_id');
                $table->foreign('cost_center_id')->references('id')->on('cost_centers');
                $table->string('party_name', 150);
                $table->decimal('amount', 14, 2);
                $table->string('notes', 255)->nullable();
                $table->timestamps();

                $table->index('batch_id');
            });

            $this->migratePurchasesToBatches();
            $this->migrateSalesToBatches();
        }

        $this->refreshOutstandingViews();
    }

    public function down(): void
    {
        $this->restoreLegacyOutstandingViews();

        Schema::dropIfExists('outstanding_lines');
        Schema::dropIfExists('outstanding_batches');
    }

    private function migratePurchasesToBatches(): void
    {
        $dates = DB::table('purchases')
            ->whereNotNull('txn_date')
            ->whereNotNull('total_billed')
            ->select('txn_date')
            ->distinct()
            ->orderBy('txn_date')
            ->pluck('txn_date');

        foreach ($dates as $date) {
            $batchId = DB::table('outstanding_batches')->insertGetId([
                'kind' => 'payable',
                'batch_date' => $date,
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $rows = DB::table('purchases')
                ->where('txn_date', $date)
                ->whereNotNull('total_billed')
                ->get();

            foreach ($rows as $row) {
                DB::table('outstanding_lines')->insert([
                    'batch_id' => $batchId,
                    'cost_center_id' => $row->cost_center_id,
                    'party_name' => $row->vendor_name,
                    'amount' => $row->balance,
                    'notes' => $row->notes,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function migrateSalesToBatches(): void
    {
        $dates = DB::table('sales')
            ->whereNotNull('txn_date')
            ->select('txn_date')
            ->distinct()
            ->orderBy('txn_date')
            ->pluck('txn_date');

        foreach ($dates as $date) {
            $batchId = DB::table('outstanding_batches')->insertGetId([
                'kind' => 'receivable',
                'batch_date' => $date,
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $rows = DB::table('sales')
                ->where('txn_date', $date)
                ->get();

            foreach ($rows as $row) {
                DB::table('outstanding_lines')->insert([
                    'batch_id' => $batchId,
                    'cost_center_id' => $row->cost_center_id,
                    'party_name' => $row->customer_name,
                    'amount' => $row->balance,
                    'notes' => $row->notes,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function refreshOutstandingViews(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_receivables_by_unit');
        DB::statement('DROP VIEW IF EXISTS v_payables_by_unit');

        DB::statement("
            CREATE OR REPLACE VIEW v_payables_by_unit AS
            SELECT ol.cost_center_id, SUM(ol.amount) AS total_payable
            FROM outstanding_lines ol
            INNER JOIN outstanding_batches ob ON ob.id = ol.batch_id
            WHERE ob.kind = 'payable'
              AND ob.batch_date = (
                SELECT MAX(batch_date) FROM outstanding_batches WHERE kind = 'payable'
              )
            GROUP BY ol.cost_center_id
        ");

        DB::statement("
            CREATE OR REPLACE VIEW v_receivables_by_unit AS
            SELECT ol.cost_center_id, SUM(ol.amount) AS total_receivable
            FROM outstanding_lines ol
            INNER JOIN outstanding_batches ob ON ob.id = ol.batch_id
            WHERE ob.kind = 'receivable'
              AND ob.batch_date = (
                SELECT MAX(batch_date) FROM outstanding_batches WHERE kind = 'receivable'
              )
            GROUP BY ol.cost_center_id
        ");
    }

    private function restoreLegacyOutstandingViews(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_receivables_by_unit');
        DB::statement('DROP VIEW IF EXISTS v_payables_by_unit');

        DB::statement('
            CREATE OR REPLACE VIEW v_payables_by_unit AS
              SELECT cost_center_id, SUM(balance) AS total_payable
              FROM purchases WHERE total_billed IS NOT NULL GROUP BY cost_center_id
        ');

        DB::statement('
            CREATE OR REPLACE VIEW v_receivables_by_unit AS
              SELECT cost_center_id, SUM(balance) AS total_receivable
              FROM sales GROUP BY cost_center_id
        ');
    }
};
