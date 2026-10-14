# Akses Lisensi Alokasi Fakultas untuk Kepala Lab & Staff Lab Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Memberikan akses read-only bagi Kepala Lab dan Staf Lab untuk melihat daftar lisensi yang dialokasikan ke fakultas mereka dan membuka/menyalin license key secara aman dengan audit log.

**Architecture:** Memanfaatkan model `LicenseAllocation` yang berelasi ke `Faculty` untuk membatasi lisensi yang tampil sesuai fakultas pengguna (`auth()->user()->faculty_id ?? auth()->user()->laboratory?->faculty_id`). Endpoint pengambilan key dilindungi validasi kepemilikan alokasi aktif, throttling (10 req/min), dan pencatatan riwayat via Spatie Activity Log. Frontend menggunakan Blade, Tailwind CSS 4, dan Alpine.js dengan mekanisme masking default, reveal on-demand bertimer 30 detik, serta satu-klik salin ke clipboard.

**Tech Stack:** Laravel 12 (PHP 8.4/8.5), Pest PHP, Spatie Permission, Spatie Activity Log, Blade, Tailwind CSS 4, Alpine.js.

**Spec:** `docs/superpowers/specs/2026-10-14-lab-faculty-license-view-design.md`

## Global Constraints

- PHP 8.4+ strict typing and return type declarations on all methods.
- Role checks via Spatie Permission `role:kepala_lab|staff_lab` middleware and controller verification.
- `LicenseInventory.license_key` is encrypted at rest and must NEVER be rendered plain-text in initial Blade responses.
- All key access must trigger a Spatie Activity Log entry (`Melihat license key alokasi fakultas`).
- Rate limiting of 10 requests per minute on the key retrieval endpoint (`throttle:10,1`).
- Pint formatting must be executed before final commit (`vendor/bin/pint --dirty --format agent`).

## Review Focus

1. **User Without Assigned Lab or Faculty:** Pengguna dengan role `kepala_lab` atau `staff_lab` yang `faculty_id` dan `laboratory_id`-nya null harus menerima respons view yang ramah/informatif tanpa exception 500.
2. **Cross-Faculty Key Access Attempt:** Upaya memanggil `POST /lab/licenses/{license}/key` untuk lisensi yang tidak dialokasikan ke fakultas pengguna harus mengembalikan HTTP 403 Forbidden.
3. **Inactive or Expired Allocation:** Jika lisensi hanya memiliki alokasi berstatus selain `active` (misal: `revoked` / `expired`), lisensi tidak boleh muncul di daftar dan akses key harus ditolak.
4. **Key Decryption Integrity:** Key yang didekripsi harus berupa string utuh yang cocok dengan `LicenseInventory.license_key`, bukan masked string.
5. **Activity Log Causer Tracking:** Entri `activity_log` harus mencatat causer sebagai user yang sedang login, mencatat properti nama software dan fakultas, dan tidak pernah menyimpan teks mentah key di kolom properties.

---

### Task 1: Controller, Routing & Backend Security

**Files:**
- Create: `app/Http/Controllers/LabLicenseController.php`
- Modify: `routes/web.php:143-148`
- Test: `tests/Feature/LabLicenseAccessTest.php`

**Interfaces:**
- Consumes: `App\Models\LicenseAllocation`, `App\Models\LicenseInventory`, `App\Models\User`, `App\Models\Faculty`
- Produces: 
  - Route `lab.licenses.index` (`GET /lab/licenses`)
  - Route `lab.licenses.key` (`POST /lab/licenses/{license}/key`)
  - `LabLicenseController::index(Request $request): \Illuminate\View\View`
  - `LabLicenseController::getKey(Request $request, LicenseInventory $license): \Illuminate\Http\JsonResponse`

- [ ] **Step 1: Write the failing tests for backend authorization, listing, and key retrieval**

Create `tests/Feature/LabLicenseAccessTest.php`:
```php
<?php

use App\Models\Faculty;
use App\Models\Laboratory;
use App\Models\LicenseAllocation;
use App\Models\LicenseInventory;
use App\Models\SoftwareCatalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'kepala_lab']);
    Role::firstOrCreate(['name' => 'staff_lab']);
    Role::firstOrCreate(['name' => 'pimpinan']);
});

test('unauthenticated user cannot access lab licenses', function () {
    $this->get(route('lab.licenses.index'))
        ->assertRedirect(route('login'));
});

test('admin or pimpinan cannot access lab licenses route directly', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('lab.licenses.index'))
        ->assertForbidden();
});

test('kepala_lab and staff_lab can view licenses allocated to their faculty', function () {
    $facultyA = Faculty::factory()->create(['name' => 'Fakultas Teknik']);
    $labA = Laboratory::factory()->create(['faculty_id' => $facultyA->id]);

    $facultyB = Faculty::factory()->create(['name' => 'Fakultas Pertanian']);

    $catalogA = SoftwareCatalog::factory()->create(['normalized_name' => 'Windows 11 Pro']);
    $licenseA = LicenseInventory::factory()->create([
        'catalog_id' => $catalogA->id,
        'license_key' => 'AAAA-BBBB-CCCC-DDDD',
    ]);

    LicenseAllocation::factory()->create([
        'license_inventory_id' => $licenseA->id,
        'faculty_id' => $facultyA->id,
        'allocated_quota' => 30,
        'status' => 'active',
    ]);

    $catalogB = SoftwareCatalog::factory()->create(['normalized_name' => 'ArcGIS Pro']);
    $licenseB = LicenseInventory::factory()->create([
        'catalog_id' => $catalogB->id,
        'license_key' => 'XXXX-YYYY-ZZZZ-WWWW',
    ]);

    LicenseAllocation::factory()->create([
        'license_inventory_id' => $licenseB->id,
        'faculty_id' => $facultyB->id,
        'allocated_quota' => 10,
        'status' => 'active',
    ]);

    $kepalaLab = User::factory()->create(['laboratory_id' => $labA->id]);
    $kepalaLab->assignRole('kepala_lab');

    $response = $this->actingAs($kepalaLab)->get(route('lab.licenses.index'));

    $response->assertOk();
    $response->assertSee('Windows 11 Pro');
    $response->assertDontSee('ArcGIS Pro');
});

test('user without laboratory or faculty sees graceful empty state', function () {
    $staff = User::factory()->create(['laboratory_id' => null, 'faculty_id' => null]);
    $staff->assignRole('staff_lab');

    $response = $this->actingAs($staff)->get(route('lab.licenses.index'));

    $response->assertOk();
    $response->assertSee('belum ditugaskan');
});

test('kepala_lab can retrieve real license key and it is logged to activity log', function () {
    $faculty = Faculty::factory()->create(['name' => 'Fakultas Teknik']);
    $lab = Laboratory::factory()->create(['faculty_id' => $faculty->id]);
    $catalog = SoftwareCatalog::factory()->create(['normalized_name' => 'Windows 11 Pro']);
    $license = LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'license_key' => 'AAAA-BBBB-CCCC-DDDD',
    ]);

    LicenseAllocation::factory()->create([
        'license_inventory_id' => $license->id,
        'faculty_id' => $faculty->id,
        'allocated_quota' => 30,
        'status' => 'active',
    ]);

    $kepalaLab = User::factory()->create(['laboratory_id' => $lab->id]);
    $kepalaLab->assignRole('kepala_lab');

    $response = $this->actingAs($kepalaLab)
        ->postJson(route('lab.licenses.key', $license->id));

    $response->assertOk()
        ->assertJson(['key' => 'AAAA-BBBB-CCCC-DDDD']);

    $this->assertDatabaseHas('activity_log', [
        'causer_id' => $kepalaLab->id,
        'subject_id' => $license->id,
        'description' => 'Melihat license key alokasi fakultas',
    ]);
});

test('user cannot retrieve license key belonging to another faculty', function () {
    $facultyA = Faculty::factory()->create(['name' => 'Fakultas Teknik']);
    $labA = Laboratory::factory()->create(['faculty_id' => $facultyA->id]);

    $facultyB = Faculty::factory()->create(['name' => 'Fakultas Pertanian']);
    $labB = Laboratory::factory()->create(['faculty_id' => $facultyB->id]);

    $catalog = SoftwareCatalog::factory()->create(['normalized_name' => 'AutoCAD 2024']);
    $license = LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'license_key' => 'AUTOCAD-KEY-1234',
    ]);

    LicenseAllocation::factory()->create([
        'license_inventory_id' => $license->id,
        'faculty_id' => $facultyB->id,
        'allocated_quota' => 15,
        'status' => 'active',
    ]);

    $staffLabA = User::factory()->create(['laboratory_id' => $labA->id]);
    $staffLabA->assignRole('staff_lab');

    $response = $this->actingAs($staffLabA)
        ->postJson(route('lab.licenses.key', $license->id));

    $response->assertForbidden();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/LabLicenseAccessTest.php`
Expected: FAIL (Route [lab.licenses.index] not defined)

- [ ] **Step 3: Register routes in `routes/web.php` and implement `LabLicenseController.php`**

In `routes/web.php`:
Add within `Route::middleware(['auth', 'role:kepala_lab|staff_lab'])->group(...)`:
```php
Route::get('/lab/licenses', [LabLicenseController::class, 'index'])->name('lab.licenses.index');
Route::post('/lab/licenses/{license}/key', [LabLicenseController::class, 'getKey'])
    ->name('lab.licenses.key')
    ->middleware('throttle:10,1');
```

In `app/Http/Controllers/LabLicenseController.php`:
Implement:
```php
namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\LicenseAllocation;
use App\Models\LicenseInventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LabLicenseController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $facultyId = $user->faculty_id ?? $user->laboratory?->faculty_id;
        $faculty = $facultyId ? Faculty::find($facultyId) : null;

        if (! $faculty) {
            return view('lab-licenses.index', [
                'faculty' => null,
                'allocations' => collect(),
            ]);
        }

        $query = LicenseAllocation::query()
            ->with(['licenseInventory.catalog'])
            ->where('faculty_id', $faculty->id)
            ->where('status', 'active');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('licenseInventory.catalog', function ($q) use ($search) {
                $q->where('normalized_name', 'like', "%{$search}%");
            });
        }

        $allocations = $query->paginate(12)->withQueryString();

        return view('lab-licenses.index', compact('faculty', 'allocations'));
    }

    public function getKey(Request $request, LicenseInventory $license): JsonResponse
    {
        $user = $request->user();
        $facultyId = $user->faculty_id ?? $user->laboratory?->faculty_id;

        if (! $facultyId) {
            abort(403, 'Akun Anda tidak terafiliasi dengan fakultas mana pun.');
        }

        $isAllocated = $license->allocations()
            ->where('faculty_id', $facultyId)
            ->where('status', 'active')
            ->exists();

        if (! $isAllocated) {
            abort(403, 'Lisensi ini tidak dialokasikan untuk fakultas Anda.');
        }

        activity()
            ->performedOn($license)
            ->causedBy($user)
            ->withProperties([
                'software' => $license->catalog->normalized_name ?? 'N/A',
                'faculty' => $user->faculty?->name ?? $user->laboratory?->faculty?->name ?? 'N/A',
                'role' => $user->getRoleNames()->first(),
            ])
            ->log('Melihat license key alokasi fakultas');

        return response()->json([
            'key' => $license->license_key,
        ]);
    }
}
```

- [ ] **Step 4: Create preliminary view or run tests**

Create placeholder `resources/views/lab-licenses/index.blade.php` if needed so `view()` resolves.
Run: `vendor/bin/pest tests/Feature/LabLicenseAccessTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/LabLicenseController.php routes/web.php tests/Feature/LabLicenseAccessTest.php
git commit -m "feat: add LabLicenseController and routes with scoped faculty license access"
```

---

### Task 2: Blade View & Alpine.js License Key Reveal Component

**Files:**
- Create: `resources/views/lab-licenses/index.blade.php`
- Modify: `resources/views/lab-licenses/index.blade.php`

**Interfaces:**
- Consumes: `$faculty`, `$allocations` passed from `LabLicenseController::index`
- Produces: Complete responsive UI with breadcrumbs, summary stats, search filter, table, and Alpine.js interactive key viewer.

- [ ] **Step 1: Write integration tests checking view elements**

In `tests/Feature/LabLicenseAccessTest.php`, add:
```php
test('lab licenses view renders search and allocations properly', function () {
    $faculty = Faculty::factory()->create(['name' => 'Fakultas Teknik']);
    $lab = Laboratory::factory()->create(['faculty_id' => $faculty->id]);
    $catalog = SoftwareCatalog::factory()->create(['normalized_name' => 'Microsoft Windows 11 Pro', 'category' => 'OS']);
    $license = LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'license_key' => 'VK7JG-NPHTM-C97JM-9MPGT',
        'expiry_date' => now()->addYear(),
    ]);

    LicenseAllocation::factory()->create([
        'license_inventory_id' => $license->id,
        'faculty_id' => $faculty->id,
        'allocated_quota' => 50,
        'status' => 'active',
        'notes' => 'Gunakan key untuk PC Lab Jaringan',
    ]);

    $staff = User::factory()->create(['laboratory_id' => $lab->id]);
    $staff->assignRole('staff_lab');

    $response = $this->actingAs($staff)->get(route('lab.licenses.index'));

    $response->assertOk()
        ->assertSee('Fakultas Teknik')
        ->assertSee('Microsoft Windows 11 Pro')
        ->assertSee('50')
        ->assertSee('Gunakan key untuk PC Lab Jaringan')
        ->assertSee($license->masked_license_key)
        ->assertDontSee('VK7JG-NPHTM-C97JM-9MPGT'); // Plain key must not exist in HTML
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/LabLicenseAccessTest.php --filter=renders`
Expected: FAIL (missing markup or partial placeholder)

- [ ] **Step 3: Implement full Blade template `resources/views/lab-licenses/index.blade.php`**

Implement `resources/views/lab-licenses/index.blade.php`:
- `<x-layout.app>` wrapper with `$breadcrumbs`.
- Unassigned state alert when `$faculty === null`.
- Search form with query string preservation.
- Table with columns: Software & Versi, Kategori, Alokasi Fakultas, Masa Berlaku, Product Key (dengan komponen Alpine `x-data="{ showKey: false, realKey: '', loading: false, copied: false, revealKey() {...}, copyKey() {...} }`), Catatan.
- Follow Tailwind CSS 4 styling and color tokens (`bg-card`, `border-border`, `text-muted-foreground`, etc.) consistent with `resources/views/lab-inventory/index.blade.php`.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/LabLicenseAccessTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add resources/views/lab-licenses/index.blade.php tests/Feature/LabLicenseAccessTest.php
git commit -m "feat: implement lab-licenses index Blade view with Alpine.js key reveal"
```

---

### Task 3: Sidebar Navigation & Role Access Polish

**Files:**
- Modify: `resources/views/components/layout/side-bar.blade.php:84-106`
- Modify: `tests/Feature/LabLicenseAccessTest.php`

**Interfaces:**
- Consumes: `route('lab.licenses.index')`
- Produces: Navigation item in sidebar for `kepala_lab` and `staff_lab`

- [ ] **Step 1: Write test for sidebar link visibility**

In `tests/Feature/LabLicenseAccessTest.php`:
```php
test('sidebar shows Lisensi Fakultas link for kepala_lab and staff_lab but not for pimpinan', function () {
    $faculty = Faculty::factory()->create();
    $lab = Laboratory::factory()->create(['faculty_id' => $faculty->id]);

    $kepalaLab = User::factory()->create(['laboratory_id' => $lab->id]);
    $kepalaLab->assignRole('kepala_lab');

    $this->actingAs($kepalaLab)
        ->get(route('dashboard'))
        ->assertSee(route('lab.licenses.index'))
        ->assertSee('Lisensi Fakultas');

    $staffLab = User::factory()->create(['laboratory_id' => $lab->id]);
    $staffLab->assignRole('staff_lab');

    $this->actingAs($staffLab)
        ->get(route('dashboard'))
        ->assertSee(route('lab.licenses.index'))
        ->assertSee('Lisensi Fakultas');

    $pimpinan = User::factory()->create();
    $pimpinan->assignRole('pimpinan');

    $this->actingAs($pimpinan)
        ->get(route('dashboard'))
        ->assertDontSee(route('lab.licenses.index'));
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/LabLicenseAccessTest.php --filter=sidebar`
Expected: FAIL (sidebar does not contain `route('lab.licenses.index')`)

- [ ] **Step 3: Update `resources/views/components/layout/side-bar.blade.php`**

Under `@role('kepala_lab')`:
```blade
<x-layout.nav-item href="{{ route('lab.licenses.index') }}" icon="fa-key" label="Lisensi Fakultas" :active="request()->is('lab/licenses*')" />
```
Under `@role('staff_lab')`:
```blade
<x-layout.nav-item href="{{ route('lab.licenses.index') }}" icon="fa-key" label="Lisensi Fakultas" :active="request()->is('lab/licenses*')" />
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/LabLicenseAccessTest.php --filter=sidebar`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add resources/views/components/layout/side-bar.blade.php tests/Feature/LabLicenseAccessTest.php
git commit -m "feat: add Lisensi Fakultas nav item to sidebar for kepala_lab and staff_lab"
```

---

### Task 4: Rate Limiting Verification, Code Style, & Full Test Suite

**Files:**
- Modify: `tests/Feature/LabLicenseAccessTest.php`
- Run: `vendor/bin/pint --dirty --format agent`

**Interfaces:**
- Consumes: All updated routes, controllers, views, and components
- Produces: Clean codebase adhering to Pint style and 100% green tests

- [ ] **Step 1: Add rate limiting test to `tests/Feature/LabLicenseAccessTest.php`**

```php
test('retrieving license key is throttled to 10 requests per minute', function () {
    $faculty = Faculty::factory()->create();
    $lab = Laboratory::factory()->create(['faculty_id' => $faculty->id]);
    $catalog = SoftwareCatalog::factory()->create();
    $license = LicenseInventory::factory()->create(['catalog_id' => $catalog->id]);

    LicenseAllocation::factory()->create([
        'license_inventory_id' => $license->id,
        'faculty_id' => $faculty->id,
        'status' => 'active',
    ]);

    $staff = User::factory()->create(['laboratory_id' => $lab->id]);
    $staff->assignRole('staff_lab');

    for ($i = 0; $i < 10; $i++) {
        $this->actingAs($staff)->postJson(route('lab.licenses.key', $license->id))->assertOk();
    }

    $this->actingAs($staff)->postJson(route('lab.licenses.key', $license->id))
        ->assertStatus(429);
});
```

- [ ] **Step 2: Run all tests in `LabLicenseAccessTest`**

Run: `vendor/bin/pest tests/Feature/LabLicenseAccessTest.php`
Expected: PASS (all tests pass)

- [ ] **Step 3: Run Pint formatter on changed files**

Run: `vendor/bin/pint --dirty --format agent`
Expected: Clean formatting with 0 style errors.

- [ ] **Step 4: Run complete application test suite**

Run: `php artisan test --compact`
Expected: PASS (all tests pass including Golden Path tests).

- [ ] **Step 5: Final Commit**

```bash
git add .
git commit -m "test: verify rate limiting and ensure code formatting for lab license access"
```
