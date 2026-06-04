# Gemini Project Context: Sistem Manifest (USN Kolaka)

This document provides essential context and instructions for AI agents working on the Sistem Manifest project, a Laravel-based IT asset and license management system for USN Kolaka.

## Project Overview
- **Purpose:** Manages IT assets (computers) and monitors software license compliance across an organization.
- **Core Workflow:**
    1. **Agent Scanning:** Client machines run a scanner (PowerShell) and send data to the backend via REST API.
    2. **Processing:** The backend processes scan results asynchronously using queues (Laravel Horizon).
    3. **Cataloging:** Discovered software is matched against a master `SoftwareCatalog`.
    4. **Compliance:** The system compares discoveries against `LicenseInventory` to generate `ComplianceReport`.
- **Architecture:** Laravel 12 (MVC), Sanctum (API Auth), Blade (UI), Tailwind CSS (Frontend).

## Technical Stack
- **Backend:** PHP 8.2+, Laravel 12.
- **Frontend:** Blade Templates, Tailwind CSS 4, Vite.
- **Database:** MySQL/SQLite (supports both).
- **Key Libraries:**
    - `spatie/laravel-permission`: Role-Based Access Control (RBAC).
    - `laravel/sanctum`: Token-based authentication for agents.
    - `maatwebsite/excel` & `barryvdh/laravel-dompdf`: Exporting reports.
    - `laravel/horizon`: Queue monitoring.
    - `pestphp/pest`: Testing framework.

## Key Entities & Relationships
- **Computer:** Represents a client machine. Acts as an authenticatable entity for API interactions.
- **SoftwareCatalog:** Master list of software that the organization tracks/manages.
- **SoftwareDiscovery:** Actual software instances found on a `Computer`. Links to `SoftwareCatalog`.
- **LicenseInventory:** Purchased licenses for software in the catalog. Supports encrypted license keys.
- **ComplianceReport:** Generated report entries indicating the compliance status of discovered software.

## Important Commands

### Development Setup
```bash
composer install
npm install
php artisan key:generate
php artisan migrate --seed
npm run dev
```

### Running the Application
```bash
# Start development server
php artisan serve

# Start queues (essential for processing scans)
php artisan queue:listen --queue=scans,default
# Or using Horizon (recommended)
php artisan horizon
```

### Testing & Quality
```bash
# Run tests (Pest)
php artisan test

# Format code (Pint)
./vendor/bin/pint
```

## Directory Structure Highlights
- `app/Http/Controllers/Api/`: Controllers handling agent registration and scan result submission.
- `app/Jobs/`: Asynchronous tasks like `ProcessScanResultJob` and `GenerateComplianceReportJob`.
- `app/Models/`: Core domain entities (Computer, SoftwareCatalog, etc.).
- `app/Services/`: Business logic for filtering software and managing the catalog (`SoftwareCatalogService`, `SoftwareFilterService`).
- `script/agent/`: Client-side scripts (e.g., `scanner.ps1`) used to gather software data from computers.
- `routes/api.php`: API endpoints for agents.
- `routes/web.php`: Admin panel routes.

## Development Conventions
- **Testing:** Always use Pest for new tests. Ensure high coverage for compliance logic.
- **Security:** License keys are encrypted by default in the database (`LicenseInventory` model).
- **Asynchronicity:** Scan processing MUST be handled via jobs to prevent API timeouts.
- **Localization:** Supports Indonesian (`id`) and English (`en`).

## Future Directions / TODOs
- Improve the `SoftwareFilterService` to better handle ambiguous software names.
- Enhance the dashboard with more real-time analytics using Livewire or similar.
- Implement more granular alerts for license expirations.
