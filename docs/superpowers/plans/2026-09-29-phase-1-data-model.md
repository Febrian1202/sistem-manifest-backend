# Phase 1 — Data Model & Database Migrations Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement the foundational multi-lab periodic monitoring and historical scan data model (`scan_sessions`, `scan_software_results`, `compliance_snapshots`, computer status, and report approval period date ranges) without disrupting existing features.

**Architecture:** Additive relational schema where `Computer` owns historical `ScanSession`s via nullable FK (`nullOnDelete` to preserve audit trails), each session aggregates `ScanSoftwareResult`s and `ComplianceSnapshot`s, while existing tables (`software_discoveries`, `compliance_reports`) remain untouched for zero-regression backward compatibility.

**Tech Stack:** Laravel 12.x, PHP 8.5, MySQL / SQLite (in-memory tests), Pest PHP 3.x, Spatie Activitylog.

**Spec:** `markdown/tasks/01-data-model.md` and `markdown/tasks/00-overview.md`

## Global Constraints

- Never break existing tests: 152 existing feature tests must continue to pass throughout.
- Follow existing Eloquent conventions: strict typing, `casts()` method or `$casts` property matching existing models, `$fillable` attributes, and Spatie Activitylog where appropriate.
- Foreign key preservation: deleting a `Computer` must NOT delete historical `scan_sessions` or `compliance_snapshots` (`nullOnDelete()`). Deleting a `ScanSession` cascades to its own child results and snapshots (`cascadeOnDelete()`).
- Format all modified/created PHP files with Pint before completion.

---

### Task 1: ScanSession Migration, Model, and Factory

**Files:**
- Create: `database/migrations/2026_09_29_000001_create_scan_sessions_table.php`
- Create: `app/Models/ScanSession.php`
- Create: `database/factories/ScanSessionFactory.php`
- Modify: `app/Models/Computer.php:60-79`
- Create: `tests/Feature/ScanSessionModelTest.php`

**Interfaces:**
- Consumes: `App\Models\Computer`
- Produces: `App\Models\ScanSession`, `Computer::scanSessions()`, `Computer::latestScanSession()`

- [ ] **Step 1: Write failing feature test for ScanSession**

Create `tests/Feature/ScanSessionModelTest.php`:
```php
<?php

use App\Models\Computer;
use App\Models\Laboratory;
use App\Models\ScanSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('can create a scan session linked to a computer', function () {
    $lab = Laboratory::factory()->create();
    $computer = Computer::factory()->create(['laboratory_id' => $lab->id]);

    $session = ScanSession::create([
        'computer_id' => $computer->id,
        'scan_uuid' => (string) Str::uuid(),
        'started_at' => now()->subMinutes(5),
        'completed_at' => now(),
        'status' => 'completed',
        'trigger' => 'scheduled',
        'software_count' => 15,
        'agent_version' => '1.0.0',
    ]);

    expect($session->computer->id)->toBe($computer->id)
        ->and($computer->scanSessions)->toHaveCount(1)
        ->and($computer->latestScanSession->id)->toBe($session->id);
});

test('deleting a computer preserves scan sessions with nullOnDelete', function () {
    $lab = Laboratory::factory()->create();
    $computer = Computer::factory()->create(['laboratory_id' => $lab->id]);

    $session = ScanSession::create([
        'computer_id' => $computer->id,
        'scan_uuid' => (string) Str::uuid(),
        'status' => 'completed',
        'trigger' => 'scheduled',
        'software_count' => 10,
    ]);

    $computer->delete();

    $session->refresh();
    expect($session->computer_id)->toBeNull();
});

test('scan session enforces unique scan_uuid', function () {
    $uuid = (string) Str::uuid();
    ScanSession::factory()->create(['scan_uuid' => $uuid]);

    expect(fn () => ScanSession::factory()->create(['scan_uuid' => $uuid]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ScanSessionModelTest`
Expected: FAIL (table and class do not exist yet)

- [ ] **Step 3: Create migration `database/migrations/2026_09_29_000001_create_scan_sessions_table.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('computer_id')->nullable()->constrained('computers')->nullOnDelete();
            $table->uuid('scan_uuid')->unique();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('status')->default('completed'); // pending, running, completed, failed, partial
            $table->string('trigger')->default('scheduled'); // scheduled, manual, on_demand
            $table->unsignedInteger('software_count')->default(0);
            $table->text('error_message')->nullable();
            $table->string('agent_version')->nullable();
            $table->timestamps();

            $table->index(['computer_id', 'status']);
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_sessions');
    }
};
```

- [ ] **Step 4: Create model `app/Models/ScanSession.php` and update `app/Models/Computer.php`**

`app/Models/ScanSession.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class ScanSession extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['computer_id', 'scan_uuid', 'status', 'trigger', 'software_count'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName) => "Scan session {$this->scan_uuid} telah di-{$eventName}");
    }

    protected $fillable = [
        'computer_id',
        'scan_uuid',
        'started_at',
        'completed_at',
        'status',
        'trigger',
        'software_count',
        'error_message',
        'agent_version',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'software_count' => 'integer',
    ];

    public function computer(): BelongsTo
    {
        return $this->belongsTo(Computer::class);
    }

    public function softwareResults(): HasMany
    {
        return $this->hasMany(ScanSoftwareResult::class);
    }

    public function complianceSnapshots(): HasMany
    {
        return $this->hasMany(ComplianceSnapshot::class);
    }
}
```

Update `app/Models/Computer.php` by adding:
```php
    public function scanSessions()
    {
        return $this->hasMany(ScanSession::class);
    }

    public function latestScanSession()
    {
        return $this->hasOne(ScanSession::class)->latestOfMany();
    }
```

- [ ] **Step 5: Create factory `database/factories/ScanSessionFactory.php`**

```php
<?php

namespace Database\Factories;

use App\Models\Computer;
use App\Models\ScanSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ScanSessionFactory extends Factory
{
    protected $model = ScanSession::class;

    public function definition(): array
    {
        return [
            'computer_id' => Computer::factory(),
            'scan_uuid' => (string) Str::uuid(),
            'started_at' => now()->subMinutes(10),
            'completed_at' => now(),
            'status' => 'completed',
            'trigger' => 'scheduled',
            'software_count' => fake()->numberBetween(10, 40),
            'error_message' => null,
            'agent_version' => '1.0.0',
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => 'Agent scan encountered registry read timeout.',
            'software_count' => 0,
        ]);
    }
}
```

- [ ] **Step 6: Run tests and ensure PASS**

Run: `php artisan test --filter=ScanSessionModelTest`
Expected: PASS

---

### Task 2: ScanSoftwareResult Migration, Model, and Factory

**Files:**
- Create: `database/migrations/2026_09_29_000002_create_scan_software_results_table.php`
- Create: `app/Models/ScanSoftwareResult.php`
- Create: `database/factories/ScanSoftwareResultFactory.php`
- Create: `tests/Feature/ScanSoftwareResultModelTest.php`

**Interfaces:**
- Consumes: `App\Models\ScanSession`, `App\Models\SoftwareCatalog`
- Produces: `App\Models\ScanSoftwareResult`, `ScanSession::softwareResults()`

- [ ] **Step 1: Write failing test for ScanSoftwareResult**

Create `tests/Feature/ScanSoftwareResultModelTest.php`:
```php
<?php

use App\Models\ScanSession;
use App\Models\ScanSoftwareResult;
use App\Models\SoftwareCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create software result linked to a scan session and catalog', function () {
    $session = ScanSession::factory()->create();
    $catalog = SoftwareCatalog::create([
        'normalized_name' => 'Visual Studio Code',
        'category' => 'Freeware',
        'status' => 'Whitelist',
    ]);

    $result = ScanSoftwareResult::create([
        'scan_session_id' => $session->id,
        'catalog_id' => $catalog->id,
        'raw_name' => 'Microsoft Visual Studio Code (User)',
        'version' => '1.93.0',
        'vendor' => 'Microsoft Corporation',
        'install_date' => '2026-01-15',
    ]);

    expect($result->scanSession->id)->toBe($session->id)
        ->and($result->catalog->id)->toBe($catalog->id)
        ->and($session->softwareResults)->toHaveCount(1);
});

test('deleting scan session cascades to its software results', function () {
    $session = ScanSession::factory()->create();
    ScanSoftwareResult::factory()->count(3)->create(['scan_session_id' => $session->id]);

    expect(ScanSoftwareResult::count())->toBe(3);

    $session->delete();

    expect(ScanSoftwareResult::count())->toBe(0);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ScanSoftwareResultModelTest`
Expected: FAIL

- [ ] **Step 3: Create migration `database/migrations/2026_09_29_000002_create_scan_software_results_table.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_software_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_session_id')->constrained('scan_sessions')->cascadeOnDelete();
            $table->foreignId('catalog_id')->nullable()->constrained('software_catalogs')->nullOnDelete();
            $table->string('raw_name');
            $table->string('version')->nullable();
            $table->string('vendor')->nullable();
            $table->date('install_date')->nullable();
            $table->timestamps();

            $table->index('scan_session_id');
            $table->index('catalog_id');
            $table->index('raw_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_software_results');
    }
};
```

- [ ] **Step 4: Create model `app/Models/ScanSoftwareResult.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanSoftwareResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'scan_session_id',
        'catalog_id',
        'raw_name',
        'version',
        'vendor',
        'install_date',
    ];

    protected $casts = [
        'install_date' => 'date',
    ];

    public function scanSession(): BelongsTo
    {
        return $this->belongsTo(ScanSession::class);
    }

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(SoftwareCatalog::class, 'catalog_id');
    }
}
```

- [ ] **Step 5: Create factory `database/factories/ScanSoftwareResultFactory.php`**

```php
<?php

namespace Database\Factories;

use App\Models\ScanSession;
use App\Models\ScanSoftwareResult;
use App\Models\SoftwareCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScanSoftwareResultFactory extends Factory
{
    protected $model = ScanSoftwareResult::class;

    public function definition(): array
    {
        return [
            'scan_session_id' => ScanSession::factory(),
            'catalog_id' => null,
            'raw_name' => fake()->randomElement(['Google Chrome', 'VLC Media Player', 'Adobe Acrobat Reader', 'Git for Windows']),
            'version' => fake()->numerify('#.#.##'),
            'vendor' => fake()->company(),
            'install_date' => fake()->date(),
        ];
    }
}
```

- [ ] **Step 6: Run test and ensure PASS**

Run: `php artisan test --filter=ScanSoftwareResultModelTest`
Expected: PASS

---

### Task 3: ComplianceSnapshot Migration, Model, and Factory

**Files:**
- Create: `database/migrations/2026_09_29_000003_create_compliance_snapshots_table.php`
- Create: `app/Models/ComplianceSnapshot.php`
- Create: `database/factories/ComplianceSnapshotFactory.php`
- Create: `tests/Feature/ComplianceSnapshotModelTest.php`

**Interfaces:**
- Consumes: `App\Models\ScanSession`, `App\Models\Computer`, `App\Models\SoftwareCatalog`, `App\Models\LicenseInventory`
- Produces: `App\Models\ComplianceSnapshot`, `ScanSession::complianceSnapshots()`

- [ ] **Step 1: Write failing test for ComplianceSnapshot**

Create `tests/Feature/ComplianceSnapshotModelTest.php`:
```php
<?php

use App\Models\ComplianceSnapshot;
use App\Models\Computer;
use App\Models\Laboratory;
use App\Models\ScanSession;
use App\Models\SoftwareCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create a compliance snapshot associated with a scan session', function () {
    $lab = Laboratory::factory()->create();
    $computer = Computer::factory()->create(['laboratory_id' => $lab->id]);
    $session = ScanSession::factory()->create(['computer_id' => $computer->id]);
    $catalog = SoftwareCatalog::create([
        'normalized_name' => 'AutoCAD 2024',
        'category' => 'Commercial',
        'status' => 'Unreviewed',
    ]);

    $snapshot = ComplianceSnapshot::create([
        'scan_session_id' => $session->id,
        'computer_id' => $computer->id,
        'software_catalog_id' => $catalog->id,
        'software_name' => 'AutoCAD 2024',
        'software_version' => '24.0',
        'status' => 'Tidak Berlisensi',
        'keterangan' => 'Belum ada lisensi terpasang.',
        'scanned_at' => now(),
    ]);

    expect($snapshot->scanSession->id)->toBe($session->id)
        ->and($snapshot->computer->id)->toBe($computer->id)
        ->and($snapshot->softwareCatalog->id)->toBe($catalog->id)
        ->and($session->complianceSnapshots)->toHaveCount(1);
});

test('deleting a computer retains compliance snapshots with null computer_id', function () {
    $lab = Laboratory::factory()->create();
    $computer = Computer::factory()->create(['laboratory_id' => $lab->id]);
    $session = ScanSession::factory()->create(['computer_id' => $computer->id]);

    $snapshot = ComplianceSnapshot::factory()->create([
        'scan_session_id' => $session->id,
        'computer_id' => $computer->id,
        'status' => 'Berlisensi',
        'scanned_at' => now(),
    ]);

    $computer->delete();

    $snapshot->refresh();
    expect($snapshot->computer_id)->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ComplianceSnapshotModelTest`
Expected: FAIL

- [ ] **Step 3: Create migration `database/migrations/2026_09_29_000003_create_compliance_snapshots_table.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_session_id')->constrained('scan_sessions')->cascadeOnDelete();
            $table->foreignId('computer_id')->nullable()->constrained('computers')->nullOnDelete();
            $table->foreignId('software_catalog_id')->nullable()->constrained('software_catalogs')->nullOnDelete();
            $table->string('software_name');
            $table->string('software_version')->nullable();
            $table->string('status'); // Berlisensi, Tidak Berlisensi, Grace Period, Perlu Ditinjau
            $table->text('keterangan')->nullable();
            $table->foreignId('license_inventory_id')->nullable()->constrained('license_inventories')->nullOnDelete();
            $table->timestamp('detected_at')->nullable();
            $table->timestamp('scanned_at');
            $table->timestamps();

            $table->index('scan_session_id');
            $table->index(['computer_id', 'status']);
            $table->index('scanned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_snapshots');
    }
};
```

- [ ] **Step 4: Create model `app/Models/ComplianceSnapshot.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'scan_session_id',
        'computer_id',
        'software_catalog_id',
        'software_name',
        'software_version',
        'status',
        'keterangan',
        'license_inventory_id',
        'detected_at',
        'scanned_at',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'scanned_at' => 'datetime',
    ];

    public function scanSession(): BelongsTo
    {
        return $this->belongsTo(ScanSession::class);
    }

    public function computer(): BelongsTo
    {
        return $this->belongsTo(Computer::class);
    }

    public function softwareCatalog(): BelongsTo
    {
        return $this->belongsTo(SoftwareCatalog::class, 'software_catalog_id');
    }

    public function licenseInventory(): BelongsTo
    {
        return $this->belongsTo(LicenseInventory::class, 'license_inventory_id');
    }
}
```

- [ ] **Step 5: Create factory `database/factories/ComplianceSnapshotFactory.php`**

```php
<?php

namespace Database\Factories;

use App\Models\ComplianceSnapshot;
use App\Models\Computer;
use App\Models\ScanSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComplianceSnapshotFactory extends Factory
{
    protected $model = ComplianceSnapshot::class;

    public function definition(): array
    {
        return [
            'scan_session_id' => ScanSession::factory(),
            'computer_id' => Computer::factory(),
            'software_catalog_id' => null,
            'software_name' => fake()->randomElement(['Microsoft Office 2021', 'AutoCAD 2024', 'Matlab R2023b', 'SPSS Statistics 29']),
            'software_version' => fake()->numerify('#.#'),
            'status' => fake()->randomElement(['Berlisensi', 'Tidak Berlisensi', 'Grace Period', 'Perlu Ditinjau']),
            'keterangan' => fake()->optional()->sentence(),
            'license_inventory_id' => null,
            'detected_at' => now()->subDays(30),
            'scanned_at' => now(),
        ];
    }
}
```

- [ ] **Step 6: Run test and ensure PASS**

Run: `php artisan test --filter=ComplianceSnapshotModelTest`
Expected: PASS

---

### Task 4: Add `status` column to `computers` Table

**Files:**
- Create: `database/migrations/2026_09_29_000004_add_status_to_computers_table.php`
- Modify: `app/Models/Computer.php`
- Create: `tests/Feature/ComputerStatusTest.php`

**Interfaces:**
- Consumes: `App\Models\Computer`
- Produces: `Computer::scopeActive()`, `Computer->status` attribute

- [ ] **Step 1: Write failing test for computer status and scope**

Create `tests/Feature/ComputerStatusTest.php`:
```php
<?php

use App\Models\Computer;
use App\Models\Laboratory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('new computer has default active status', function () {
    $lab = Laboratory::factory()->create();
    $computer = Computer::factory()->create(['laboratory_id' => $lab->id]);

    expect($computer->status)->toBe('active');
});

test('scopeActive filters only active computers', function () {
    $lab = Laboratory::factory()->create();
    $active = Computer::factory()->create(['laboratory_id' => $lab->id, 'status' => 'active']);
    $inactive = Computer::factory()->create(['laboratory_id' => $lab->id, 'status' => 'inactive']);
    $retired = Computer::factory()->create(['laboratory_id' => $lab->id, 'status' => 'retired']);

    $activeComputers = Computer::active()->get();

    expect($activeComputers->pluck('id'))->toContain($active->id)
        ->and($activeComputers->pluck('id'))->not->toContain($inactive->id)
        ->and($activeComputers->pluck('id'))->not->toContain($retired->id);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ComputerStatusTest`
Expected: FAIL

- [ ] **Step 3: Create migration `database/migrations/2026_09_29_000004_add_status_to_computers_table.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('computers', function (Blueprint $table) {
            $table->string('status')->default('active')->after('laboratory_id'); // active, inactive, maintenance, retired
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('computers', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
```

- [ ] **Step 4: Update `app/Models/Computer.php` with fillable and scopeActive**

Add `'status'` to `$fillable`:
```php
    protected $fillable = [
        'hostname',
        'os_name',
        'os_version',
        'os_architecture',
        'os_license_status',
        'os_partial_key',
        'processor',
        'ram_gb',
        'disk_total_gb',
        'disk_free_gb',
        'ip_address',
        'mac_address',
        'serial_number',
        'manufacturer',
        'model',
        'location',
        'laboratory_id',
        'status',
        'last_seen_at',
        'scan_requested',
    ];
```

Add scope to `Computer`:
```php
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
```

- [ ] **Step 5: Run test and ensure PASS**

Run: `php artisan test --filter=ComputerStatusTest`
Expected: PASS

---

### Task 5: Add `period_start` and `period_end` to `report_approvals` Table

**Files:**
- Create: `database/migrations/2026_09_29_000005_add_period_dates_to_report_approvals_table.php`
- Modify: `app/Models/ReportApproval.php`
- Create: `tests/Feature/ReportApprovalPeriodDatesTest.php`

**Interfaces:**
- Consumes: `App\Models\ReportApproval`
- Produces: `ReportApproval->period_start`, `ReportApproval->period_end`

- [ ] **Step 1: Write failing test for period_start and period_end**

Create `tests/Feature/ReportApprovalPeriodDatesTest.php`:
```php
<?php

use App\Models\Laboratory;
use App\Models\ReportApproval;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('report approval supports period_start and period_end while keeping period string', function () {
    $lab = Laboratory::factory()->create();

    $report = ReportApproval::create([
        'laboratory_id' => $lab->id,
        'report_type' => 'compliance',
        'period' => '2026-09',
        'period_start' => '2026-09-01',
        'period_end' => '2026-09-30',
        'status' => 'pending',
    ]);

    expect($report->period)->toBe('2026-09')
        ->and($report->period_start->format('Y-m-d'))->toBe('2026-09-01')
        ->and($report->period_end->format('Y-m-d'))->toBe('2026-09-30');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ReportApprovalPeriodDatesTest`
Expected: FAIL

- [ ] **Step 3: Create migration `database/migrations/2026_09_29_000005_add_period_dates_to_report_approvals_table.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_approvals', function (Blueprint $table) {
            $table->date('period_start')->nullable()->after('period');
            $table->date('period_end')->nullable()->after('period_start');

            $table->index(['period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::table('report_approvals', function (Blueprint $table) {
            $table->dropIndex(['period_start', 'period_end']);
            $table->dropColumn(['period_start', 'period_end']);
        });
    }
};
```

- [ ] **Step 4: Update `app/Models/ReportApproval.php`**

Update `$fillable` and `$casts`:
```php
    protected $fillable = [
        'laboratory_id',
        'reviewed_by',
        'report_type',
        'period',
        'period_start',
        'period_end',
        'status',
        'notes',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'period_start' => 'date',
        'period_end' => 'date',
    ];
```

- [ ] **Step 5: Run test and ensure PASS**

Run: `php artisan test --filter=ReportApprovalPeriodDatesTest`
Expected: PASS

---

### Task 6: Seeder for Historical Scan Data

**Files:**
- Create: `database/seeders/ScanSessionSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

**Interfaces:**
- Consumes: `Computer`, `SoftwareCatalog`, `LicenseInventory`
- Produces: `ScanSessionSeeder` generating sample 3-month periodic scans

- [ ] **Step 1: Create `database/seeders/ScanSessionSeeder.php`**

Create seeder with realistic historical data:
```php
<?php

namespace Database\Seeders;

use App\Models\ComplianceSnapshot;
use App\Models\Computer;
use App\Models\ScanSession;
use App\Models\ScanSoftwareResult;
use App\Models\SoftwareCatalog;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ScanSessionSeeder extends Seeder
{
    public function run(): void
    {
        $computers = Computer::all();
        if ($computers->isEmpty()) {
            return;
        }

        $catalogs = SoftwareCatalog::all();

        // Generate 3 periodic scan cycles (e.g. 60 days ago, 30 days ago, and recently)
        $periods = [
            now()->subDays(60),
            now()->subDays(30),
            now()->subDays(2),
        ];

        foreach ($computers as $computer) {
            foreach ($periods as $index => $scanDate) {
                $session = ScanSession::create([
                    'computer_id' => $computer->id,
                    'scan_uuid' => (string) Str::uuid(),
                    'started_at' => (clone $scanDate)->subMinutes(5),
                    'completed_at' => $scanDate,
                    'status' => 'completed',
                    'trigger' => 'scheduled',
                    'software_count' => rand(8, 15),
                    'agent_version' => '1.0.0',
                ]);

                // Sample software findings
                $sampleSoftwares = [
                    ['raw_name' => 'Visual Studio Code', 'vendor' => 'Microsoft', 'version' => '1.9' . $index, 'category' => 'Freeware', 'status' => 'Berlisensi'],
                    ['raw_name' => 'Google Chrome', 'vendor' => 'Google LLC', 'version' => '120.0.' . $index, 'category' => 'Freeware', 'status' => 'Berlisensi'],
                    ['raw_name' => 'AutoCAD 2024', 'vendor' => 'Autodesk', 'version' => '24.' . $index, 'category' => 'Commercial', 'status' => 'Tidak Berlisensi'],
                    ['raw_name' => 'WinRAR 6.24', 'vendor' => 'win.rar GmbH', 'version' => '6.24', 'category' => 'Shareware', 'status' => 'Perlu Ditinjau'],
                ];

                foreach ($sampleSoftwares as $item) {
                    $cat = $catalogs->firstWhere('normalized_name', $item['raw_name']);

                    ScanSoftwareResult::create([
                        'scan_session_id' => $session->id,
                        'catalog_id' => $cat?->id,
                        'raw_name' => $item['raw_name'],
                        'version' => $item['version'],
                        'vendor' => $item['vendor'],
                        'install_date' => Carbon::parse($scanDate)->subMonths(2)->toDateString(),
                    ]);

                    ComplianceSnapshot::create([
                        'scan_session_id' => $session->id,
                        'computer_id' => $computer->id,
                        'software_catalog_id' => $cat?->id,
                        'software_name' => $item['raw_name'],
                        'software_version' => $item['version'],
                        'status' => $item['status'],
                        'keterangan' => 'Audit status snapshot',
                        'detected_at' => Carbon::parse($scanDate)->subMonths(2),
                        'scanned_at' => $scanDate,
                    ]);
                }
            }
        }
    }
}
```

- [ ] **Step 2: Verify `ScanSessionSeeder` can be executed cleanly**

Run: `php artisan db:seed --class=ScanSessionSeeder`
Expected: SUCCESS

---

### Task 7: Full Test Suite Verification and Pint Code Formatting

**Files:**
- All touched PHP files

- [ ] **Step 1: Run complete test suite**

Run: `php artisan test --compact`
Expected: ALL tests PASS (152 existing + new tests = 157+ passed)

- [ ] **Step 2: Run Pint code formatter**

Run: `vendor/bin/pint --dirty --format agent`
Expected: Zero unformatted files
