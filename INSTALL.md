# Installation — Matjar

These are the exact toolchain commands used to build and verify this project on Windows. Everything runs locally; no Docker/WSL and no global installs.

## Requirements

- PHP **8.4** at `php` with `pdo_sqlite`, `sqlite3`, `mbstring`, `fileinfo`, `gd`, `intl`, `zip`
- Composer 2 at `composer`
- Node.js 24 / npm 11
- Git

Versions verified on this machine:

```text
PHP 8.4.28 (cli)
Composer 2.11.2
Node v24.12.0
npm 11.6.2
```

## 1. Create the project

```powershell
composer create-project laravel/laravel matjar --no-interaction --prefer-dist
cd matjar
```

## 2. Install development/test tooling

```powershell
composer require pestphp/pest pestphp/pest-plugin-laravel --dev --with-all-dependencies --no-interaction
```

## 3. Install front-end dependencies

```powershell
npm install
npm install alpinejs
```

The project intentionally does **not** use the Laravel starter's Bunny font plugin or any CDN. Rubik (headings) and Noto Sans Arabic (body) are installed from npm via `@fontsource-variable/*` (SIL OFL) and bundled into `public/build/assets/` by Vite — no font is fetched at runtime.

## 4. Environment

The project ships `.env.example` with dummy values and `APP_DEBUG=false`. Locally:

```powershell
copy .env.example .env
```

Set at least:

```dotenv
APP_NAME="Matjar"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8107
APP_LOCALE=ar
APP_FALLBACK_LOCALE=ar
DEMO_MODE=true
DB_CONNECTION=sqlite
FILESYSTEM_DISK=public
SESSION_DRIVER=database
```

Then:

```powershell
php artisan key:generate
```

The local `.env` is git-ignored; only `.env.example` with dummy values is committed.

## 5. Database, storage and assets

```powershell
if (-not (Test-Path database\database.sqlite)) { New-Item -ItemType File database\database.sqlite }
php artisan migrate:fresh --seed --force
php artisan storage:link
npm run build
```

`migrate:fresh --seed` also creates the demo catalog, demo users, demo coupons and demo orders. Demo orders are labelled as demo and are skipped automatically in `production`.

## 6. Run locally (port 8107)

```powershell
php artisan serve --port=8107
```

Open `http://localhost:8107`.

For front-end hot reload during development (optional, still port 8107 for the app):

```powershell
npm run dev
php artisan serve --port=8107
```

## Demo accounts

| Role | Email | Password |
|---|---|---|
| Admin | `admin@matjar.test` | `Demo12345` |
| Staff | `staff@matjar.test` | `Demo12345` |
| Customer | `customer@matjar.test` | `Demo12345` |

## Verification commands

```powershell
php artisan test
npm run build
```

Expected final result at the time of writing: 168 passing tests (848 assertions) and a successful Vite production build. See `TESTING.md`.

## Demo mode

- Set `DEMO_MODE=true` (the default locally) to show the “Try the demo” entry on the
  login and landing pages. Visitors can sign in with one click as Admin, Staff or
  Customer; demo accounts are flagged `is_demo` and get a tighter (2 MB) image
  upload limit.
- Set `DEMO_MODE=false` to hide the demo entry points; the `/demo` and
  `/demo/login` routes then return 404.
- Reset the demo to its seeded baseline (wipes the demo DB and reseeds):

  ```powershell
  php artisan demo:reset --force
  ```

  The command refuses to run when `DEMO_MODE=false`, and requires `--force` in
  production. `routes/console.php` schedules it hourly while demo mode is enabled
  (run `php artisan schedule:work` or a cron entry to activate).

## Troubleshooting

- **`no such table`** — run `php artisan migrate:fresh --seed`.
- **Product images 404** — run `php artisan storage:link`.
- **Styles missing** — run `npm run build` (or `npm run dev` with the dev server).
- **Database is locked (SQLite)** — stop other PHP processes using the same file; SQLite is single-writer by design in this demo.
- **Demo seed skipped** — seeders deliberately no-op when `APP_ENV=production`.

## What not to do

- Do not commit `.env` or real credentials.
- Do not point `APP_URL` at a CDN or add analytics/trackers; assets are self-hosted.
- Do not use the simulated payment gateway for real payments.
