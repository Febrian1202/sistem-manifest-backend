# Planning: Fitur Manajemen Akun Pengguna

> **Tanggal:** 5 Juni 2026  
> **Proyek:** Sistem Manifest — USN Kolaka  
> **Status:** 📋 Draft / Menunggu Persetujuan

---

## 1. Latar Belakang & Analisis Kondisi Saat Ini

Saat ini, sistem **tidak memiliki antarmuka (UI)** untuk mengelola akun pengguna. Pembuatan akun hanya bisa dilakukan melalui **database seeder** (`DatabaseSeeder.php`), yang meng-_hardcode_ dua akun default:

| Email                | Role     |
| -------------------- | -------- |
| `admin@usn.ac.id`    | admin    |
| `pimpinan@usn.ac.id` | pimpinan |

### Keterbatasan Saat Ini

- ❌ Tidak ada halaman untuk **melihat daftar akun**
- ❌ Tidak ada fungsi untuk **menambah akun baru**
- ❌ Tidak ada fungsi untuk **mengedit profil akun** (nama, email, role)
- ❌ Tidak ada fungsi untuk **menghapus akun**
- ❌ Tidak ada fungsi untuk **mengganti password sendiri** (self-service)
- ❌ Tidak ada fungsi untuk **admin me-reset password** user lain
- ❌ Menu "Profil" di top-bar dropdown masih di-_comment out_

### Komponen & Infrastruktur yang Sudah Tersedia

- ✅ **Model `User`** — sudah menggunakan `HasRoles` (Spatie), `fillable: [name, email, password]`, password sudah di-_cast_ sebagai `hashed`
- ✅ **RBAC** — `spatie/laravel-permission` sudah aktif dengan 2 role (`admin`, `pimpinan`) dan 4 permission
- ✅ **AuthController** — sudah menangani login/logout
- ✅ **Layout** — `app.blade.php`, `side-bar.blade.php`, `top-bar.blade.php` sudah tersedia
- ✅ **UI Components** — button, card, dialog/confirm, dropdown, select, table, toast, form (input, label)
- ✅ **Pattern yang Konsisten** — CRUD pattern pada Computers dan Licenses bisa dijadikan referensi

---

## 2. Tujuan Fitur

Membangun fitur **Manajemen Akun** yang memungkinkan:

1. **Admin** dapat melakukan CRUD akun pengguna (tambah, lihat, edit, hapus)
2. **Admin** dapat me-reset password user lain
3. **Setiap user** (admin maupun pimpinan) dapat mengganti password sendiri
4. Fitur terintegrasi penuh dengan **RBAC** yang sudah ada

---

## 3. Daftar Fitur Detail

### 3.1. Halaman Daftar Akun (`/accounts`) — Admin Only

| Aspek          | Detail                                                                  |
| -------------- | ----------------------------------------------------------------------- |
| **Akses**      | Hanya role `admin`                                                      |
| **Tampilan**   | Tabel daftar user dengan kolom: Nama, Email, Role, Tanggal Dibuat, Aksi |
| **Pencarian**  | Filter berdasarkan nama/email                                           |
| **Filter**     | Filter berdasarkan role (Admin, Pimpinan, Semua)                        |
| **Pagination** | Pagination standar seperti halaman Komputer                             |

### 3.2. Tambah Akun Baru — Admin Only

| Aspek         | Detail                                                                      |
| ------------- | --------------------------------------------------------------------------- | -------------------------- | ----- | ---------------------------------- | ----- | --------------------------- | ------------------ |
| **Trigger**   | Tombol "Tambah Akun" di halaman daftar                                      |
| **Metode UI** | Dialog modal (konsisten dengan pattern License)                             |
| **Field**     | Nama, Email, Password, Konfirmasi Password, Role (dropdown: admin/pimpinan) |
| **Validasi**  | `name: required                                                             | max:255`, `email: required | email | unique:users`, `password: required | min:8 | confirmed`, `role: required | in:admin,pimpinan` |

### 3.3. Edit Akun — Admin Only

| Aspek         | Detail                                                  |
| ------------- | ------------------------------------------------------- | -------------------------- | ----- | ----------------------------------------- | ------------------ |
| **Trigger**   | Tombol aksi "Edit" di setiap baris tabel                |
| **Metode UI** | Dialog modal                                            |
| **Field**     | Nama, Email, Role                                       |
| **Validasi**  | `name: required                                         | max:255`, `email: required | email | unique:users,email,{id}`, `role: required | in:admin,pimpinan` |
| **Catatan**   | Password **tidak** diubah di sini (ada fungsi terpisah) |

### 3.4. Hapus Akun — Admin Only

| Aspek                | Detail                                                     |
| -------------------- | ---------------------------------------------------------- |
| **Trigger**          | Tombol aksi "Hapus" di setiap baris tabel                  |
| **Metode UI**        | Dialog konfirmasi (`x-ui.dialog.confirm`)                  |
| **Validasi Backend** | Tidak bisa menghapus akun sendiri (self-delete prevention) |

### 3.5. Reset Password (oleh Admin) — Admin Only

| Aspek         | Detail                                                             |
| ------------- | ------------------------------------------------------------------ | ----- | ---------- |
| **Trigger**   | Tombol aksi "Reset Password" di setiap baris tabel                 |
| **Metode UI** | Dialog modal dengan field: Password Baru, Konfirmasi Password Baru |
| **Validasi**  | `password: required                                                | min:8 | confirmed` |
| **Catatan**   | Admin bisa me-reset password user mana pun (termasuk admin lain)   |

### 3.6. Ganti Password Sendiri (Self-Service) — Semua User

| Aspek         | Detail                                                   |
| ------------- | -------------------------------------------------------- | -------------------------------------- | ----- | --------- | --------------------------- |
| **Trigger**   | Menu dropdown profil di top-bar → item "Ganti Password"  |
| **Metode UI** | Halaman terpisah (`/account/password`) atau dialog modal |
| **Field**     | Password Lama, Password Baru, Konfirmasi Password Baru   |
| **Validasi**  | `current_password: required                              | current_password`, `password: required | min:8 | confirmed | different:current_password` |

---

## 4. Arsitektur & File yang Akan Dibuat/Dimodifikasi

### 4.1. Backend (Controller, Request, Route)

#### [BARU] `app/Http/Controllers/AccountController.php`

Controller utama untuk manajemen akun dengan method:

| Method             | HTTP   | Route                             | Fungsi                      |
| ------------------ | ------ | --------------------------------- | --------------------------- |
| `index()`          | GET    | `/accounts`                       | Menampilkan daftar akun     |
| `store()`          | POST   | `/accounts`                       | Menyimpan akun baru         |
| `update()`         | PUT    | `/accounts/{user}`                | Memperbarui data akun       |
| `destroy()`        | DELETE | `/accounts/{user}`                | Menghapus akun              |
| `resetPassword()`  | PUT    | `/accounts/{user}/reset-password` | Admin reset password user   |
| `changePassword()` | PUT    | `/account/password`               | User ganti password sendiri |

#### [BARU] `app/Http/Requests/StoreAccountRequest.php`

Form Request untuk validasi pembuatan akun baru:

- `name` → required, string, max:255
- `email` → required, email, unique:users
- `password` → required, min:8, confirmed
- `role` → required, in:admin,pimpinan

#### [BARU] `app/Http/Requests/UpdateAccountRequest.php`

Form Request untuk validasi update akun:

- `name` → required, string, max:255
- `email` → required, email, unique:users,email,{user_id}
- `role` → required, in:admin,pimpinan

#### [BARU] `app/Http/Requests/ResetPasswordRequest.php`

Form Request untuk validasi reset password oleh admin:

- `password` → required, min:8, confirmed

#### [BARU] `app/Http/Requests/ChangePasswordRequest.php`

Form Request untuk validasi ganti password sendiri:

- `current_password` → required, current_password
- `password` → required, min:8, confirmed, different:current_password

#### [MODIFIKASI] `routes/web.php`

Menambahkan route group baru:

```php
// Manajemen Akun (Admin Only)
Route::middleware(['role:admin'])->group(function () {
    Route::get('/accounts', [AccountController::class, 'index'])->name('accounts');
    Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
    Route::put('/accounts/{user}', [AccountController::class, 'update'])->name('accounts.update');
    Route::delete('/accounts/{user}', [AccountController::class, 'destroy'])->name('accounts.destroy');
    Route::put('/accounts/{user}/reset-password', [AccountController::class, 'resetPassword'])->name('accounts.reset-password');
});

// Ganti Password Sendiri (Semua User Terautentikasi)
Route::put('/account/password', [AccountController::class, 'changePassword'])->name('account.change-password');
```

---

### 4.2. Frontend (Blade Views)

#### [BARU] `resources/views/pages/admin/accounts.blade.php`

Halaman utama daftar akun, mengikuti pattern dari `computers.blade.php`:

- Header dengan judul dan tombol "Tambah Akun"
- Form pencarian dan filter role
- Tabel daftar akun
- Pagination
- Dialog modal untuk tambah/edit/reset password
- Dialog konfirmasi untuk hapus

#### [BARU] `resources/views/components/accounts/table.blade.php`

Komponen tabel untuk menampilkan daftar user:

- Kolom: No, Nama, Email, Role (badge), Tanggal Dibuat, Aksi
- Tombol aksi per baris: Edit, Reset Password, Hapus
- Role ditampilkan sebagai badge berwarna (Admin = biru, Pimpinan = hijau)

#### [MODIFIKASI] `resources/views/components/layout/side-bar.blade.php`

Menambahkan menu "Manajemen Akun" di sidebar, hanya untuk role `admin`:

```blade
@role('admin')
    <div class="mt-4 px-3 mb-2 ...">Pengaturan</div>
    <a href="/accounts" class="...">
        <i class="fa-solid fa-users-gear"></i>
        <span>Manajemen Akun</span>
    </a>
@endrole
```

#### [MODIFIKASI] `resources/views/components/layout/top-bar.blade.php`

Meng-_uncomment_ dan mengaktifkan menu dropdown profil, menambahkan item "Ganti Password":

```blade
<x-dropdown-item href="#" @click="$dispatch('open-dialog', 'change-password')">
    <i class="fa-solid fa-key mr-2"></i>
    Ganti Password
</x-dropdown-item>
```

---

### 4.3. Permission (Opsional tapi Direkomendasikan)

#### [MODIFIKASI] `database/seeders/RoleAndPermissionSeeder.php`

Menambahkan permission baru:

```php
Permission::firstOrCreate(['name' => 'manage users', 'guard_name' => 'web']);
```

Dan assign ke role admin:

```php
$adminRole->givePermissionTo(Permission::all()); // Sudah otomatis termasuk
```

---

## 5. Alur Kerja (Flow)

### 5.1. Admin Menambah Akun Baru

```
Admin klik "Tambah Akun" → Dialog modal terbuka → Isi form (Nama, Email, Password, Role)
→ Submit → Validasi (StoreAccountRequest) → Simpan ke DB + Assign Role
→ Redirect ke /accounts dengan flash message "Akun berhasil ditambahkan"
```

### 5.2. Admin Mengedit Akun

```
Admin klik "Edit" pada baris akun → Dialog modal terbuka (prefilled)
→ Ubah data → Submit → Validasi (UpdateAccountRequest) → Update DB + Sync Role
→ Redirect dengan flash message "Akun berhasil diperbarui"
```

### 5.3. Admin Menghapus Akun

```
Admin klik "Hapus" → Dialog konfirmasi muncul → Konfirmasi
→ Backend cek (bukan self-delete?) → Hapus dari DB → Redirect dengan flash message
```

### 5.4. Admin Reset Password User

```
Admin klik "Reset Password" → Dialog modal → Isi password baru + konfirmasi
→ Submit → Validasi → Update password di DB → Redirect dengan flash message
```

### 5.5. User Ganti Password Sendiri

```
User klik menu profil → "Ganti Password" → Dialog/halaman muncul
→ Isi password lama + baru + konfirmasi → Submit → Validasi (cek password lama)
→ Update password → Redirect dengan flash message "Password berhasil diubah"
```

---

## 6. Keamanan & Validasi

| Aspek                      | Implementasi                                               |
| -------------------------- | ---------------------------------------------------------- |
| **Otorisasi**              | Middleware `role:admin` untuk semua route CRUD akun        |
| **Self-delete Prevention** | Controller menolak jika `$user->id === auth()->id()`       |
| **Password Hashing**       | Otomatis via `'password' => 'hashed'` cast pada model User |
| **Rate Limiting**          | Opsional — throttle pada endpoint ganti password           |
| **CSRF**                   | Otomatis via `@csrf` di semua form Blade                   |
| **Validasi Input**         | Menggunakan Form Request classes untuk setiap operasi      |
| **Unique Email**           | Validasi `unique:users,email` dengan exception untuk edit  |

---

## 7. Rencana Pengujian

### 7.1. Unit/Feature Test (Pest)

#### [BARU] `tests/Feature/AccountManagementTest.php`

| Test Case                                         | Deskripsi                        |
| ------------------------------------------------- | -------------------------------- |
| `test_admin_can_view_accounts_page`               | Admin bisa akses `/accounts`     |
| `test_pimpinan_cannot_view_accounts_page`         | Pimpinan mendapat 403            |
| `test_admin_can_create_account`                   | Buat akun baru dengan data valid |
| `test_create_account_validates_required_fields`   | Validasi field wajib             |
| `test_create_account_validates_unique_email`      | Email duplikat ditolak           |
| `test_admin_can_update_account`                   | Edit akun berhasil               |
| `test_admin_can_delete_account`                   | Hapus akun berhasil              |
| `test_admin_cannot_delete_self`                   | Tidak bisa hapus akun sendiri    |
| `test_admin_can_reset_user_password`              | Reset password berhasil          |
| `test_user_can_change_own_password`               | Ganti password sendiri berhasil  |
| `test_change_password_validates_current_password` | Password lama salah ditolak      |

### 7.2. Manual Testing

- [ ] Akses halaman `/accounts` sebagai admin → tampil daftar akun
- [ ] Akses halaman `/accounts` sebagai pimpinan → ditolak (403)
- [ ] Tambah akun baru → muncul di tabel
- [ ] Edit akun → data berubah
- [ ] Hapus akun lain → berhasil
- [ ] Hapus akun sendiri → gagal dengan pesan error
- [ ] Reset password user lain → user bisa login dengan password baru
- [ ] Ganti password sendiri → berhasil login dengan password baru
- [ ] Ganti password sendiri dengan password lama salah → ditolak

---

## 8. Urutan Implementasi (Task Breakdown)

### Tahap 1: Backend Foundation

1. Buat `StoreAccountRequest.php`
2. Buat `UpdateAccountRequest.php`
3. Buat `ResetPasswordRequest.php`
4. Buat `ChangePasswordRequest.php`
5. Buat `AccountController.php` dengan semua method
6. Tambahkan route di `web.php`
7. Update `RoleAndPermissionSeeder.php` (tambah permission `manage users`)

### Tahap 2: Frontend — Halaman Daftar Akun

8. Buat `resources/views/pages/admin/accounts.blade.php`
9. Buat `resources/views/components/accounts/table.blade.php`
10. Tambahkan menu di `side-bar.blade.php`

### Tahap 3: Frontend — Dialog & Form

11. Tambahkan dialog modal "Tambah Akun" di halaman accounts
12. Tambahkan dialog modal "Edit Akun" di halaman accounts
13. Tambahkan dialog konfirmasi "Hapus Akun"
14. Tambahkan dialog modal "Reset Password"

### Tahap 4: Ganti Password Sendiri

15. Modifikasi `top-bar.blade.php` — aktifkan menu profil + tambah "Ganti Password"
16. Buat dialog/halaman ganti password sendiri

### Tahap 5: Testing & Polish

17. Buat `tests/Feature/AccountManagementTest.php`
18. Jalankan test dan perbaiki bug
19. Format kode dengan Pint

---

## 9. Estimasi Waktu

| Tahap                           | Estimasi       |
| ------------------------------- | -------------- |
| Tahap 1: Backend Foundation     | ~45 menit      |
| Tahap 2: Frontend Daftar Akun   | ~30 menit      |
| Tahap 3: Frontend Dialog & Form | ~45 menit      |
| Tahap 4: Ganti Password Sendiri | ~20 menit      |
| Tahap 5: Testing & Polish       | ~30 menit      |
| **Total**                       | **~2.5–3 jam** |

---

## 10. Pertanyaan & Keputusan yang Perlu Dikonfirmasi

> [!IMPORTANT]
> Mohon konfirmasi poin-poin berikut sebelum implementasi dimulai:

1. **Role tambahan?** — Saat ini hanya ada `admin` dan `pimpinan`. Apakah perlu menambahkan role lain (misalnya `operator`)?

=> tak perlu role tambahan, cukup admin dan pimpinan.

2. **Ganti Password UI** — Untuk fitur ganti password sendiri, apakah lebih disukai:
    - **(A)** Dialog modal di top-bar (lebih cepat diakses), atau
    - **(B)** Halaman terpisah `/account/password` (lebih luas untuk fitur profil ke depannya)?

=> Dialog modal sudah cukup, tidak perlu halaman terpisah.

3. **Self-edit restriction** — Apakah admin boleh mengubah role-nya sendiri? (Bisa berbahaya jika admin tidak sengaja mengubah role sendiri menjadi pimpinan)

=> admin tidak boleh mengubah role-nya sendiri

4. **Minimum akun admin** — Apakah perlu validasi agar minimal selalu ada 1 akun admin di sistem (mencegah sistem terkunci)?

=> iya, perlu validasi agar minimal selalu ada 1 akun admin di sistem (mencegah sistem terkunci).
