# Hexagro — Local Development Setup

## Prerequisites

- PHP 8.3+
- Composer
- Node.js 20+
- MySQL 8.0.16+

## Database

1. Create the database:

```sql
CREATE DATABASE hexagro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Set credentials in `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hexagro
DB_USERNAME=root
DB_PASSWORD=your_password
```

3. After Phase 1 migrations, switch sessions back to database if desired:

```
SESSION_DRIVER=database
```

## Run locally

Terminal 1:

```sh
php artisan serve
```

Terminal 2:

```sh
npm run dev
```

Open http://localhost:8000 — you should see the Phase 0 status page with Hexagro branding.

## Installed packages (Phase 0)

| Package | Purpose |
|---|---|
| livewire/livewire ^3.8 | Interactive UI |
| maatwebsite/excel ^3.1 | CSV/XLSX import |
| laravel/boost ^2.7 | AI dev guidelines |
| chart.js ^4.5 | Dashboard charts |

## Next step

Phase 1 — run MySQL migrations from `docs/Hexagro_Database_Schema.md` and seed reference data.
