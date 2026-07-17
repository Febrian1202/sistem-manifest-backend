# Task 12 — Update `AccountController`: Tambah Role `kepala_lab` + Pilih Lab

## Sprint: 2 (CRUD Lab & Akun)
## Prioritas: Tinggi
## Dependensi: Sprint 1 selesai
## Estimasi: 1-2 jam

---

## Deskripsi

Update controller dan views akun pengguna agar mendukung role `kepala_lab` dan pemilihan laboratorium.

## Langkah-langkah

### 1. Update Controller (`AccountController`)

**Method `create()` / form view:**
- Tambah data `$laboratories` ke view: `Laboratory::orderBy('name')->get()`
- Tambah data `$roles` ke view: `Role::pluck('name')`

**Method `store()`:**
- Tambah validasi:
  ```php
  'role' => 'required|in:admin,kepala_lab,pimpinan',
  'laboratory_id' => 'nullable|required_if:role,kepala_lab|exists:laboratories,id',
  ```
- Saat simpan user, set `laboratory_id` jika role = `kepala_lab`
- Assign role yang dipilih (bukan hardcode)

**Method `edit()` / form view:**
- Sama seperti create, tapi isi data existing
- **Proteksi**: Admin tidak bisa mengubah role diri sendiri (sudah ada, pertahankan)

**Method `update()`:**
- Validasi sama seperti store
- Jika role berubah dari `kepala_lab` ke role lain, set `laboratory_id` = null
- Jika role berubah ke `kepala_lab`, `laboratory_id` wajib diisi
- Sync role (hapus role lama, assign role baru)

### 2. Update Views

**Form create/edit akun** — tambah:

```html
<!-- Dropdown Role -->
<label>Role</label>
<select name="role">
    <option value="admin">Administrator</option>
    <option value="kepala_lab">Kepala Lab / PJ Lab</option>
    <option value="pimpinan">Pimpinan</option>
</select>

<!-- Dropdown Laboratorium (tampil hanya jika role = kepala_lab) -->
<div x-show="role === 'kepala_lab'"> <!-- atau JS conditional -->
    <label>Laboratorium</label>
    <select name="laboratory_id">
        <option value="">-- Pilih Laboratorium --</option>
        @foreach($laboratories as $lab)
            <option value="{{ $lab->id }}">{{ $lab->name }} ({{ $lab->code }})</option>
        @endforeach
    </select>
</div>
```

**Daftar akun (index)** — tambah kolom:
- Role (tampilkan badge warna berbeda per role)
- Laboratorium (tampilkan nama lab jika role = kepala_lab, atau "-" jika role lain)

### 3. Validasi Bisnis

- Minimal 1 user dengan role `admin` harus selalu ada (sudah ada, pertahankan)
- Satu lab bisa punya lebih dari satu PJ Lab (tidak ada unique constraint)
- Admin dan Pimpinan TIDAK boleh punya `laboratory_id`

## Referensi
- Lihat `AccountController` yang sudah ada untuk memahami pola CRUD, proteksi self-edit, dan minimum admin enforcement
- Lihat `app/Http/Requests/` untuk pola form request yang sudah ada

## File yang Dimodifikasi
- `app/Http/Controllers/AccountController.php`
- View form create akun (cari file yang berisi form input nama/email akun)
- View form edit akun
- View daftar akun (index)
- (Opsional) `app/Http/Requests/StoreAccountRequest.php` atau `UpdateAccountRequest.php`

## Verifikasi
- [ ] Bisa buat akun dengan role `kepala_lab` + pilih lab
- [ ] Bisa buat akun dengan role `admin` atau `pimpinan` (tanpa lab)
- [ ] Dropdown lab muncul hanya saat role `kepala_lab` dipilih
- [ ] Validasi: role `kepala_lab` tanpa lab → error
- [ ] Edit akun: ganti role dari `kepala_lab` ke `admin` → lab jadi null
- [ ] Daftar akun menampilkan kolom Role dan Lab
- [ ] Self-edit protection tetap berjalan
- [ ] Minimum 1 admin enforcement tetap berjalan
