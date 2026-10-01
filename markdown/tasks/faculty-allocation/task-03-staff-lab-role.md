# Task 03: Peran Staff Lab & Scoping Akses (Staff Lab Role)

## 1. Ringkasan Task
Menambahkan peran baru **Staff Lab (`staff_lab`)** yang bertugas sebagai operator teknis di lapangan untuk mendeploy/menginstall scanner agent ke komputer-komputer laboratorium, serta memantau status scan perangkat.
Sesuai hasil diskusi kebutuhan:
- **Hak Akses:** Read-only terhadap data komputer & hasil scan, serta hak download scanner agent. Staff **tidak** mengelola lisensi (admin sistem tetap menjadi *single source of truth*).
- **Scope Penugasan:** Fleksibel — staf dapat ditugaskan pada satu **Laboratorium** spesifik ATAU pada satu **Fakultas** (mencakup seluruh lab di bawah fakultas tersebut).

- **Status Dependensi:** Bergantung pada penyelesaian [Task 01 — Entitas Fakultas](./task-01-faculty-entity.md) dan [Task 02 — Relasi Laboratorium ke Fakultas](./task-02-lab-faculty-relation.md).
- **Target File yang Dibuat / Diubah:**
  - `database/migrations/YYYY_MM_DD_HHMMSS_add_faculty_id_to_users_table.php`
  - `app/Models/User.php`
  - `database/seeders/RoleAndPermissionSeeder.php`
  - `app/Models/Traits/ScopedByLaboratory.php`
  - `app/Http/Requests/StoreAccountRequest.php`
  - `app/Http/Requests/UpdateAccountRequest.php`
  - `app/Http/Controllers/AccountController.php`
  - `app/Http/Controllers/AgentDownloadController.php`
  - `resources/views/pages/admin/accounts.blade.php`
  - `resources/views/components/layout/side-bar.blade.php`
  - `routes/web.php`
  - `tests/Feature/StaffLabRoleTest.php`

---

## 2. Rincian Spesifikasi Teknis

### 2.1 Skema Database (`users.faculty_id`)
Tambahkan foreign key `faculty_id` ke tabel `users` untuk mendukung penugasan staff berbasis fakultas:
```php
Schema::table('users', function (Blueprint $table) {
    $table->foreignId('faculty_id')
        ->nullable()
        ->after('laboratory_id')
        ->constrained('faculties')
        ->nullOnDelete();
    
    $table->index('faculty_id');
});
```

### 2.2 Model `App\Models\User`
- Tambahkan `faculty_id` pada `$fillable`.
- Tambahkan relasi:
  ```php
  public function faculty(): BelongsTo
  {
      return $this->belongsTo(Faculty::class);
  }
  ```
- Tambahkan helper method untuk cek cakupan penugasan:
  ```php
  /**
   * Mendapatkan daftar ID laboratorium yang berada di bawah kewenangan user ini.
   */
  public function getAccessibleLaboratoryIds(): array
  {
      if ($this->hasRole('admin') || $this->hasRole('pimpinan')) {
          return Laboratory::pluck('id')->toArray();
      }

      if ($this->laboratory_id) {
          return [$this->laboratory_id];
      }

      if ($this->faculty_id) {
          return Laboratory::where('faculty_id', $this->faculty_id)->pluck('id')->toArray();
      }

      return [];
  }
  ```

### 2.3 Role & Permissions (`RoleAndPermissionSeeder.php`)
Daftarkan role `staff_lab` dan asosiasi permission-nya:
```php
$staffLabRole = Role::firstOrCreate(['name' => 'staff_lab']);
$staffLabRole->givePermissionTo([
    'access panel',
    'view lab inventory', // Melihat komputer & software lab yang ditugaskan
]);
```
Tambahkan permission khusus jika belum ada:
```php
Permission::firstOrCreate(['name' => 'download agent scanner']);
$adminRole->givePermissionTo('download agent scanner');
$kepalaLabRole->givePermissionTo('download agent scanner');
$staffLabRole->givePermissionTo('download agent scanner');
```

### 2.4 Trait Scoping: `ScopedByLaboratory.php`
Perbarui scoping agar mengenali `staff_lab` dengan fleksibilitas lab atau fakultas:
```php
public function scopeForUserLab(Builder $query): Builder
{
    $user = auth()->user();

    if (! $user) {
        return $query;
    }

    if ($user->hasRole('admin') || $user->hasRole('pimpinan')) {
        return $query; // Tidak dibatasi untuk level universitas
    }

    if ($user->hasRole('kepala_lab') || $user->hasRole('staff_lab')) {
        $labIds = $user->getAccessibleLaboratoryIds();
        
        // Sesuaikan dengan letak foreign key model bersangkutan:
        // 1. Jika model memiliki kolom laboratory_id langsung
        if (Schema::hasColumn($this->getTable(), 'laboratory_id')) {
            return $query->whereIn($this->getTable() . '.laboratory_id', $labIds);
        }
        
        // 2. Jika model berhubungan lewat relasi computer
        if (method_exists($this, 'computer')) {
            return $query->whereHas('computer', function ($q) use ($labIds) {
                $q->whereIn('laboratory_id', $labIds);
            });
        }
    }

    return $query;
}
```

### 2.5 Form Request Akun & Manajemen Akun
- **`StoreAccountRequest.php` & `UpdateAccountRequest.php`**:
  - `role`: tambahkan `staff_lab` ke dalam enum/exists rule.
  - Aturan kondisional:
    - Jika role = `kepala_lab`: `laboratory_id` wajib (`required`).
    - Jika role = `staff_lab`: minimal salah satu dari `laboratory_id` atau `faculty_id` harus diisi (`required_without:faculty_id` dan `required_without:laboratory_id`).
- **`resources/views/pages/admin/accounts.blade.php`**:
  - Tambahkan opsi role "Staff Lab" pada modal/form Tambah & Edit Akun.
  - Tambahkan dropdown pilihan Fakultas (muncul saat role Staff Lab dipilih menggunakan Alpine.js `x-show="role === 'staff_lab'"`).
  - Pada tabel daftar akun, tampilkan badge Role `Staff Lab` beserta nama Lab/Fakultas yang ditugaskan.

### 2.6 Akses Download Agent & Scanner Deployment
- **`routes/web.php`**:
  Pastikan endpoint download agent dapat diakses oleh `staff_lab`:
  ```php
  Route::middleware(['auth', 'role:admin|kepala_lab|staff_lab'])->group(function () {
      Route::get('/agent/download', [AgentDownloadController::class, 'index'])->name('agent.download');
      Route::post('/agent/download/bundle', [AgentDownloadController::class, 'downloadBundle'])->name('agent.download.bundle');
  });
  ```
- **`AgentDownloadController.php`**:
  - Untuk role `staff_lab`, saat men-generate script konfigurasi `config.json`, dropdown pilihan lab hanya memuat lab yang ada dalam `getAccessibleLaboratoryIds()`.

### 2.7 Navigasi Menu (`side-bar.blade.php`)
Sesuaikan tampilan sidebar untuk `staff_lab`:
```blade
@role('staff_lab')
    <div class="px-3 py-2 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
        Menu Staff Lab
    </div>
    <x-layout.nav-item href="{{ route('lab-inventory.index') }}" icon="fa-desktop" label="Komputer & Aset" />
    <x-layout.nav-item href="{{ route('agent.download') }}" icon="fa-download" label="Download Scanner" />
@endrole
```

---

## 3. Langkah-Langkah Pengerjaan (Step-by-Step)

1. Buat migration `add_faculty_id_to_users_table`:
   ```bash
   php artisan make:migration add_faculty_id_to_users_table --table=users
   ```
2. Tambahkan kolom, indeks, dan relasi pada `User.php`.
3. Implementasikan method `getAccessibleLaboratoryIds()` pada `User.php`.
4. Perbarui `RoleAndPermissionSeeder.php` untuk mendaftarkan role `staff_lab` dan permissions.
5. Perbarui trait `ScopedByLaboratory.php` agar mendukung filter multi-lab berdasarkan fakultas penugasan.
6. Perbarui form requests `StoreAccountRequest` dan `UpdateAccountRequest`.
7. Perbarui view `pages/admin/accounts.blade.php` untuk form input penugasan fakultas/lab.
8. Buka akses route `/agent/download` dan inventaris lab untuk role `staff_lab`.
9. Sesuaikan `side-bar.blade.php`.
10. Tulis dan jalankan test feature.

---

## 4. Kriteria Keberhasilan (Acceptance Criteria)
- [x] Role `staff_lab` berhasil dibuat dalam sistem.
- [x] Admin dapat membuat akun `staff_lab` dengan penugasan ke Lab tertentu atau ke Fakultas tertentu.
- [x] Staff Lab yang ditugaskan ke Fakultas dapat melihat seluruh komputer di semua lab di bawah fakultas tersebut.
- [x] Staff Lab yang ditugaskan ke Lab tertentu hanya dapat melihat komputer di lab tersebut.
- [x] Staff Lab dapat mendownload bundle scanner agent yang sudah terkonfigurasi untuk lab yang ditugaskan.
- [x] Staff Lab TIDAK memiliki akses ke menu pengelolaan lisensi, alokasi lisensi, maupun manipulasi master data fakultas/lab.

---

## 5. Rencana Pengujian Otomatis (Pest Test)
Buat file `tests/Feature/StaffLabRoleTest.php`:
1. `it('assigns staff_lab role to a user scoped to a laboratory')`
2. `it('assigns staff_lab role to a user scoped to a faculty')`
3. `it('restricts staff_lab computer view to their assigned faculty laboratories')`
4. `it('allows staff_lab to access agent download page with their allowed labs only')`
5. `it('denies staff_lab from accessing license management and allocation endpoints')`
