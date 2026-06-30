# AGENTS.md

## Project

Laravel 12 (PHP 8.2+) IT asset & software license compliance system for USN Kolaka. Blade + Tailwind CSS 4 frontend, REST API for remote agent scanners, MySQL in production, SQLite for tests.

## Commands

```bash
# Full dev environment (server + queue worker + log tail + vite, all concurrent)
composer dev

# Run tests (Pest on SQLite :memory:, no external services needed)
composer test          # clears config cache then runs `php artisan test`
php artisan test --filter=SomeTest   # single test

# Code formatting
./vendor/bin/pint

# Migrations
php artisan migrate --seed   # seeds roles + default admin/pimpinan users

# One-shot setup (composer install, key:generate, migrate, npm install, npm build)
composer setup
```

## Architecture

- **Two auth guards**: `web` (User model, session) and `sanctum` (Computer model as Authenticatable, token-based for agent API).
- **Computer is Authenticatable** (`app/Models/Computer.php` extends `Illuminate\Foundation\Auth\User`), not a regular Model. It uses `HasApiTokens` for Sanctum.
- **Queue processing is required** for scan results. Jobs dispatch to named queues: `scans`, `compliance`, `default`. Worker command: `php artisan queue:listen --queue=scans,compliance,default --tries=3 --timeout=120`. Redis is the queue driver (predis client).
- **Key jobs**: `ProcessScanResultJob` (processes agent scan data), `GenerateComplianceReportJob` (runs compliance checks).
- **Services**: `SoftwareFilterService` (filters/normalizes discovered software names), `SoftwareCatalogService` (manages the master software catalog).
- **License key encryption**: `LicenseInventory.license_key` uses Laravel's `encrypted` cast. Never change encryption logic in the model. The field is in `$hidden` and exposed only via the `masked_license_key` accessor.

## Roles & Permissions (spatie/laravel-permission)

- `admin` -- full access, all mutations
- `pimpinan` -- read-only (dashboard, reports, view computers/licenses/compliance)
- Routes enforce roles via `role:admin|pimpinan` and `role:admin` middleware in `routes/web.php`

## Custom Config Files

- `config/compliance.php` -- blocked software list (piracy tools like KMSPico, uTorrent, etc.)
- `config/software_whitelist.php` -- freeware/open-source auto-approval keywords with category mapping

## Testing

- Framework: **Pest** (not PHPUnit directly)
- Tests use SQLite `:memory:` (configured in `phpunit.xml`), no Redis/MySQL needed
- `RefreshDatabase` is commented out in `tests/Pest.php`; individual feature tests handle their own DB setup
- Feature tests cover: agent auth, scan processing, compliance report generation, RBAC, license key encryption, account management, activity logs
- CI runs on PHP 8.4 + Node 20 (`.github/workflows/deploy.yml`)

## Environment Quirks

- `AGENT_REGISTRATION_KEY` and `DEFAULT_USER_PASSWORD` are env vars used by seeders/agent registration -- not standard Laravel vars
- Default timezone is `Asia/Makassar` (WITA, UTC+8), locale is `id` (Indonesian)
- `REDIS_CLIENT=predis` (not phpredis extension)
- `QUEUE_CONNECTION=redis` in production, `sync` in tests
- Docker setup uses MySQL 8.0 + Redis 7; the `app` service is PHP-FPM behind Nginx

## API Endpoints (routes/api.php)

- `POST /api/agent/register` -- public, throttled 5/min, registers a Computer and returns Sanctum token
- `POST /api/scan-result` -- sanctum-authed, receives software scan payload
- `GET /api/agent/scan-command` -- sanctum-authed, agent polls for scan requests

## Directory Guide

- `app/Http/Controllers/Api/` -- agent-facing API controllers
- `app/Http/Controllers/` -- web admin panel controllers
- `app/Http/Requests/` -- form request validation (Store/Update for License, Computer, Account, Software)
- `app/Observers/` -- model observers for Computer, LicenseInventory, SoftwareCatalog
- `app/Exports/` -- Excel/PDF export classes (maatwebsite/excel, barryvdh/laravel-dompdf)
- `script/agent/` -- PowerShell scanner scripts deployed to client machines (not part of the Laravel app)
- `lang/id/` -- Indonesian translations
- `prompt/` -- reference prompts and data files (not application code)

## Conventions

- Commit messages follow conventional commits: `feat:`, `fix:`, etc.
- Indonesian used in UI labels, seeder descriptions, and log messages. Code (variables, classes, comments in logic) is in English.
- Feature branches named `feature/FeatureName`, PRs target `main`.
- Deployment: push to `main` triggers CI test then SSH deploy to VPS (`docker compose up -d --build`).
