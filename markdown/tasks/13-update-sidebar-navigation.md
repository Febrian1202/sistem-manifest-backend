# Task 13 — Update Sidebar/Navigasi: Menu Per Role

## Sprint: 2 (CRUD Lab & Akun)
## Prioritas: Tinggi
## Dependensi: Sprint 1 selesai
## Estimasi: 1-2 jam

---

## Deskripsi

Update navigasi sidebar agar menampilkan menu yang sesuai dengan role pengguna yang login. Tambah menu baru untuk PJ Lab dan menu "Kirim Laporan" untuk Admin.

## Langkah-langkah

### 1. Cari File Sidebar

Cari file Blade yang berisi navigasi sidebar. Biasanya di:
- `resources/views/layouts/` (app.blade.php atau navigation.blade.php)
- `resources/views/components/` (sidebar.blade.php)

Cari string seperti "Dashboard", "Komputer", "Lisensi" untuk menemukan file yang tepat.

### 2. Struktur Menu Per Role

**Menu Admin (sudah ada, tambah beberapa):**
```
├── Dashboard
├── Data Komputer
├── Data Software
├── Data Lisensi
├── Kepatuhan
├── Laboratorium          ← BARU
├── Laporan
│   ├── Pusat Laporan
│   └── Kirim ke PJ Lab  ← BARU
├── Manajemen Akun
├── Log Aktivitas
└── Download Scanner
```

**Menu Kepala Lab (BARU):**
```
├── Dashboard
├── Inventaris Lab        ← BARU
├── Review Laporan        ← BARU
└── Profil
```

**Menu Pimpinan (sudah ada, tidak berubah):**
```
├── Dashboard
├── Laporan
└── Profil
```

### 3. Implementasi Conditional Menu

Gunakan Blade directive `@role` atau `@hasrole` dari Spatie:

```blade
{{-- Menu untuk semua role --}}
<a href="{{ route('dashboard') }}">Dashboard</a>

{{-- Menu khusus Admin --}}
@role('admin')
    <a href="{{ route('computers.index') }}">Data Komputer</a>
    <a href="{{ route('software.index') }}">Data Software</a>
    <a href="{{ route('licenses.index') }}">Data Lisensi</a>
    <a href="{{ route('compliance.index') }}">Kepatuhan</a>
    <a href="{{ route('laboratories.index') }}">Laboratorium</a>
    <a href="{{ route('reports.index') }}">Pusat Laporan</a>
    <a href="{{ route('report-submissions.index') }}">Kirim ke PJ Lab</a>
    <a href="{{ route('accounts.index') }}">Manajemen Akun</a>
    <a href="{{ route('activity-logs.index') }}">Log Aktivitas</a>
@endrole

{{-- Menu khusus Kepala Lab --}}
@role('kepala_lab')
    <a href="{{ route('lab.inventory.index') }}">Inventaris Lab</a>
    <a href="{{ route('lab.reports.index') }}">Review Laporan</a>
@endrole

{{-- Menu khusus Pimpinan --}}
@role('pimpinan')
    <a href="{{ route('reports.index') }}">Laporan</a>
@endrole
```

### 4. Perhatikan Route Names

Route names untuk menu baru mungkin belum ada. Gunakan placeholder atau pastikan route sudah didaftarkan di Sprint sebelumnya. Sesuaikan nama route dengan konvensi yang sudah digunakan di proyek.

## Referensi
- Lihat sidebar yang sudah ada untuk mengetahui pola HTML/CSS dan konvensi penamaan
- Gunakan icon yang konsisten dengan icon sidebar yang sudah ada
- Pastikan active state (highlight menu aktif) berfungsi untuk menu baru

## File yang Dimodifikasi
- File sidebar/navigasi (cari di `resources/views/layouts/` atau `resources/views/components/`)

## Verifikasi
- [ ] Login sebagai admin → melihat semua menu termasuk "Laboratorium" dan "Kirim ke PJ Lab"
- [ ] Login sebagai kepala_lab → hanya melihat Dashboard, Inventaris Lab, Review Laporan
- [ ] Login sebagai pimpinan → hanya melihat Dashboard dan Laporan
- [ ] Menu yang tidak seharusnya muncul benar-benar tersembunyi (bukan hanya disabled)
- [ ] Active state berfungsi untuk menu baru
- [ ] Responsive (jika sidebar punya mode mobile)
