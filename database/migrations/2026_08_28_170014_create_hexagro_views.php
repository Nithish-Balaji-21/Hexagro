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
        DB::statement('DROP VIEW IF EXISTS v_receivables_by_unit');
        DB::statement('DROP VIEW IF EXISTS v_payables_by_unit');
        DB::statement('DROP VIEW IF EXISTS v_shareholder_contribution');
        DB::statement('DROP VIEW IF EXISTS v_entity_ledger');
        DB::statement('DROP VIEW IF EXISTS v_entity_ledger_raw');

        DB::statement("
            CREATE OR REPLACE VIEW v_entity_ledger_raw AS
              SELECT
                d.paid_through_entity_id AS entity_id,
                d.txn_date,
                d.cost_center_id,
                CONCAT('Paid for ', d.account, IF(d.description IS NULL,'',CONCAT(' — ', d.description))) AS particulars,
                d.amount AS signed_amount,
                'debit_transactions' AS source_table, d.id AS source_id
              FROM debit_transactions d
              UNION ALL
              SELECT
                c.received_to_entity_id, c.txn_date, c.cost_center_id,
                CONCAT('Received', IF(c.description IS NULL,'',CONCAT(' — ', c.description))),
                -c.amount,
                'credit_transactions', c.id
              FROM credit_transactions c
              UNION ALL
              SELECT
                t.from_entity_id, t.txn_date, t.cost_center_id,
                CONCAT('Transfer to ', (SELECT short_name FROM entities e WHERE e.id = t.to_entity_id), IF(t.note IS NULL,'',CONCAT(' — ', t.note))),
                -t.amount,
                'transfers', t.id
              FROM transfers t
              UNION ALL
              SELECT
                t.to_entity_id, t.txn_date, t.cost_center_id,
                CONCAT('Transfer from ', (SELECT short_name FROM entities e WHERE e.id = t.from_entity_id), IF(t.note IS NULL,'',CONCAT(' — ', t.note))),
                t.amount,
                'transfers', t.id
              FROM transfers t
        ");

        DB::statement('
            CREATE OR REPLACE VIEW v_entity_ledger AS
              SELECT
                entity_id, txn_date, cost_center_id, particulars,
                IF(signed_amount < 0, -signed_amount, 0) AS debit,
                IF(signed_amount > 0,  signed_amount, 0) AS credit,
                SUM(signed_amount) OVER (
                  PARTITION BY entity_id
                  ORDER BY txn_date, source_table, source_id
                  ROWS UNBOUNDED PRECEDING
                ) AS running_balance
              FROM v_entity_ledger_raw
        ');

        DB::statement("
            CREATE OR REPLACE VIEW v_shareholder_contribution AS
              SELECT
                e.id AS entity_id, e.short_name, cc.id AS cost_center_id, cc.name AS cost_center_name,
                COALESCE(SUM(CASE WHEN d.paid_through_entity_id = e.id THEN d.amount END), 0)
                  - COALESCE((SELECT SUM(c.amount) FROM credit_transactions c
                              WHERE c.received_to_entity_id = e.id AND c.cost_center_id = cc.id), 0) AS contribution
              FROM entities e
              CROSS JOIN cost_centers cc
              LEFT JOIN debit_transactions d ON d.paid_through_entity_id = e.id AND d.cost_center_id = cc.id
              WHERE e.entity_type = 'SHAREHOLDER'
              GROUP BY e.id, e.short_name, cc.id, cc.name
        ");

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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_receivables_by_unit');
        DB::statement('DROP VIEW IF EXISTS v_payables_by_unit');
        DB::statement('DROP VIEW IF EXISTS v_shareholder_contribution');
        DB::statement('DROP VIEW IF EXISTS v_entity_ledger');
        DB::statement('DROP VIEW IF EXISTS v_entity_ledger_raw');
    }
};
