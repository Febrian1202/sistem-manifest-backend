# AGENTS.md

## Project

Laravel 12 (PHP 8.2+, production on PHP 8.4 Alpine) IT asset & software license compliance system for Universitas Sembilanbelas November (USN) Kolaka. Blade + Tailwind CSS 4 + Alpine.js frontend (ApexCharts & Chart.js), REST API for remote agent scanners (PowerShell), MySQL in production, SQLite for tests, Redis for queue/cache, and Laravel Horizon for queue worker orchestration.

## Commands

```bash
# Full dev environment (server + queue worker + log tail + vite, all concurrent)
composer dev

# Run tests (Pest on SQLite :memory:, no external services needed)
composer test                         # clears config cache then runs `php artisan test`
php artisan test --filter=SomeTest    # single test
npm run test:e2e                      # Playwright E2E test suite (all roles)

# Code formatting
./vendor/bin/pint

# Queue worker & monitoring (Horizon)
php artisan horizon

# Migrations & Seeding (roles, faculties, sample labs, and default accounts)
php artisan migrate --seed

# One-shot setup (composer install, key:generate, migrate, npm install, npm build)
composer setup
```

## Architecture

- **Two auth guards**: `web` (User model, session-based) and `sanctum` (Computer model as Authenticatable, token-based for agent API).
- **Computer is Authenticatable** (`app/Models/Computer.php` extends `Illuminate\Foundation\Auth\User`), using `HasApiTokens` for Sanctum.
- **Organizational Hierarchy & Multi-tier Scoping**:
  - Entity hierarchy: `Faculty` -> `Laboratory` -> `Computer` -> `ScanSession` -> `ScanSoftwareResult`.
  - Data isolation and laboratory-level scoping via `App\Models\Traits\ScopedByLaboratory` and `User::getAccessibleLaboratoryIds()`.
- **Queue Processing with Laravel Horizon**:
  - Redis queue driver (`predis` in local/app config, pecl redis in production container).
  - Horizon worker orchestrator (`php artisan horizon`). Horizon dashboard available at `/horizon` (restricted to `admin` role via `HorizonServiceProvider`).
  - Dedicated queue priorities: `scans`, `compliance`, `default`.
- **Key jobs**:
  - `ProcessScanResultJob` (processes agent scan payload, diffs software changes, updates computer and scan metadata).
  - `GenerateComplianceReportJob` (evaluates software installations against licenses, whitelist, and blocked rules).
- **Key services**:
  - `LicenseComplianceService`: centralized compliance calculation, multi-level faculty/lab quota tracking, entitlement vs installed calculation.
  - `SoftwareChangeDetectionService`: detects software state changes between scan sessions (`added`, `removed`, `changed`, `returned`).
  - `SoftwareFilterService`: filters OS updates and noise, normalizes raw software names.
  - `SoftwareCatalogService`: manages master software catalog and categorization.
- **License key encryption & auditing**:
  - `LicenseInventory.license_key` uses Laravel's `encrypted` cast. Never decrypted in views; exposed only via `masked_license_key` accessor.
  - Decryption endpoint (`POST /licenses/{license}/key`) is throttled (10 req/min) and audit-logged.
- **Activity Logging & Backups**:
  - `spatie/laravel-activitylog`: tracks entity mutations, user accounts, password changes, and license key reveals.
  - `spatie/laravel-backup`: automated database and storage file backups.

## Roles & Permissions (spatie/laravel-permission)

- `admin` -- full system access: user accounts, faculties, laboratories, licenses & allocations, compliance scans, activity logs, Horizon.
- `pimpinan` -- read-only executive view across all faculties: executive dashboard, license needs reports, computer/software/license inventory, compliance status.
- `kepala_lab` -- laboratory coordinator scoped to assigned laboratory/faculty: review/approve/reject laboratory compliance reports, view lab computer inventory & software changes, download lab agent scanner.
- `staff_lab` -- laboratory operator scoped to assigned laboratory/faculty: view lab computer inventory, download lab agent scanner.
- Routes enforce roles via `role:admin|pimpinan|kepala_lab|staff_lab`, `role:admin|pimpinan|kepala_lab`, `role:admin|pimpinan`, `role:admin|kepala_lab`, and `role:admin` middleware in `routes/web.php`.

## Custom Config Files

- `config/compliance.php` -- blocked software list (piracy tools like KMSPico, uTorrent, etc.)
- `config/software_whitelist.php` -- freeware/open-source auto-approval keywords with category mapping
- `config/horizon.php` -- Laravel Horizon supervisors, balancing strategies, and queue configuration
- `config/backup.php` -- Spatie backup configuration (database dump and file backup)

## Testing

- Framework: **Pest** (with underlying PHPUnit 11)
- Tests run on SQLite `:memory:` (configured in `phpunit.xml`), no external Redis/MySQL required
- `RefreshDatabase` is configured in individual feature tests or test traits
- Feature tests cover:
  - Role-based Golden Paths (`AdminGoldenPathTest`, `PimpinanGoldenPathTest`, `KepalaLabGoldenPathTest`, `StaffLabGoldenPathTest`)
  - Faculty & Laboratory scoping (`ScopedByLaboratoryTest`, `MultiLabScopeTest`)
  - License calculations & allocation (`LicenseCalculationAccuracyTest`, `LicenseAllocationTest`)
  - Software change detection & periodic scanning
  - Report submission and approval workflows (`ReportApprovalTest`, `ReportSubmissionTest`)
  - Agent authentication, registration, scan payload processing
  - License key encryption & Activity Log auditing
- End-to-End: **Playwright** (`npm run test:e2e`) covering critical user flows for all 4 roles
- CI runs on PHP 8.4 + Node 20 (`.github/workflows/deploy.yml`)

## Environment Quirks

- `AGENT_REGISTRATION_KEY` -- shared secret key required by client scanner agents to register a computer
- `DEFAULT_USER_PASSWORD` -- seed default user password (default: `ManifestUSN_2026!`)
- Default timezone is `Asia/Makassar` (WITA, UTC+8), locale is `id` (Indonesian)
- `REDIS_CLIENT=predis` (in Laravel `.env`), `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`
- Docker setup: Multi-stage build (`php:8.4-fpm-alpine`, Node 20, Composer 2.7), Nginx web server (host port `8080:80` in `docker-compose.yml`, `127.0.0.1:8080` behind host reverse proxy in `docker-compose.prod.yml`), MySQL 8.0, Redis 7, Horizon worker container, and cron scheduler container.

## API Endpoints (routes/api.php)

- `GET /api/ping` -- public health-check endpoint
- `POST /api/agent/register` -- public, throttled 5/min, registers Computer (with `laboratory_id`) and returns Sanctum token
- `POST /api/scan-result` -- sanctum-authed, throttled 60/min, receives software scan payload (`ScanSoftwareResult`, OS metadata)
- `GET /api/agent/scan-command` -- sanctum-authed, throttled 60/min, agent polls for scan requests

## Directory Guide

- `app/Http/Controllers/Api/` -- agent-facing API controllers (`AgentRegisterController`, `ScanController`, `AgentCommandController`)
- `app/Http/Controllers/` -- web admin & lab portal controllers (Faculties, Laboratories, LicenseAllocations, Reports, Approvals, Submissions, Monitoring, etc.)
- `app/Http/Requests/` -- form request validation classes
- `app/Models/Traits/` -- `ScopedByLaboratory` multi-tenant scoping trait
- `app/Services/` -- `LicenseComplianceService`, `SoftwareChangeDetectionService`, `SoftwareCatalogService`, `SoftwareFilterService`
- `app/Observers/` -- model observers for Computer, LicenseInventory, SoftwareCatalog
- `app/Exports/` -- Excel/PDF export classes (LicenseNeedsExport, SoftwareChangesExport, MonitoringRecapExport, KepatuhanExport, etc.)
- `e2e/` -- Playwright end-to-end tests for all roles (`admin`, `pimpinan`, `kepala_lab`, `staff_lab`)
- `script/agent/` -- PowerShell scanner scripts deployed to client machines (`scanner.ps1`, `setup_tasks.ps1`, `config.example.json`)
- `.docker/` -- Docker entrypoint, Nginx configurations, PHP production INI
- `lang/id/` -- Indonesian translations
- `prompt/` -- reference prompts and planning documents (not application code)

## Conventions

- Commit messages follow conventional commits: `feat:`, `fix:`, `docs:`, `test:`, etc.
- Indonesian used in UI labels, seeder descriptions, and log messages. Code (variables, classes, comments in logic) is in English.
- Feature branches named `feature/FeatureName`, PRs target `main`.
- Deployment: push to `main` triggers GitHub Actions CI (linting, tests, Docker build & push to GHCR) followed by automated SSH deploy to VPS.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.5. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record a rule with `record-rule` only when the user explicitly asks for one. Instructions for the work at hand are not rules, no matter how emphatic: "remove this typo", "use X here" are work to do, not rules to record. Never record a rule on your own initiative, as a byproduct of a change, or to summarize what you just did. When the user does ask, pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Use `record-rule` rather than your native memory or notes tool, because native memory is personal and session-scoped, while only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app/Console/Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.

- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

=== spatie/laravel-activitylog/core rules ===

# spatie/laravel-activitylog

Activity logging package for Laravel. Logs model events and manual activities to a database table.

## Key Concepts

- **Activity**: An Eloquent model (`Spatie\Activitylog\Models\Activity`) storing log entries with subject, causer, event, attribute_changes, and properties.
- **Subject**: The model being acted upon (polymorphic `subject_type`/`subject_id`).
- **Causer**: The model that caused the action, typically the authenticated user (polymorphic `causer_type`/`causer_id`).
- **LogOptions**: Fluent configuration object returned by `getActivitylogOptions()` on models using the `LogsActivity` trait.
- **ActivityEvent**: Enum with cases `Created`, `Updated`, `Deleted`, `Restored`.
- **`attribute_changes`** column: stores `{"attributes": {...}, "old": {...}}` for tracked model changes.
- **`properties`** column: stores custom user data set via `withProperties()`.

## Traits

### `LogsActivity`

Add to models to automatically log create/update/delete events. Optionally implement `getActivitylogOptions()` to configure which attributes to track (defaults to logging events without attribute changes).

```php
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Article extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
```

### `CausesActivity`

Add to user/causer models. Provides `activitiesAsCauser()` relationship.

### `HasActivity`

Combines `LogsActivity` and `CausesActivity`. Provides `activities()`, `activitiesAsSubject()`, and `activitiesAsCauser()`.

## Manual Logging

```php
activity()
    ->performedOn($article)
    ->causedBy($user)
    ->event(ActivityEvent::Updated)
    ->withProperties(['key' => 'value'])
    ->log('Article was updated');
```

## LogOptions Methods

| Method | Description |
|--------|-------------|
| `logFillable()` | Log all fillable attributes |
| `logAll()` | Log all attributes |
| `logOnly(array)` | Log specific attributes |
| `logExcept(array)` | Exclude attributes |
| `logOnlyDirty()` | Only log changed attributes |
| `dontLogEmptyChanges()` | Skip logging when no tracked attributes changed |
| `dontLogIfAttributesChangedOnly(array)` | Ignore updates that only change these attributes |
| `useLogName(string)` | Set custom log name |
| `setDescriptionForEvent(Closure)` | Custom description per event |
| `useAttributeRawValues(array)` | Store raw (uncast) values |

## Querying Activities

```php
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Enums\ActivityEvent;

Activity::forEvent(ActivityEvent::Created)->get();
Activity::causedBy($user)->get();
Activity::forSubject($article)->get();
Activity::inLog('orders')->get();
```

## Setting the causer

Override the causer for a block of code:

```php
use Spatie\Activitylog\Facades\Activity;

Activity::defaultCauser($admin, function () {
    // all activities here are caused by $admin
});

// or set globally for the rest of the request
Activity::defaultCauser($admin);
```

## Disabling Logging

```php
activity()->withoutLogging(function () {
    // no activities logged here
});
```

## Accessing Changes and Properties

```php
$activity = Activity::latest()->first();

// Tracked model changes (set automatically by LogsActivity)
$activity->attribute_changes; // Collection: {"attributes": {...}, "old": {...}}

// Custom user data (set via withProperties)
$activity->properties; // Collection
$activity->getProperty('key'); // single value
```

## Custom Activity Model

Set `activity_model` in `config/activitylog.php` to a class that extends `Model` and implements `Spatie\Activitylog\Contracts\Activity`. Use a custom model for custom table names or database connections.

## Customizing Actions

The package uses action classes (`LogActivityAction`, `CleanActivityLogAction`) that can be extended and swapped via config:

```php
// config/activitylog.php
'actions' => [
    'log_activity' => \App\Actions\CustomLogActivityAction::class,
    'clean_log' => \App\Actions\CustomCleanAction::class,
],
```

Custom action classes must extend the originals. Override protected methods (`save()`, `beforeActivityLogged()`, `resolveDescription()`, etc.) to customize behavior.

## Configuration

Key config options in `config/activitylog.php`:
- `enabled`: Master on/off switch (env: `ACTIVITYLOG_ENABLED`)
- `clean_after_days`: Days to keep records for `activitylog:clean` command
- `default_log_name`: Default log name (string)
- `default_auth_driver`: Auth driver for causer resolution
- `include_soft_deleted_subjects`: Include soft-deleted subjects
- `activity_model`: Custom Activity model class
- `default_except_attributes`: Globally excluded attributes
- `actions.log_activity`: Action class for logging activities
- `actions.clean_log`: Action class for cleaning old activities

</laravel-boost-guidelines>
