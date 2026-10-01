# Task 01: Entitas Fakultas (Master Data Faculty)

## 1. Ringkasan Task
Membuat entitas **Fakultas (`Faculty`)** sebagai unit organisasi tingkat atas di USN Kolaka. Fakultas akan menjadi induk dari laboratorium-laboratorium komputer dan menjadi sasaran entitas alokasi lisensi software.

- **Status Dependensi:** Tidak ada (Langkah awal / fondasi).
- **Target File yang Dibuat / Diubah:**
  - `database/migrations/YYYY_MM_DD_HHMMSS_create_faculties_table.php`
  - `app/Models/Faculty.php`
  - `database/factories/FacultyFactory.php`
  - `database/seeders/FacultySeeder.php`
  - `app/Http/Requests/StoreFacultyRequest.php`
  - `app/Http/Requests/UpdateFacultyRequest.php`
  - `app/Http/Controllers/FacultyController.php`
  - `resources/views/faculties/index.blade.php`
  - `resources/views/faculties/create.blade.php`
  - `resources/views/faculties/edit.blade.php`
  - `routes/web.php`
  - `resources/views/components/layout/side-bar.blade.php`
  - `tests/Feature/FacultyManagementTest.php`

---

## 2. Rincian Spesifikasi Teknis

### 2.1 Skema Database (`faculties`)
Tabel `faculties` dibuat dengan struktur berikut:
```php
Schema::create('faculties', function (Blueprint $table) {
    $table->id();
    $table->string('code', 50)->unique()->comment('Kode resmi fakultas, misal FTI, FKIP');
    $table->string('name', 255)->comment('Nama lengkap fakultas');
    $table->text('description')->nullable()->comment('Deskripsi atau keterangan fakultas');
    $table->timestamps();
});
```

### 2.2 Model `App\Models\Faculty`
- Menggunakan trait: `HasFactory`, `LogsActivity` (Spatie Activity Log).
- Konfigurasi `getActivitylogOptions()`: mencatat atribut `code`, `name`, `description` jika berubah (`logOnlyDirty()`).
- Relasi awal:
  ```php
  public function laboratories(): HasMany
  {
      return $this->hasMany(Laboratory::class);
  }
  ```

### 2.3 Factory & Seeder
- **`FacultyFactory.php`**: Menyediakan fake `code` (e.g. `FTI`, `FKIP`) dan `name`.
- **`FacultySeeder.php`**: Menyediakan data awal representatif fakultas di USN Kolaka:
  1. `FTI` — Fakultas Teknologi Informasi
  2. `FKIP` — Fakultas Keguruan dan Ilmu Pendidikan
  3. `FISIP` — Fakultas Ilmu Sosial dan Ilmu Politik
  4. `FPP` — Fakultas Pertanian, Perikanan dan Peternakan
  5. `SAINS-TEK` — Fakultas Sains dan Teknologi
  6. `HUKUM` — Fakultas Hukum

### 2.4 Form Requests
- **`StoreFacultyRequest`**:
  - `authorize()`: `$this->user()?->hasRole('admin') ?? false`
  - Aturan:
    - `name` => `['required', 'string', 'max:255']`
    - `code` => `['required', 'string', 'max:50', 'unique:faculties,code']`
    - `description` => `['nullable', 'string']`
  - Pesan error kustom dalam Bahasa Indonesia.
- **`UpdateFacultyRequest`**:
  - Mirip dengan `StoreFacultyRequest`, namun `unique:faculties,code` mengecualikan ID fakultas yang sedang diedit:
    `'unique:faculties,code,' . $this->route('faculty')->id`

### 2.5 Controller: `App\Http\Controllers\FacultyController`
Mengikuti pola `LaboratoryController` yang sudah ada:
- `index(Request $request)`:
  - Query dengan `withCount('laboratories')`.
  - Filter pencarian teks (`code` atau `name`).
  - Pagination 10 baris dengan `withQueryString()`.
- `create()`: Menampilkan view `faculties.create`.
- `store(StoreFacultyRequest $request)`: Membuat record baru, flash message sukses, redirect ke `faculties.index`.
- `edit(Faculty $faculty)`: Eager load `laboratories`, tampilkan view `faculties.edit`.
- `update(UpdateFacultyRequest $request, Faculty $faculty)`: Update record, flash message sukses.
- `destroy(Faculty $faculty)`:
  - Cek jika `$faculty->laboratories()->exists()`. Jika ada, tolak penghapusan dengan flash message destructive (`"Fakultas tidak dapat dihapus karena masih memiliki laboratorium."`).
  - Jika kosong, hapus record dan kembalikan response sukses.

### 2.6 Tampilan Antarmuka (Blade Views)
Konsisten dengan styling Tailwind CSS 4 dan komponen Blade yang ada (`<x-layout.app>`, `<x-ui.table>`, `<x-ui.dialog.confirm>`, `<x-form.*>`):
- **`resources/views/faculties/index.blade.php`**:
  - Header: Breadcrumbs (`Dashboard > Fakultas`), Judul "Data Fakultas", Tombol "Tambah Fakultas".
  - Search bar input kata kunci.
  - Tabel: Kolom No, Kode Fakultas (Badge), Nama Fakultas, Jumlah Laboratorium (Badge counter), Deskripsi, Aksi (Edit, Hapus via dialog konfirmasi).
- **`resources/views/faculties/create.blade.php`**:
  - Form card centered (`max-w-3xl`): Input Kode Fakultas, Nama Fakultas, Textarea Deskripsi. Tombol Batal & Simpan.
- **`resources/views/faculties/edit.blade.php`**:
  - Form edit fakultas (`max-w-4xl`).
  - Sub-section: **Daftar Laboratorium Terdaftar** di bawah fakultas ini (menampilkan tabel lab, kode, gedung, lantai).

### 2.7 Routing & Navigasi
- Di `routes/web.php`:
  ```php
  Route::middleware(['auth', 'role:admin'])->group(function () {
      Route::resource('faculties', FacultyController::class);
  });
  ```
  *(Catatan: Pimpinan dapat diberi akses `index` dan `show` jika diperlukan pada role-group pimpinan).*
- Di `resources/views/components/layout/side-bar.blade.php`:
  - Tambahkan menu **Fakultas** pada seksi **Manajemen Aset** atau seksi **Organisasi** di atas submenu **Laboratorium**.

---

## 3. Langkah-Langkah Pengerjaan (Step-by-Step)

1. Jalankan pembuatan migration, model, dan factory:
   ```bash
   php artisan make:model Faculty -mf
   ```
2. Definisikan kolom pada file migration dan jalankan `php artisan migrate`.
3. Lengkapi model `Faculty.php` dengan fillable, casts, log activity, dan relasi `laboratories()`.
4. Buat form requests:
   ```bash
   php artisan make:request StoreFacultyRequest
   php artisan make:request UpdateFacultyRequest
   ```
5. Buat controller `FacultyController`:
   ```bash
   php artisan make:controller FacultyController
   ```
6. Buat file views di `resources/views/faculties/` (`index.blade.php`, `create.blade.php`, `edit.blade.php`).
7. Daftarkan resource route di `routes/web.php`.
8. Tambahkan navigasi link pada `side-bar.blade.php`.
9. Buat `FacultySeeder.php` dan panggil pada `DatabaseSeeder.php`.
10. Buat test feature `tests/Feature/FacultyManagementTest.php` dan jalankan.

---

## 4. Kriteria Keberhasilan (Acceptance Criteria)
- [ ] Migration `create_faculties_table` berhasil dieksekusi tanpa error di MySQL dan SQLite.
- [ ] Admin dapat membuat, melihat, mengedit, dan menghapus fakultas melalui antarmuka web.
- [ ] Validasi kode fakultas unik bekerja dengan pesan kesalahan yang informatif.
- [ ] Fakultas yang masih memiliki laboratorium tidak dapat dihapus (proteksi integritas data).
- [ ] Aktivitas manipulasi fakultas tercatat di `activity_log`.
- [ ] Menu navigasi baru muncul pada sidebar untuk admin.
- [ ] Seluruh unit/feature test untuk `Faculty` lulus dengan status hijau (100% pass).

---

## 5. Rencana Pengujian Otomatis (Pest Test)
Buat file `tests/Feature/FacultyManagementTest.php` dengan cakupan skenario:
1. `it('allows admin to view faculties list')`
2. `it('forbids unauthenticated users and unauthorized roles from accessing faculty crud')`
3. `it('validates faculty code uniqueness on store and update')`
4. `it('successfully creates a new faculty with valid payload')`
5. `it('successfully updates an existing faculty')`
6. `it('prevents deletion of faculty when laboratories exist')`
7. `it('allows deletion of faculty when no laboratories are linked')`
