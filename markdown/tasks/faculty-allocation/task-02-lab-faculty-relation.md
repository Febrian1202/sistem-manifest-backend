# Task 02: Relasi Laboratorium ke Fakultas (Lab-Faculty Relationship)

## 1. Ringkasan Task
Menghubungkan entitas **Laboratorium (`Laboratory`)** ke **Fakultas (`Faculty`)** melalui penambahan kolom `faculty_id`. Hal ini memungkinkan penelusuran hierarki komputer dan hasil scan lisensi:
```text
Computer → Laboratory → Faculty
```

- **Status Dependensi:** Bergantung pada penyelesaian [Task 01 — Entitas Fakultas](./task-01-faculty-entity.md).
- **Target File yang Dibuat / Diubah:**
  - `database/migrations/YYYY_MM_DD_HHMMSS_add_faculty_id_to_laboratories_table.php`
  - `app/Models/Laboratory.php`
  - `app/Models/Faculty.php`
  - `app/Http/Requests/StoreLaboratoryRequest.php`
  - `app/Http/Requests/UpdateLaboratoryRequest.php`
  - `app/Http/Controllers/LaboratoryController.php`
  - `resources/views/laboratories/index.blade.php`
  - `resources/views/laboratories/create.blade.php`
  - `resources/views/laboratories/edit.blade.php`
  - `database/seeders/DatabaseSeeder.php`
  - `tests/Feature/LaboratoryFacultyRelationTest.php`

---

## 2. Rincian Spesifikasi Teknis

### 2.1 Skema Database (`laboratories.faculty_id`)
Buat migration baru untuk menambahkan kolom foreign key `faculty_id`:
```php
Schema::table('laboratories', function (Blueprint $table) {
    $table->foreignId('faculty_id')
        ->nullable()
        ->after('id')
        ->constrained('faculties')
        ->nullOnDelete();
    
    $table->index('faculty_id');
});
```
*Catatan:* Dibuat `nullable()` di level basis data agar migration data existing tidak gagal, namun divalidasi `required` pada form input aplikasi.

### 2.2 Model Eloquent

#### `app/Models/Laboratory.php`
1. Tambahkan `faculty_id` ke dalam properti `$fillable`.
2. Daftarkan relasi ke `Faculty`:
   ```php
   public function faculty(): BelongsTo
   {
       return $this->belongsTo(Faculty::class);
   }
   ```
3. Update `getActivitylogOptions()` agar perubahan `faculty_id` ikut tercatat.

#### `app/Models/Faculty.php`
Pastikan relasi terbalik aktif:
```php
public function laboratories(): HasMany
{
    return $this->hasMany(Laboratory::class);
}

public function computers(): HasManyThrough
{
    return $this->hasManyThrough(Computer::class, Laboratory::class);
}
```

### 2.3 Form Requests
Update file yang ada:
- **`StoreLaboratoryRequest.php`**:
  Tambahkan aturan:
  ```php
  'faculty_id' => ['required', 'exists:faculties,id'],
  ```
  Sertakan pesan error: `'faculty_id.required' => 'Fakultas wajib dipilih.', 'faculty_id.exists' => 'Fakultas yang dipilih tidak valid.'`.
- **`UpdateLaboratoryRequest.php`**:
  Tambahkan aturan yang sama:
  ```php
  'faculty_id' => ['required', 'exists:faculties,id'],
  ```

### 2.4 Controller: `App\Http\Controllers\LaboratoryController`
Update method-method berikut:
- **`index(Request $request)`**:
  - Eager load relasi `faculty`:
    ```php
    $query = Laboratory::query()
        ->with('faculty')
        ->withCount('computers')
        ->with('penanggungJawab');
    ```
  - Tambahkan filter fakultas jika `$request->filled('faculty_id')`:
    ```php
    $query->where('faculty_id', $request->faculty_id);
    ```
  - Kirimkan data list seluruh fakultas (`Faculty::orderBy('name')->get()`) ke view untuk dropdown filter.
- **`create()`**:
  - Ambil seluruh fakultas: `$faculties = Faculty::orderBy('name')->get();` dan kirim ke view `laboratories.create`.
- **`store(StoreLaboratoryRequest $request)`**:
  - `$request->validated()` otomatis menyertakan `faculty_id`.
- **`edit(Laboratory $laboratory)`**:
  - Kirimkan `$faculties = Faculty::orderBy('name')->get();` ke view `laboratories.edit`.
- **`update(UpdateLaboratoryRequest $request, Laboratory $laboratory)`**:
  - `$request->validated()` memperbarui `faculty_id`.

### 2.5 Tampilan Antarmuka (Blade Views)

#### `resources/views/laboratories/index.blade.php`
1. **Area Filter:** Tambahkan `<select name="faculty_id">` berdampingan dengan search bar. Menampilkan opsi "Semua Fakultas" dan opsi tiap fakultas. Saat filter dipilih, auto-submit atau klik tombol cari.
2. **Tabel Data:**
   - Tambahkan kolom **Fakultas** setelah kolom Nama/Kode.
   - Tampilkan badge nama fakultas (`$lab->faculty->name ?? 'Belum Ditentukan'`).

#### `resources/views/laboratories/create.blade.php`
Tambahkan form field dropdown fakultas sebelum kolom nama:
```blade
<div class="col-span-2">
    <x-form.label for="faculty_id" :required="true">Fakultas</x-form.label>
    <select id="faculty_id" name="faculty_id" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" required>
        <option value="">-- Pilih Fakultas --</option>
        @foreach($faculties as $faculty)
            <option value="{{ $faculty->id }}" @selected(old('faculty_id') == $faculty->id)>
                {{ $faculty->code }} - {{ $faculty->name }}
            </option>
        @endforeach
    </select>
    @error('faculty_id')
        <p class="mt-1 text-xs text-destructive">{{ $message }}</p>
    @enderror
</div>
```

#### `resources/views/laboratories/edit.blade.php`
Tambahkan form field dropdown fakultas yang sama, dengan nilai terpilih: `@selected(old('faculty_id', $laboratory->faculty_id) == $faculty->id)`.

### 2.6 Pembaruan Seeder (`DatabaseSeeder.php`)
Pastikan seeder mengikat laboratorium ke salah satu fakultas yang dibuat di `FacultySeeder`:
```php
$fti = Faculty::where('code', 'FTI')->first();

Laboratory::firstOrCreate(
    ['code' => 'LAB-KOM1'],
    [
        'faculty_id' => $fti?->id,
        'name' => 'Laboratorium Komputer Dasar 1',
        'building' => 'Gedung FTI',
        'floor' => 'Lantai 2',
        'description' => 'Laboratorium untuk praktikum pemrograman dasar.',
    ]
);
```

---

## 3. Langkah-Langkah Pengerjaan (Step-by-Step)

1. Buat file migration:
   ```bash
   php artisan make:migration add_faculty_id_to_laboratories_table --table=laboratories
   ```
2. Definisikan `foreignId('faculty_id')` dan jalankan `php artisan migrate`.
3. Tambahkan relasi `belongsTo(Faculty::class)` di `Laboratory.php` dan `hasMany(Laboratory::class)` di `Faculty.php`.
4. Tambahkan validasi `faculty_id` pada `StoreLaboratoryRequest` dan `UpdateLaboratoryRequest`.
5. Sesuaikan `LaboratoryController` untuk passing `$faculties` dan filter query.
6. Perbarui view `laboratories/index.blade.php`, `create.blade.php`, dan `edit.blade.php`.
7. Perbarui seeder `DatabaseSeeder.php` agar setiap lab sample terafiliasi ke FTI/FKIP.
8. Buat dan jalankan test feature.

---

## 4. Kriteria Keberhasilan (Acceptance Criteria)
- [ ] Kolom `faculty_id` berhasil ditambahkan pada tabel `laboratories`.
- [ ] Laboratorium wajib memilih satu fakultas saat create/update.
- [ ] Halaman daftar laboratorium dapat difilter berdasarkan fakultas.
- [ ] Relasi `Laboratory -> faculty` dan `Faculty -> laboratories` dapat diakses secara Eloquent.
- [ ] Relasi `Faculty -> computers` dapat diakses melalui `hasManyThrough`.
- [ ] Seeder menghasilkan data laboratorium yang valid dengan relasi fakultas.

---

## 5. Rencana Pengujian Otomatis (Pest Test)
Buat file `tests/Feature/LaboratoryFacultyRelationTest.php`:
1. `it('requires faculty_id when storing a laboratory')`
2. `it('creates a laboratory linked to a faculty')`
3. `it('updates laboratory faculty correctly')`
4. `it('filters laboratories by faculty_id on index page')`
5. `it('retains laboratory with null faculty_id if parent faculty is deleted')` (menguji `nullOnDelete`)
