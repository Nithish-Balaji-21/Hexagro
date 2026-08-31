# Hexagro Shareholder Settlement

Laravel + Livewire application for tracking debits, credits, transfers, and computing shareholder settlement across Hexagro's three business units: **Fibre**, **Chips**, and **Washing**.

## Features

- **Dashboard** — summary charts and key metrics
- **Transactions** — Debit, Credit, and Transfer entry
- **Reports** — Monthly Spend, Settlement, Purchases, Sales
- **Finance** — Banking snapshots, Entity Ledger, Historical Alam expenses
- **Excel import** — bulk workbook import with downloadable templates (admin only)

## Tech stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.3+, Laravel 13, Livewire 3 |
| Database | MySQL 8.0.16+ |
| Frontend | Tailwind CSS 4, Vite, Chart.js |
| Import | PhpSpreadsheet |

## Requirements

- PHP 8.3+
- Composer
- Node.js 20+
- MySQL 8.0.16+

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure database credentials in `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hexagro_shareholder
DB_USERNAME=root
DB_PASSWORD=your_password
```

Then run migrations and build assets:

```bash
php artisan migrate --seed
npm install && npm run build
```

Alternatively, use the composer shortcut (installs dependencies, generates key, migrates, and builds):

```bash
composer run setup
```

## Environment

Key variables (see `.env.example` for the full list):

| Variable | Purpose |
|---|---|
| `APP_NAME` | Application name (default: Hexagro) |
| `DB_*` | MySQL connection |
| `SESSION_DRIVER` | Session storage (default: `database`) |

## Running locally

Terminal 1:

```bash
php artisan serve
```

Terminal 2:

```bash
npm run dev
```

Open http://localhost:8000 — the login page is at `/`.

Seeded users (from `ReferenceSeeder`): Jagadeesan (admin), Jagadeshwaran, Vellingiri, Vikas (viewers). Passwordless login selects a user by name.

## Production deployment

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Additional checklist:

- Set `APP_ENV=production` and `APP_DEBUG=false`
- Ensure `APP_KEY` is set and kept secret
- Point the web server document root to `public/`
- Configure MySQL credentials in the server `.env`

## Project structure

```
app/
  Livewire/          UI pages (dashboard, reports, transactions, import)
  Services/          SettlementService, FundingBreakdownService, import pipeline
  Models/            Eloquent models and SQL views
  Console/Commands/  Import, ledger rebuild, settlement validation
config/
  hexagro.php        Alam attribution, UBI participants, fiscal year, tolerances
database/
  migrations/        Database schema (source of truth)
  seeders/           Reference data (users, entities, cost centers, shares)
resources/views/
  livewire/          Blade templates for Livewire components
routes/web.php       Application routes
```

## Routes

| Path | Page |
|---|---|
| `/` | Login |
| `/dashboard` | Dashboard |
| `/debit` | Debit transactions |
| `/credit` | Credit transactions |
| `/transfers` | Transfers |
| `/monthly-spend` | Monthly spend report |
| `/settlement` | Settlement report |
| `/purchases` | Purchases |
| `/sales` | Sales |
| `/banking` | Banking snapshots |
| `/ledger-book` | Entity ledger |
| `/historical-alam` | Historical Alam expenses |
| `/import` | Excel import (admin) |

## Artisan commands

```bash
php artisan hexagro:import-excel {file}     # Bulk Excel import (--dry-run, --only=debit,credit,...)
php artisan hexagro:rebuild-ledger            # Rebuild ledger entries (--entity=id)
php artisan hexagro:validate-settlement       # Settlement smoke check
```

## Domain summary

Hexagro tracks money flowing through **8 funding entities** (4 shareholders, Payable to Alam, 3 Union Bank accounts) across **3 cost centers** (Fibre, Chips, Washing).

Settlement is computed dynamically — nothing is stored except actual shareholder-to-shareholder payments in the settlement ledger and manual adjustments.

**Contribution** per shareholder per unit:

```
contribution = paid_directly + alam_share + ubi_share
```

- **Paid directly** — debits, raw materials, and transfers attributed to the shareholder entity
- **Alam share** — proportional share of Alam's net position (attribution in `config/hexagro.php`)
- **UBI share** — equal split of Union Bank totals among eligible partners (Vikas excluded)

**Net position**:

```
net = contribution − fair_share
fair_share = unit_total_cost × ownership_pct
```

Ownership percentages come from `shareholder_shares` (latest `effective_from` on or before today). The fiscal year starts in April (`fiscal_year_start_month = 4`).

Settlement ledger entries and manual adjustments update outstanding balances. A position is **balanced** when outstanding is within ₹1.00 (`settlement_balanced_tolerance`).

Core logic lives in:

- `App\Services\SettlementService`
- `App\Services\FundingBreakdownService`
- `config/hexagro.php`

## License

MIT
