# Phase 2 Handoff — Auth, Policies & App Shell

Use this document to start Phase 2 in a new Cursor chat. Phase 1 is complete.

---

## Copy-paste prompt for new chat

```
Continue Hexagro Shareholding app — Phase 2 only.

## Context
- Laravel 13 + Livewire 3 + MySQL 8 + Tailwind 4
- Repo: /home/imik/Imik/Hexagro_shareholder
- Phase 1 done: migrations, models, enums, seeders, domain services, docs/DERIVED_VALUES.md
- MySQL `hexagro` migrated + seeded (reference data only; no debit/credit transactions)
- 14 tests passing

## Phase 2 deliverables (auth + shell only — do NOT build transaction/report pages yet)
1. Auth — Livewire `Auth/Login`: 4 user cards (JD/JW/VG/VK), click → session login → `/dashboard`
   - `password_hash` NULL = click-through; non-null = defer real password UI to later
2. Policies — `AdminPolicy` + per-model policies; admin-only writes; viewers read-only
3. `UnitScope` trait/middleware — admin sees all units; viewer sees units from `config/hexagro.php` `unit_shareholders`
   - Session: `selected_units` (cost center IDs); default = all visible units
4. `AuditableObserver` + wire `AuditLogService` on financial model writes (pattern only; full CRUD in Phase 3+)
5. App layout shell matching prototype:
   - Sidebar (Overview / Transactions / Reports / Finance nav groups)
   - Topbar: breadcrumb, unit switcher, profile/logout
   - Mobile drawer below 900px
6. Livewire: `Layout/UnitSwitcher`, stub `Dashboard` (welcome + unit scope smoke test)
7. Routes: `/` = login (guest), `/dashboard` = auth; logout
8. Blade UI components: card, kpi-card, page-head, empty-state (minimal set for shell)
9. `SESSION_DRIVER=database` in `.env` (sessions table exists)

## Rules
- Match prototype UI: hexagro-shareholding-prototype-3 (1).html (login + sidebar/topbar)
- Use existing design tokens in resources/css/app.css
- Do NOT build Debit/Credit/Settlement pages yet (Phase 3+)
- Stop after Phase 2 and wait for confirmation before Phase 3

## Read first
1. docs/PHASE1_HANDOFF.md — what Phase 1 built
2. docs/DERIVED_VALUES.md — settlement formulas
3. config/hexagro.php — unit visibility, Alam/UBI
4. hexagro-shareholding-prototype-3 (1).html — login (USERS), sidebar nav, unit switcher
5. .cursor/plans/hexagro_laravel_rebuild_861fe26d.plan.md — Phase 2 spec (auth + shell sections)
```

---

## Phase 1 completed (already in repo)

| Item | Status |
|------|--------|
| Migrations (tables + views + gap tables) | Done |
| Enums: UserRole, EntityType, DebitCategory, CreditType, AuditAction | Done |
| Eloquent models + view models | Done |
| Seeders: users, cost_centers, entities, shares, banking, historical Alam, adjustment | Done |
| Services: FundingBreakdown, Settlement, EntityLedger, MonthlySpend, Banking, AuditLog | Done |
| `docs/DERIVED_VALUES.md` | Done |
| `php artisan hexagro:validate-settlement` | Done (stub) |
| `php artisan migrate` + `db:seed` on MySQL | Done |
| Feature tests (14 passing) | Done |

**Not yet done:** Login, auth middleware, policies, app shell, unit switcher, dashboard stub.

---

## Seeded reference data

| Table | Count |
|-------|-------|
| users | 4 (Jagadeesan=ADMIN, others=VIEWER) |
| cost_centers | 3 (Fibre, Chips, Washing) |
| entities | 8 |
| shareholder_shares | 10 |
| historical_alam_expenses | 30 |
| banking_snapshots | 1 (2026-08-09) |
| settlement_adjustments | 1 (JW→VG ₹116,980) |
| debit/credit transactions | 0 (Zoho export in Phase 6) |

---

## Phase 2 exit criteria

- [ ] Guest visits `/` → login screen with 4 user cards
- [ ] Click user → logged in → `/dashboard` stub
- [ ] Viewer (e.g. Vikas) sees Fibre only in unit switcher; Admin sees all 3
- [ ] Logout works
- [ ] Sidebar + topbar layout matches prototype structure
- [ ] Policies registered; viewer cannot hit admin write actions (test or smoke)
- [ ] `SESSION_DRIVER=database`
- [ ] No Debit/Credit/Settlement pages yet

---

## After Phase 2

Stop and report what was built. Wait for confirmation before Phase 3 (Dashboard + Debit + Credit).
