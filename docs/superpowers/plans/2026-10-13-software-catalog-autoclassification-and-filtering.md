# Software Catalog Auto-classification and Junk Filtering Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Auto-classify scanned software into Commercial, Freeware, and OpenSource without manual review, filter out system/SDK junk, and provide an Artisan maintenance command for existing and future deployments.

**Architecture:** Extend `SoftwareFilterService` with junk/uninstaller/sub-component rules so future scans ignore noise. Expand `config/software_whitelist.php` and `SoftwareCatalogService` to auto-tag recognized applications into Commercial, Freeware, and OpenSource with `Whitelist` status. Provide an idempotent `php artisan software:reclassify` command to prune junk and re-classify existing database entries.

**Tech Stack:** Laravel 12, PHP 8.4+, Pest PHP, Artisan Console, SQLite in-memory for tests.

## Global Constraints
- Do not remove or alter existing valid software catalog entries that already have active licenses (`license_inventories`).
- Follow established Laravel conventions: constructor promotion, strict typing, Pest tests, Pint styling.
- All code and tests must pass `vendor/bin/pint --format agent` and `php artisan test --compact`.

## Review Focus
- Uninstaller entries (e.g. `EPSON L120 Series Printer Uninstall`) must be detected as junk and filtered out.
- Suite sub-modules (e.g. `CorelDRAW Graphics Suite 2021 - Capture (x64)`) must be filtered as junk, while main suites (e.g. `CorelDRAW Graphics Suite 2021`) remain intact.
- Commercial software (e.g. `Adobe Premiere Pro`, `Microsoft Office`, `AutoCAD`, `TablePlus`) must be auto-assigned `Commercial` category and `Whitelist` status.
- OpenSource/Freeware tools (e.g. `Android Studio`, `Arduino IDE`, `Dev-C++`, `XAMPP`) must be auto-assigned `OpenSource`/`Freeware` and `Whitelist` status.
- Reclassify command must safely delete junk without breaking foreign keys, and support `--dry-run`.

---

### Task 1: Expand SoftwareFilterService with Junk and Sub-component Filters

**Files:**
- Modify: `app/Services/SoftwareFilterService.php`
- Modify: `tests/Unit/SoftwareFilterServiceTest.php` (or create if not present)

**Interfaces:**
- Consumes: `SoftwareFilterService::filter(array $softwareList): FilterResult`
- Produces: Enhanced `filter` method recognizing SDKs, testing kits, uninstallers, and sub-components.

- [ ] **Step 1: Write tests for new junk keywords and sub-components**

Add test cases in `tests/Unit/SoftwareFilterServiceTest.php` asserting that items like:
- `EPSON L120 Series Printer Uninstall`
- `Application Verifier x64 External Package (DesktopEditions)`
- `CorelDRAW Graphics Suite 2021 - Capture (x64)`
- `Autodesk CER`
- `VS JIT Debugger`
- `WinRT Intellisense Desktop - en-us`
- `WPTx64 (DesktopEditions)`
are classified as `junk`, while `CorelDRAW Graphics Suite 2021` and `Adobe Premiere Pro 2019` remain `clean`.

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/SoftwareFilterServiceTest.php`
Expected: FAIL on newly asserted junk items.

- [ ] **Step 3: Update `SoftwareFilterService.php`**

Add missing junk keywords and regex patterns in `JUNK_KEYWORDS` and `JUNK_PATTERNS`:
- Keywords: `'Uninstall'`, `'Application Verifier'`, `'Autodesk CER'`, `'Autodesk Access'`, `'Autodesk App Manager'`, `'Autodesk Featured Apps'`, `'Autodesk Save to Web'`, `'AutoCAD Open in'`, `'EShare Audio Card'`, `'ESystemRemoteService'`, `'VS JIT Debugger'`, `'VS Script Debugging'`, `'Visual Studio Tools for Applications'`, `'Universal CRT'`, `'WinRT Intellisense'`, `'Windows Software Development Kit'`, `'Windows App Certification Kit'`, `'WinAppDeploy'`, `'MSI Development Tools'`, `'WPTx64'`, `'Compatibility Fix Database'`, `'icecap_'`, `'Handwriting.Main'`, `'Scan OCR Component'`, `'Web Deploy'`.
- Pattern for sub-modules: `/^CorelDRAW Graphics Suite.* - (Capture|Common|Custom Data|Discovery|Draw|EN|Filters|Font Manager|IPM|Models|PHOTO-PAINT|VBA|Workspaces|Writing Tools)/i`.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Unit/SoftwareFilterServiceTest.php`
Expected: PASS

- [ ] **Step 5: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

---

### Task 2: Configure Auto-Classification Rules for Commercial, Freeware, and OpenSource

**Files:**
- Modify: `config/software_whitelist.php`
- Modify: `app/Services/SoftwareCatalogService.php`
- Modify: `tests/Unit/SoftwareCatalogServiceTest.php` (or Feature test)

**Interfaces:**
- Consumes: `SoftwareCatalogService::getAutoProcessedData(string $softwareName): array`
- Produces: Correct `['category' => ..., 'status' => 'Whitelist']` for scanned applications.

- [ ] **Step 1: Write test for auto-processing Commercial and Freeware/OpenSource software**

Add tests verifying:
- `Adobe Premiere Pro 2019` -> `['category' => 'Commercial', 'status' => 'Whitelist']`
- `Microsoft Office Professional Plus 2019 - en-us` -> `['category' => 'Commercial', 'status' => 'Whitelist']`
- `Android Studio` -> `['category' => 'OpenSource', 'status' => 'Whitelist']`
- `CapCut` -> `['category' => 'Freeware', 'status' => 'Whitelist']`
- `XAMPP` -> `['category' => 'OpenSource', 'status' => 'Whitelist']`
- `SmartPLS 4 4.1.1.7` -> `['category' => 'Commercial', 'status' => 'Whitelist']`

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/SoftwareCatalogServiceTest.php`
Expected: FAIL on unmapped software (defaults to Unreviewed/Freeware).

- [ ] **Step 3: Update `config/software_whitelist.php` and `SoftwareCatalogService.php`**

Populate `config/software_whitelist.php` with verified rules for:
- Commercial: Adobe Premiere Pro, CorelDRAW Graphics Suite, Microsoft Office, Microsoft 365, Microsoft Visio, Visual Studio Professional, AutoCAD, TablePlus, SmartPLS, WinRAR, PowerISO.
- Freeware: Antigravity, Bizagi Modeler, BlueStacks, CapCut, Copilot, Epson, Google Drive, Growtopia, Kasir Pintar, Mendeley, Microsoft Edge, Microsoft Flight Simulator, Microsoft OneDrive, Microsoft OneNote, MicrosoftTeams, MSTeams, Notion, OpenVPN, osu!, ScreenShare, Steam, Ubisoft Connect, WebAdvisor by McAfee, Windows Subsystem for Linux, Zoom Workplace.
- OpenSource: Android Studio, Arduino IDE, DB Browser for SQLite, Dev-C++, FileZilla, HeidiSQL, Laragon, PostgreSQL, Python Launcher, Rainmeter, Redis on Windows, XAMPP.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Unit/SoftwareCatalogServiceTest.php`
Expected: PASS

- [ ] **Step 5: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

---

### Task 3: Create Artisan Command `software:reclassify`

**Files:**
- Create: `app/Console/Commands/ReclassifySoftwareCatalog.php`
- Create: `tests/Feature/ReclassifySoftwareCatalogCommandTest.php`

**Interfaces:**
- Consumes: `SoftwareFilterService`, `SoftwareCatalogService`, `SoftwareCatalog`, `SoftwareDiscovery`
- Produces: CLI command `php artisan software:reclassify {--dry-run} {--force}`

- [ ] **Step 1: Write Feature test for `software:reclassify`**

Test scenarios:
- Deletes junk catalog records and associated discoveries (unless tied to a license).
- Updates Unreviewed catalogs to Whitelist with matching category when rules match.
- Leaves already-reviewed or unmatched entries untouched unless `--force` is used.
- Simulates without DB modifications when `--dry-run` is passed.
- Clears dashboard cache tags after execution.

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/ReclassifySoftwareCatalogCommandTest.php`
Expected: FAIL (command does not exist).

- [ ] **Step 3: Implement `ReclassifySoftwareCatalog.php`**

Build the command with:
- Signature: `software:reclassify {--dry-run : Simulate changes without writing to database} {--force : Reclassify even if status is not Unreviewed}`
- Junk pruning logic with safety check: `$catalog->licenses()->exists()`.
- Auto-reclassification using `SoftwareCatalogService::getAutoProcessedData`.
- Call `Cache::forget` for dashboard and compliance statistics.
- Descriptive output using `$this->info`, `$this->warn`, and `$this->table`.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/ReclassifySoftwareCatalogCommandTest.php`
Expected: PASS

- [ ] **Step 5: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

---

### Task 4: Full Suite Verification and Golden Path Check

**Files:**
- None (verification)

- [ ] **Step 1: Run Pint code formatter**

Run: `vendor/bin/pint --format agent`

- [ ] **Step 2: Run entire test suite**

Run: `php artisan test --compact`
Expected: All tests pass.
