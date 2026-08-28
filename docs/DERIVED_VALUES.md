# Derived values — Hexagro Shareholding

All INR amounts are stored as `DECIMAL(14,2)`. Intermediate settlement math uses scale 6, then rounds with half-up via `App\Support\Money`.

Nothing in this file is stored as a source of truth except settlement ledger payments and manual adjustments.

---

## Funding breakdown (`FundingBreakdownService`)

Per entity `E` and cost center `U`:

```
expenses(E,U)       = SUM(debit_transactions WHERE paid_through=E AND cost_center=U AND category=EXPENSE)
raw_materials(E,U)  = SUM(debit_transactions WHERE paid_through=E AND cost_center=U AND category=RAW_MATERIALS)
other_debits(E,U)   = SUM(transfers WHERE to_entity=E AND cost_center=U)
credits(E,U)        = SUM(credit_transactions WHERE received_to=E AND cost_center=U)
                    + SUM(transfers WHERE from_entity=E AND cost_center=U)
entity_total(E,U)   = expenses + raw_materials + other_debits − credits
```

`other_debits` is how Transfer Fund rows from the Excel Debit sheet land after transfers are a first-class table. Credits include both business inflows and transfers out of the entity.

---

## Settlement (`SettlementService` + `config/hexagro.php`)

Alam attribution and UBI eligibility live in config, not extra tables.

```
paid_directly(S,U)  = entity_total(S,U) for shareholder S

alam_net(U)         = entity_total(Alam, U)
                    + (U is Fibre Unit
                        ? SUM(historical_alam_expenses.amount) × hist_alam_share_pct
                        : 0)

alam_share(S,U)     = alam_net(U) × alam_attribution[S]
                    # Jagadeesan 50%, Jagadeshwaran 50%, Vellingiri 0, Vikas 0

ubi_pool(U)         = Σ entity_total(E,U) for E of type BANK_ACCOUNT
ubi_share(S,U)      = ubi_pool(U) / |ubi_participants[U]|   if S is a UBI participant
                    = 0                                    otherwise
                    # Vikas is excluded on every unit, including Fibre

contribution(S,U)   = paid_directly + alam_share + ubi_share
unit_total_cost(U)  = Σ contribution(S,U) for shareholders with a stake in U
fair_share(S,U)     = unit_total_cost(U) × share_pct(S,U)
                    # share_pct is NOT renormalized (Fibre 0.2222×3 + 0.3333 = 0.9999)
net(S,U)            = contribution − fair_share
```

Ownership comes from `shareholder_shares` (latest `effective_from` on or before today).

### Overall position

```
overall_net(S)      = Σ net(S,U) across selected units (missing unit = 0)
adjustment(S)       = −amount if S is from_entity of settlement_adjustments
                    +amount if S is to_entity
adjusted_net(S)     = overall_net + adjustment
```

Seeded adjustment: Jagadeshwaran → Vellingiri ₹116,980.

### Outstanding after settlement ledger

Payer (`from`) increases outstanding; receiver (`to`) decreases it (prototype `outstandingNet()`).

```
outstanding(S, U)       = net(S,U)
                        + SUM(settlement_ledger_entries WHERE unit_scope=U.name AND from=S)
                        − SUM(settlement_ledger_entries WHERE unit_scope=U.name AND to=S)

outstanding(S, Overall) = adjusted_net(S)
                        + SUM(ledger WHERE from=S AND (unit_scope=Overall OR unit_scope is a unit name))
                        − SUM(ledger WHERE to=S AND (unit_scope=Overall OR unit_scope is a unit name))
```

**Balanced** when `|outstanding| < 1.00` (`hexagro.settlement_balanced_tolerance`).

### Suggested transfers

Greedy match of payers (outstanding < −0.5) to receivers (outstanding > 0.5), largest first. Same algorithm as prototype `computeTransfers()`.

---

## Entity ledger (`EntityLedgerService` / SQL views)

Sign convention (matches Ledger Book):

```
Credit (+) = debits paid through E + transfers to E
Debit  (−) = credits received to E + transfers from E
running_balance = cumulative signed amount
```

`v_entity_ledger` computes running balance **partitioned by entity across all units**. When the unit switcher filters cost centers, `EntityLedgerService` reads `v_entity_ledger_raw` and recomputes the running balance in PHP so skipped units do not leak into the series.

Direct contribution (no Alam/UBI): `v_shareholder_contribution`.

---

## Monthly spend (`MonthlySpendService`)

Fiscal year starts in April (`hexagro.fiscal_year_start_month`).

```
expenses(month, U)      = SUM(debits WHERE category=EXPENSE)
raw_materials(month, U) = SUM(debits WHERE category=RAW_MATERIALS)
total                   = expenses + raw_materials
```

---

## Banking (`BankingService`)

Latest `banking_snapshots` row by `as_of_date`, then `id`.

```
cc_available = cc_limit − cc_utilised
alam_payable = −alam_utilised
```

`alam_utilised` on the snapshot is a stored point-in-time figure (prototype DATA_BANKING). After Zoho import it should stay in sync with `SUM(debits WHERE paid_through=Alam)` plus any policy the business confirms.

---

## Purchases / sales (generated columns)

```
purchases.balance = total_billed − total_paid     # NULL billed ⇒ NULL balance (TBD)
sales.balance     = total_invoiced − total_received
```

Payables / receivables rollups: `v_payables_by_unit`, `v_receivables_by_unit`. Informational — excluded from settlement.

---

## Phase 6 validation targets (not enforced yet)

After the full Zoho export is imported, Fibre Jagadeesan should match the prototype within ₹0.01:

| Field | Target |
|---|---|
| contribution | 954,706.30 |
| fair share | 639,925.42 |
| net | 314,780.88 |

`php artisan hexagro:validate-settlement` currently prints `ready for Zoho export` and smoke-runs Fibre settlement. It will compare these targets in Phase 6.

### Historical Alam note

Prototype UI copy says 66.666% of historical Alam is folded into Fibre Alam share. Hardcoded `DATA_SETTLEMENT` Fibre Alam share (₹1,890.50) is 50% of live Alam `entity_total` only (₹3,781) and does **not** include that historical fold. The service follows the documented/config rule (fold historical into Fibre `alam_net`). Confirm against Excel Settlement before locking Phase 6 tolerances.

Historical rows seeded: **30** (prototype `RAW_HIST_ALAM`). Handoff mentioned 31; the HTML array has 30.
