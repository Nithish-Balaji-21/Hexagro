# Phase 1 Handoff — Database Layer

Use this document to start Phase 1 in a new Cursor chat. Phase 0 is complete.

---

## Copy-paste prompt for new chat

```
Continue Hexagro Shareholding app — Phase 1 only.

## Context
- Laravel 13 + Livewire 3 + MySQL 8 + Tailwind 4
- Phase 0 done: Livewire, maatwebsite/excel, Boost, Chart.js, design tokens, config/hexagro.php
- Repo: /home/imik/Imik/Hexagro_shareholder

## Phase 1 deliverables (do NOT build UI yet)
1. Laravel migrations matching the approved MySQL schema below (tables + views + gap tables)
2. PHP 8 backed Enums (UserRole, EntityType, DebitCategory, CreditType)
3. Eloquent models with relationships and decimal casts
4. Read-only models for SQL views (v_entity_ledger, etc.)
5. Reference seeders: users, cost_centers, entities, shareholder_shares, banking_snapshots, historical_alam_expenses, settlement_adjustments
6. Domain services: FundingBreakdownService, SettlementService, EntityLedgerService, MonthlySpendService, BankingService, AuditLogService
7. docs/DERIVED_VALUES.md — formula reference
8. php artisan hexagro:validate-settlement — stub that prints "ready for Zoho export"

## Schema source of truth
Use the MySQL DDL the user approved in chat (entities, debit_transactions, credit_transactions, transfers, purchases, sales, settlement_ledger_entries, banking_snapshots, audit_log + views). Also add gap tables:
- historical_alam_expenses
- settlement_adjustments

Business rules in config/hexagro.php (Alam 50/50, UBI equal-split, unit visibility).

## Important notes
- Do NOT seed debit/credit transactions yet — user will provide full Zoho export in Phase 6
- Replace default Laravel users migration with Hexagro users schema
- purchases.balance and sales.balance = MySQL GENERATED columns
- Fair share / Alam share / UBI share computed in SettlementService (not stored)
- Read docs/Hexagro_Database_Schema.md for business rules; use simpler table names from approved SQL

## After Phase 1
Stop and report what was built. Wait for confirmation before Phase 2 (login shell).
```

---

## Phase 0 completed (already in repo)

| Item | Status |
|------|--------|
| `livewire/livewire` ^3.8 | Installed |
| `maatwebsite/excel` ^3.1 | Installed |
| `laravel/boost` ^2.7 | Installed + configured |
| `chart.js` ^4.5 | Installed |
| `config/hexagro.php` | Alam/UBI/unit rules |
| `resources/css/app.css` | Prototype design tokens |
| `resources/views/components/layouts/app.blade.php` | Livewire layout shell |
| `resources/views/welcome.blade.php` | Phase 0 status page |
| `.env` | MySQL `hexagro` configured; `SESSION_DRIVER=file` until migrations |

**Not yet done:** No Hexagro migrations, no domain models, no seeders beyond Laravel defaults.

---

## Approved MySQL schema (implement as Laravel migrations)

### Reference tables
- `users` — id, name, initials, role ENUM(ADMIN,VIEWER), password_hash NULL, created_at
- `cost_centers` — id, name UNIQUE
- `entities` — id, name, short_name, entity_type ENUM(SHAREHOLDER,NON_SHAREHOLDER_FUNDER,BANK_ACCOUNT), is_active
- `shareholder_shares` — cost_center_id, entity_id, share_pct DECIMAL(7,4), effective_from

### Transactional tables
- `debit_transactions` — txn_date, cost_center_id, category, account, paid_through_entity_id, description, amount, created_by, updated_by
- `credit_transactions` — txn_date, cost_center_id, credit_type, received_to_entity_id, description, amount, created_by, updated_by
- `transfers` — txn_date, cost_center_id, from_entity_id, to_entity_id, note, amount, created_by (CHECK from ≠ to, amount > 0)
- `purchases` — vendor_name, total_billed NULL, total_paid, balance GENERATED
- `sales` — customer_name, total_invoiced, total_received, balance GENERATED
- `settlement_ledger_entries` — txn_date, unit_scope VARCHAR, from_entity_id, to_entity_id, amount, note, created_by
- `banking_snapshots` — as_of_date, cc_limit, cc_utilised, current_balance, term_loan, alam_utilised, created_by
- `audit_log` — table_name, record_id, action, changed_by, before_data JSON, after_data JSON

### Gap tables (add in Phase 1)
```sql
historical_alam_expenses (id, txn_date, account, description, amount, created_by, created_at)
settlement_adjustments (id, from_entity_id, to_entity_id, amount, note, created_by, created_at)
```

### SQL views (DB::statement in migration)
- `v_entity_ledger_raw` — union debits/credits/transfers with signed amounts
- `v_entity_ledger` — running balance window function
- `v_shareholder_contribution` — direct contribution per shareholder per unit
- `v_payables_by_unit` / `v_receivables_by_unit`

Full DDL was pasted by user in prior chat; see also `docs/Hexagro_Database_Schema.md` for business analysis.

---

## Seed data (reference only — no transactions)

### Users
| name | initials | role |
|------|----------|------|
| Jagadeesan | JD | ADMIN |
| Jagadeshwaran | JW | VIEWER |
| Vellingiri | VG | VIEWER |
| Vikas | VK | VIEWER |

### Entities (8)
Shareholder - Jagadeesan, Jagadeshwaran, Vellingiri, Vikas, Payable to Alam, Union Bank - CC, Current, Term Loan

### shareholder_shares
- Fibre: JD/JW/VG = 0.2222, Vikas = 0.3333
- Chips/Washing: JD/JW/VG = 0.3333

### banking_snapshots (from prototype DATA_BANKING)
- as_of: 2026-08-09, cc_limit: 5000000, cc_utilised: 3718510, current_balance: 274018, term_loan: 13326000, alam_utilised: 1461586.25

### settlement_adjustments
- Jagadeshwaran → Vellingiri: ₹116,980 (manual true-up)

### historical_alam_expenses
- 31 rows from prototype RAW_HIST_ALAM (Fibre Unit pre-settlement)

---

## Derived formulas (SettlementService)

```
paid_directly   = shareholder entity_total from funding breakdown
alam_share      = alam_entity_total × alam_attribution (JD/JW 50%)
ubi_share       = gross_bank_spend / ubi_participant_count (Vikas excluded on Fibre)
contribution    = paid_directly + alam_share + ubi_share
fair_share      = unit_total_cost × share_pct
net             = contribution − fair_share
outstanding     = net ± settlement_ledger_entries ± adjustments
```

Validation targets (after full export in Phase 6):
- Fibre Jagadeesan: contribution ₹954,706.30, fair share ₹639,925.42, net ₹314,780.88

---

## Key files to read first

1. `docs/Hexagro_Database_Schema.md` — business rules & analysis
2. `config/hexagro.php` — attribution config
3. `hexagro-shareholding-prototype-3 (1).html` — DATA_SETTLEMENT, RAW_* arrays
4. `Hexagro_Shareholding_Data (10).xlsx` — full transaction source (289 debits)
5. `.cursor/plans/hexagro_laravel_rebuild_861fe26d.plan.md` — full build plan

---

## MySQL setup (user must configure)

```sql
CREATE DATABASE hexagro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Set `DB_USERNAME` and `DB_PASSWORD` in `.env`, then:

```sh
php artisan migrate
php artisan db:seed
```

---

## Phase 1 exit criteria

- [ ] `php artisan migrate` succeeds on MySQL 8
- [ ] `php artisan db:seed` loads reference data
- [ ] All Eloquent models + enums exist
- [ ] SettlementService computes from empty transactions without error
- [ ] `php artisan hexagro:validate-settlement` runs
- [ ] No Livewire pages built yet (Phase 2)
