# Task 11 — Controller + Views: `LaboratoryController` (CRUD)

## Sprint: 2 (CRUD Lab & Akun)
## Prioritas: Tinggi
## Dependensi: Sprint 1 selesai
## Estimasi: 2-3 jam

---

## Deskripsi

Buat controller dan views untuk CRUD laboratorium. Hanya admin yang bisa mengakses fitur ini.

## Langkah-langkah

### 1. Buat Controller

```bash
php artisan make:controller LaboratoryController
```

### 2. Implementasi Method

| Method | Route | Deskripsi |
|--------|-------|-----------|
| `index()` | GET `/laboratories` | Daftar semua lab + jumlah komputer + PJ Lab yang ditugaskan |
| `create()` | GET `/laboratories/create` | Form tambah lab baru |
| `store()` | POST `/laboratories` | Simpan lab baru |
| `edit($lab)` | GET `/laboratories/{laboratory}/edit` | Form edit lab |
| `update($lab)` | PUT `/laboratories/{laboratory}` | Update data lab |
| `destroy($lab)` | DELETE `/laboratories/{laboratory}` | Hapus lab (cek ada komputer terkait) |

### 3. Validasi (Buat FormRequest atau inline)

```php
// Store
'name' => 'required|string|max:255',
'code' => 'required|string|max:50|unique:laboratories,code',
'building' => 'nullable|string|max:255',
'floor' => 'nullable|string|max:50',
'description' => 'nullable|string',

// Update — sama tapi unique ignore current
'code' => 'required|string|max:50|unique:laboratories,code,' . $laboratory->id,
```

### 4. Logic `destroy()`

Sebelum hapus, cek:
- Jika ada komputer yang terkait → tampilkan peringatan atau tolak penghapusan
- Jika ada user (PJ Lab) yang terkait → set `laboratory_id` = null pada user tersebut

### 5. Buat Views

3 view Blade yang diperlukan:

**`resources/views/laboratories/index.blade.php`**
- Tabel: Nama, Kode, Gedung, Lantai, Jumlah Komputer, PJ Lab, Aksi (Edit/Hapus)
- Tombol "Tambah Laboratorium"
- Search/filter (opsional)

**`resources/views/laboratories/create.blade.php`**
- Form: name, code, building, floor, description
- Tombol Simpan + Batal

**`resources/views/laboratories/edit.blade.php`**
- Form yang sama dengan create, terisi data existing
- Tampilkan info tambahan: daftar komputer di lab ini, PJ Lab yang ditugaskan

### 6. Daftarkan Route

Di `routes/web.php`, dalam group middleware `role:admin`:

```php
Route::resource('laboratories', LaboratoryController::class);
```

## Referensi UI
- Gunakan layout dan styling yang konsisten dengan halaman CRUD lain di proyek (lihat Computer, License, Account views)
- Gunakan Tailwind CSS classes yang sudah ada
- Gunakan bahasa Indonesia untuk label UI

## File yang Dibuat
- `app/Http/Controllers/LaboratoryController.php`
- `resources/views/laboratories/index.blade.php`
- `resources/views/laboratories/create.blade.php`
- `resources/views/laboratories/edit.blade.php`
- (Opsional) `app/Http/Requests/StoreLaboratoryRequest.php`
- (Opsional) `app/Http/Requests/UpdateLaboratoryRequest.php`

## File yang Dimodifikasi
- `routes/web.php` — tambah route resource

## Verifikasi
- [ ] Halaman daftar laboratorium tampil dengan benar
- [ ] Bisa tambah lab baru (validasi code unique berjalan)
- [ ] Bisa edit lab existing
- [ ] Bisa hapus lab (dengan cek komputer terkait)
- [ ] Role selain admin tidak bisa akses (403)
- [ ] Activity log tercatat untuk setiap operasi CRUD
