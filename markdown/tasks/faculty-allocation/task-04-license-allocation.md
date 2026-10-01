# Task 04: Sistem Alokasi Lisensi (License Allocation System)

## 1. Ringkasan Task
Membangun modul **Alokasi Lisensi (`LicenseAllocation`)**. Modul ini menjembatani kepemilikan lisensi universitas (`LicenseInventory`) dengan unit penerima hak pakai, yaitu Fakultas (`Faculty`).
Modul ini mengimplementasikan aturan bisnis fundamental:
1. Lisensi tetap merupakan aset terpusat Universitas.
2. Setiap alokasi mendistribusikan sejumlah kursi (`allocated_quota`) ke fakultas tertentu.
3. Total kuota yang dialokasikan dari suatu lisensi **tidak boleh melebihi** kuota kepemilikan aktif lisensi tersebut.

- **Status Dependensi:** Bergantung pada [Task 01 — Entitas Fakultas](./task-01-faculty-entity.md) dan [Task 02 — Relasi Laboratorium ke Fakultas](./task-02-lab-faculty-relation.md).
- **Target File yang Dibuat / Diubah:**
  - `database/migrations/YYYY_MM_DD_HHMMSS_create_license_allocations_table.php`
  - `app/Models/LicenseAllocation.php`
  - `app/Models/LicenseInventory.php`
  - `app/Models/Faculty.php`
  - `app/Http/Requests/StoreLicenseAllocationRequest.php`
  - `app/Http/Requests/UpdateLicenseAllocationRequest.php`
  - `app/Http/Controllers/LicenseAllocationController.php`
  - `resources/views/licenses/allocations/index.blade.php`
  - `resources/views/licenses/allocations/create.blade.php`
  - `resources/views/licenses/allocations/edit.blade.php`
  - `resources/views/components/layout/side-bar.blade.php`
  - `routes/web.php`
  - `tests/Feature/LicenseAllocationTest.php`

---

## 2. Rincian Spesifikasi Teknis

### 2.1 Skema Database (`license_allocations`)
```php
Schema::create('license_allocations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('license_inventory_id')
        ->constrained('license_inventories')
        ->restrictOnDelete()
        ->comment('Lisensi induk milik universitas');
    
    $table->foreignId('faculty_id')
        ->constrained('faculties')
        ->restrictOnDelete()
        ->comment('Fakultas penerima alokasi');
    
    $table->unsignedInteger('allocated_quota')->default(1)->comment('Jumlah kursi/lisensi yang dialokasikan');
    $table->date('allocation_date')->comment('Tanggal penetapan alokasi');
    $table->date('start_date')->nullable()->comment('Awal masa berlaku alokasi');
    $table->date('end_date')->nullable()->comment('Akhir masa berlaku alokasi');
    $table->string('status', 30)->default('active')->comment('active, inactive, revoked');
    $table->text('notes')->nullable()->comment('Catatan administrasi alokasi');
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();

    // Indexes untuk optimasi query agregasi
    $table->index(['license_inventory_id', 'status']);
    $table->index(['faculty_id', 'status']);
});
```

### 2.2 Model Eloquent

#### `App\Models\LicenseAllocation`
- Traits: `HasFactory`, `LogsActivity`.
- Fillable: `license_inventory_id`, `faculty_id`, `allocated_quota`, `allocation_date`, `start_date`, `end_date`, `status`, `notes`, `created_by`.
- Casts: `allocation_date` => `date`, `start_date` => `date`, `end_date` => `date`, `allocated_quota` => `integer`.
- Relasi:
  ```php
  public function licenseInventory(): BelongsTo
  {
      return $this->belongsTo(LicenseInventory::class);
  }

  public function faculty(): BelongsTo
  {
      return $this->belongsTo(Faculty::class);
  }

  public function creator(): BelongsTo
  {
      return $this->belongsTo(User::class, 'created_by');
  }
  ```
- Scopes:
  ```php
  public function scopeActive(Builder $query): Builder
  {
      return $query->where('status', 'active');
  }

  public function scopeForFaculty(Builder $query, int $facultyId): Builder
  {
      return $query->where('faculty_id', $facultyId);
  }
  ```

#### `App\Models\LicenseInventory`
Tambahkan relasi & helper kalkulasi alokasi:
```php
public function allocations(): HasMany
{
    return $this->hasMany(LicenseAllocation::class);
}

public function activeAllocations(): HasMany
{
    return $this->hasMany(LicenseAllocation::class)->where('status', 'active');
}

/**
 * Total kursi yang sudah dialokasikan ke semua fakultas.
 */
public function getTotalAllocatedAttribute(): int
{
    return (int) $this->activeAllocations()->sum('allocated_quota');
}

/**
 * Sisa kursi universitas yang masih belum dialokasikan.
 */
public function getRemainingUnallocatedAttribute(): int
{
    return max(0, $this->quota_limit - $this->total_allocated);
}
```

#### `App\Models\Faculty`
Tambahkan relasi:
```php
public function licenseAllocations(): HasMany
{
    return $this->hasMany(LicenseAllocation::class);
}
```

### 2.3 Aturan Validasi Kuota (Form Requests)

#### `StoreLicenseAllocationRequest.php`
Validasi memastikan alokasi baru tidak melebihi sisa kuota lisensi:
```php
public function rules(): array
{
    return [
        'license_inventory_id' => ['required', 'exists:license_inventories,id'],
        'faculty_id' => ['required', 'exists:faculties,id'],
        'allocated_quota' => ['required', 'integer', 'min:1'],
        'allocation_date' => ['required', 'date'],
        'start_date' => ['nullable', 'date'],
        'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        'status' => ['required', 'in:active,inactive,revoked'],
        'notes' => ['nullable', 'string', 'max:1000'],
    ];
}

public function withValidator($validator)
{
    $validator->after(function ($validator) {
        if ($this->filled('license_inventory_id') && $this->filled('allocated_quota')) {
            $inventory = LicenseInventory::find($this->license_inventory_id);
            if ($inventory) {
                $alreadyAllocated = (int) $inventory->activeAllocations()->sum('allocated_quota');
                $available = $inventory->quota_limit - $alreadyAllocated;

                if ($this->allocated_quota > $available) {
                    $validator->errors()->add(
                        'allocated_quota',
                        "Kuota alokasi ({$this->allocated_quota}) melebihi sisa lisensi yang tersedia ({$available} seat dari total {$inventory->quota_limit})."
                    );
                }
            }
        }
    });
}
```

#### `UpdateLicenseAllocationRequest.php`
Pada saat update, kuota dari record yang sedang diedit tidak boleh dihitung ganda:
```php
public function withValidator($validator)
{
    $validator->after(function ($validator) {
        $allocation = $this->route('license_allocation');
        $inventoryId = $this->input('license_inventory_id', $allocation->license_inventory_id);
        $inventory = LicenseInventory::find($inventoryId);

        if ($inventory) {
            // Hitung alokasi aktif lain selain record saat ini
            $otherAllocated = (int) $inventory->activeAllocations()
                ->where('id', '!=', $allocation->id)
                ->sum('allocated_quota');

            $available = $inventory->quota_limit - $otherAllocated;

            if ($this->input('status') === 'active' && $this->allocated_quota > $available) {
                $validator->errors()->add(
                    'allocated_quota',
                    "Kuota alokasi ({$this->allocated_quota}) melebihi kuota tersedia ({$available} seat)."
                );
            }
        }
    });
}
```

### 2.4 Controller: `App\Http\Controllers\LicenseAllocationController`
- `index(Request $request)`:
  - Query dengan eager load `['licenseInventory.catalog', 'faculty', 'creator']`.
  - Filter: `faculty_id`, `catalog_id`, `status`.
  - Agregasi statistik atas: Total Alokasi Aktif, Total Seat Terdistribusi, Fakultas Terbanyak Alokasi.
- `create()`:
  - Ambil lisensi yang masih memiliki sisa kuota (`whereRaw` atau filter collection di mana `remaining_unallocated > 0`).
  - Ambil daftar Fakultas.
  - Tampilkan form `licenses.allocations.create`.
- `store(StoreLicenseAllocationRequest $request)`:
  - Isi `created_by = auth()->id()`.
  - `LicenseAllocation::create(...)`.
  - Flash message sukses & redirect.
- `edit(LicenseAllocation $licenseAllocation)`:
  - Load data lisensi dan fakultas, tampilkan `licenses.allocations.edit`.
- `update(UpdateLicenseAllocationRequest $request, LicenseAllocation $licenseAllocation)`:
  - Update data, catat di log aktivitas, flash message sukses.
- `destroy(LicenseAllocation $licenseAllocation)`:
  - Hapus alokasi (atau ubah status jadi `revoked`), flash message.

### 2.5 Tampilan Antarmuka (Blade Views)

#### `resources/views/licenses/allocations/index.blade.php`
- Header: Breadcrumbs (`Dashboard > Lisensi & Audit > Alokasi Lisensi`), Judul, Tombol "Buat Alokasi Baru".
- Filter bar: Dropdown Fakultas, Dropdown Status, Search Software.
- Kartu Ringkasan (Cards):
  - Total Kuota Terdistribusi
  - Total Lisensi Menganggur (Belum Dialokasi)
  - Jumlah Fakultas Penerima
- Tabel Data:
  - Kolom: No, Software (Nama & Kategori), Lisensi (No PO / Kuota Total), Fakultas Penerima, Kuota Alokasi (Seat), Masa Berlaku, Status (Badge), Aksi (Edit, Hapus).

#### `resources/views/licenses/allocations/create.blade.php` & `edit.blade.php`
- Form Card terpusat:
  - Pilihan Lisensi Software: Menampilkan nama software, nomor PO, dan badge jumlah sisa seat tersedia secara real-time.
  - Pilihan Fakultas target.
  - Input Kuota Alokasi (integer, ada petunjuk sisa maksimal).
  - Tanggal Alokasi, Tanggal Mulai, Tanggal Berakhir.
  - Textarea Catatan.
  - Status (Active / Inactive).

### 2.6 Routing & Menu Sidebar
- Daftarkan di `routes/web.php`:
  ```php
  Route::middleware(['auth', 'role:admin'])->group(function () {
      Route::resource('license-allocations', LicenseAllocationController::class)
          ->parameters(['license-allocations' => 'license_allocation']);
  });
  ```
- Di `side-bar.blade.php`, pada seksi **Lisensi & Audit**:
  ```blade
  @role('admin')
      <x-layout.nav-item href="{{ route('licenses.index') }}" icon="fa-key" label="Inventaris Lisensi" />
      <x-layout.nav-item href="{{ route('license-allocations.index') }}" icon="fa-diagram-project" label="Alokasi Lisensi" />
      <x-layout.nav-item href="{{ route('compliance.index') }}" icon="fa-shield-halved" label="Audit Kepatuhan" />
  @endrole
  ```

---

## 3. Langkah-Langkah Pengerjaan (Step-by-Step)

1. Buat migration dan model:
   ```bash
   php artisan make:model LicenseAllocation -m
   ```
2. Definisikan kolom migration dan jalankan `php artisan migrate`.
3. Lengkapi model `LicenseAllocation.php`, relasi, scope, dan log activity.
4. Tambahkan relasi dan method kalkulasi pada `LicenseInventory.php` dan `Faculty.php`.
5. Buat Form Requests:
   ```bash
   php artisan make:request StoreLicenseAllocationRequest
   php artisan make:request UpdateLicenseAllocationRequest
   ```
6. Buat controller `LicenseAllocationController`:
   ```bash
   php artisan make:controller LicenseAllocationController
   ```
7. Buat direktori view `resources/views/licenses/allocations/` dan file view-nya.
8. Daftarkan resource route di `routes/web.php` dan tambahkan menu pada sidebar.
9. Tulis test feature di `tests/Feature/LicenseAllocationTest.php` dan verifikasi seluruh pengujian lolos.

---

## 4. Kriteria Keberhasilan (Acceptance Criteria)
- [ ] Admin dapat mendistribusikan kuota lisensi ke fakultas.
- [ ] Sistem menolak secara otomatis jika kuota alokasi melebihi sisa kapasitas lisensi yang dimiliki universitas.
- [ ] Tidak terjadi double-counting alokasi saat proses edit data.
- [ ] Status alokasi dapat diubah menjadi `inactive` atau `revoked` yang secara otomatis mengembalikan sisa kuota ke pool universitas.
- [ ] Menu Alokasi Lisensi tampil pada sidebar admin dan terproteksi dari akses user non-admin.

---

## 5. Rencana Pengujian Otomatis (Pest Test)
Buat file `tests/Feature/LicenseAllocationTest.php`:
1. `it('allows admin to create a valid license allocation')`
2. `it('rejects allocation when requested quota exceeds available license quota')`
3. `it('allows multiple allocations for the same license across different faculties if sum <= total quota')`
4. `it('allows updating allocation quota within remaining limit')`
5. `it('prevents update when new quota exceeds remaining limit')`
6. `it('recalculates remaining unallocated license quota when an allocation is marked inactive or deleted')`
7. `it('forbids non-admin users from creating or editing allocations')`
