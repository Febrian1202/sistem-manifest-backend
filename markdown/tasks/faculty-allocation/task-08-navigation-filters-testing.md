# Task 08: Navigasi, Filter Global, Edge Cases & Pengujian Menyeluruh (Navigation, Filters, Edge Cases & Verification)

## 1. Ringkasan Task
Tahap finalisasi dan penjaminan kualitas (*quality assurance*) yang menyatukan seluruh modul menjadi satu kesatuan sistem yang kohesif:
1. **Penyempurnaan Navigasi Sidebar:** Mengelompokkan modul ke dalam struktur logis (Organisasi, Infrastruktur, Software & Lisensi, Laporan, Pengaturan).
2. **Cascading Filters (Fakultas → Lab):** Memastikan filter bertingkat bekerja mulus pada halaman Data Komputer, Monitoring Scan, dan Laporan.
3. **Penanganan Kasus Ekstrem (*Edge Cases*) & Proteksi Integritas Data:** Foreign key cascade, validasi pembagian nol, software tanpa alokasi, lisensi kedaluwarsa.
4. **Verifikasi Suite Pengujian Pest:** Mengimplementasikan dan menjalankan seluruh skenario pengujian dari dokumen acuan (Test Case 1 s/d 8).
5. **Code Formatting:** Menjalankan Laravel Pint agar memenuhi standar kualitas kode proyek.

- **Status Dependensi:** Bergantung pada seluruh task sebelumnya ([Task 01](./task-01-faculty-entity.md) s/d [Task 07](./task-07-dashboard-reports.md)).
- **Target File yang Dibuat / Diubah:**
  - `resources/views/components/layout/side-bar.blade.php` (Restrukturisasi menu)
  - `app/Http/Controllers/ComputerDataController.php` (Tambahan filter fakultas)
  - `resources/views/pages/admin/computers.blade.php` (Tambahan dropdown filter fakultas)
  - `tests/Feature/FacultyLicenseSystemIntegrationTest.php` (Suite integrasi lengkap)

---

## 2. Rincian Spesifikasi Teknis

### 2.1 Restrukturisasi Sidebar Menu (`side-bar.blade.php`)

Atur ulang seksi menu navigasi agar mencerminkan model organisasi baru:

```blade
{{-- 1. UTAMA --}}
<x-layout.nav-item href="{{ route('dashboard') }}" icon="fa-chart-pie" label="Dashboard" />

{{-- 2. ORGANISASI (Admin & Pimpinan) --}}
@role('admin|pimpinan')
    <div class="px-3 py-2 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
        Organisasi
    </div>
    <x-layout.nav-item href="{{ route('faculties.index') }}" icon="fa-building-columns" label="Fakultas" />
    <x-layout.nav-item href="{{ route('laboratories.index') }}" icon="fa-flask" label="Laboratorium" />
@endrole

{{-- 3. INFRASTRUKTUR & PERANGKAT --}}
@role('admin|pimpinan')
    <div class="px-3 py-2 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
        Infrastruktur
    </div>
    <x-layout.nav-item href="{{ route('computers.index') }}" icon="fa-desktop" label="Data Komputer" />
    <x-layout.nav-item href="{{ route('monitoring.index') }}" icon="fa-wave-square" label="Riwayat Scan" />
    <x-layout.nav-item href="{{ route('monitoring.changes') }}" icon="fa-clock-rotate-left" label="Perubahan Software" />
@endrole

{{-- 4. SOFTWARE & LISENSI --}}
@role('admin|pimpinan')
    <div class="px-3 py-2 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
        Software & Lisensi
    </div>
    <x-layout.nav-item href="{{ route('softwares.index') }}" icon="fa-box-archive" label="Katalog Software" />
    <x-layout.nav-item href="{{ route('licenses.index') }}" icon="fa-key" label="Inventaris Lisensi" />
    @role('admin')
        <x-layout.nav-item href="{{ route('license-allocations.index') }}" icon="fa-diagram-project" label="Alokasi Lisensi" />
    @endrole
    <x-layout.nav-item href="{{ route('compliance.index') }}" icon="fa-shield-halved" label="Audit Kepatuhan" />
@endrole

{{-- 5. LAPORAN & AUDIT --}}
@role('admin|pimpinan')
    <div class="px-3 py-2 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
        Laporan
    </div>
    <x-layout.nav-item href="{{ route('reports.index') }}" icon="fa-file-lines" label="Pusat Laporan" />
    <x-layout.nav-item href="{{ route('reports.kebutuhan-lisensi') }}" icon="fa-file-invoice" label="Analisis Kebutuhan Lisensi" />
    @role('admin')
        <x-layout.nav-item href="{{ route('report-submissions.index') }}" icon="fa-paper-plane" label="Kirim ke PJ Lab" />
    @endrole
@endrole

{{-- 6. MENU PJ & STAFF LAB --}}
@role('kepala_lab')
    <div class="px-3 py-2 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
        Menu PJ Lab
    </div>
    <x-layout.nav-item href="{{ route('lab-inventory.index') }}" icon="fa-boxes-stacked" label="Inventaris Lab" />
    <x-layout.nav-item href="{{ route('report-approvals.index') }}" icon="fa-clipboard-check" label="Review Laporan" />
@endrole

@role('staff_lab')
    <div class="px-3 py-2 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
        Menu Staff Lab
    </div>
    <x-layout.nav-item href="{{ route('lab-inventory.index') }}" icon="fa-desktop" label="Komputer & Aset" />
    <x-layout.nav-item href="{{ route('agent.download') }}" icon="fa-download" label="Download Scanner" />
@endrole

{{-- 7. PENGATURAN SISTEM (Admin Only) --}}
@role('admin')
    <div class="px-3 py-2 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
        Pengaturan
    </div>
    <x-layout.nav-item href="{{ route('accounts.index') }}" icon="fa-users-gear" label="Manajemen Akun" />
    <x-layout.nav-item href="{{ route('activity-logs.index') }}" icon="fa-clipboard-list" label="Log Aktivitas" />
    <x-layout.nav-item href="{{ route('agent.download') }}" icon="fa-download" label="Download Scanner" />
@endrole
```

---

### 2.2 Cascading Filter pada Data Komputer (`computers.blade.php`)

Pada halaman `ComputerDataController::index()`, tambahkan kapabilitas filter berjenjang:
```php
$facultyId = $request->query('faculty_id');
$laboratoryId = $request->query('laboratory_id');

$query = Computer::query()->with(['laboratory.faculty']);

if ($facultyId) {
    $query->whereHas('laboratory', fn ($q) => $q->where('faculty_id', $facultyId));
}

if ($laboratoryId) {
    $query->where('laboratory_id', $laboratoryId);
}
```
Di frontend Blade:
- Menggunakan Alpine.js untuk cascading dropdown: Memilih Fakultas akan memfilter pilihan Laboratorium yang muncul hanya untuk lab di bawah fakultas tersebut.

---

### 2.3 Matriks Penanganan Kasus Ekstrem (*Edge Cases*)

| Kasus Ekstrem | Potensi Masalah | Solusi & Mekanisme Pengamanan |
|---|---|---|
| **Penghapusan Fakultas Berisi Lab** | Data laboratorium menjadi yatim (*orphan*) atau error FK. | Dicegah oleh `FacultyController::destroy()`: return error jika `$faculty->laboratories()->exists()`. |
| **Alokasi Melebihi Kuota Lisensi** | Terjadi over-allocation kepemilikan universitas. | Divalidasi di `StoreLicenseAllocationRequest` & `UpdateLicenseAllocationRequest` via closure validator. |
| **Software Terpasang Tanpa Alokasi** | Pembagian nol pada kalkulasi persentase utilisasi. | Rumus utilisasi memeriksa `Allocated > 0`. Jika 0, kembalikan status `Tanpa Alokasi` dan utilisasi `null`. |
| **Lisensi Induk Kedaluwarsa** | Software masih dianggap berlisensi padahal lisensi expired. | `LicenseComplianceService::getActiveEntitlement()` memfilter `expiry_date >= now()`. Lisensi expired diabaikan dari kuota sah. |
| **Komputer Tidak Pernah Scan / Offline** | Komputer dihitung tanpa software discovery. | Hanya komputer `status = 'active'` yang dihitung dalam populasi audit kepatuhan. |
| **Multiple License Inventory** | Hanya lisensi pertama yang dihitung (`->first()`). | Diselesaikan di Task 05 melalui `SUM(quota_limit)` dari semua baris lisensi aktif. |

---

## 3. Rangkaian Pengujian End-to-End (Pest E2E Suite)

Buat file pengujian komprehensif di `tests/Feature/FacultyLicenseSystemIntegrationTest.php` yang mencakup 8 skenario pengujian utama:

### Test Case 1: Alokasi Lisensi Valid & Sisa Kuota
- Input: Kepemilikan lisensi USN = 50 kursi. Alokasi FTI = 20, Alokasi FKIP = 20.
- Assertion:
  - Total teralokasi = 40.
  - Sisa lisensi unallocated = 10.
  - Status sukses tersimpan.

### Test Case 2: Penolakan Alokasi Melebihi Kepemilikan
- Input: Kepemilikan USN = 50 kursi. Alokasi FTI = 30, coba alokasikan FKIP = 30 (Total 60).
- Assertion:
  - Request validasi gagal dengan error pada atribut `allocated_quota`.
  - Database tidak menyimpan alokasi kedua.

### Test Case 3: Pendeteksian Fakultas Defisit
- Input: Alokasi FTI = 20 seat. Hasil scan menunjukkan 25 komputer di lab FTI menginstall software tersebut.
- Assertion:
  - Defisit FTI = 5.
  - Status kepatuhan FTI = `Defisit`.
  - Tingkat utilisasi = 125%.

### Test Case 4: Pendeteksian Fakultas Surplus
- Input: Alokasi FKIP = 30 seat. Hasil scan menunjukkan 20 komputer terpasang.
- Assertion:
  - Surplus FKIP = 10.
  - Status kepatuhan FKIP = `Surplus`.
  - Tingkat utilisasi = 66.7%.

### Test Case 5: Software Terpasang Tanpa Alokasi
- Input: Alokasi = 0 seat. Terpasang pada 10 komputer di FISIP.
- Assertion:
  - Defisit = 10.
  - Tidak terjadi error pembagian dengan nol (*DivisionByZeroError*).
  - Status = `Tanpa Alokasi (Defisit Penuh)`.

### Test Case 6: Agregasi Multi-Inventory (Eliminasi Bug ->first())
- Input: Office 2019 memiliki 3 PO (Inventory 1 = 20, Inventory 2 = 15, Inventory 3 = 10). Total = 45 seat.
- Assertion:
  - `getActiveEntitlement()` menghasilkan tepat 45.
  - Komputer ke-25 terdeteksi tetap berstatus `Berlisensi` (karena 25 $\le$ 45).

### Test Case 7: Filter Hierarkis Fakultas & Laboratorium
- Input: Komputer terdaftar di Lab FTI dan Lab FKIP.
- Assertion:
  - Filter `faculty_id = FTI` hanya mengembalikan komputer di bawah FTI.
  - Filter kepatuhan FTI hanya mengakumulasi software di komputer FTI.

### Test Case 8: Drill-down & Konsistensi Data Laporan
- Input: Lakukan scan result submission dari agent.
- Assertion:
  - Data mengalir secara benar: `Scan Result → Computer Discovery → Lab Count → Faculty Compliance → Executive Report`.
  - Angka pada preview laporan sama persis dengan angka pada file ekspor PDF/Excel.

---

## 4. Langkah-Langkah Verifikasi Akhir

1. **Jalankan Pest Test Suite:**
   ```bash
   php artisan test --compact
   ```
   Pastikan seluruh test (termasuk test suite eksisting) berstatus **PASS** tanpa kegagalan regresi.
2. **Jalankan Linter & Code Formatter:**
   ```bash
   ./vendor/bin/pint --format agent
   ```
   Pastikan tidak ada pelanggaran standar penulisan kode PHP.
3. **Uji Coba Manual Alur Lengkap:**
   - Login sebagai `admin`: Buat fakultas baru, tautkan ke lab, buat alokasi lisensi.
   - Login sebagai `pimpinan`: Buka dashboard, periksa tabel perbandingan fakultas, download laporan kebutuhan lisensi.
   - Login sebagai `staff_lab`: Pastikan hanya melihat aset lab/fakultas tugasnya dan bisa mengunduh script agent.

---

## 5. Kriteria Keberhasilan (Acceptance Criteria)
- [x] Struktur menu navigasi sidebar tersusun rapi sesuai kategori fungsional.
- [x] Filter hierarkis Fakultas → Laboratorium berfungsi dengan baik.
- [x] Seluruh 8 test case utama lolos pengujian otomatis dengan status hijau.
- [x] Seluruh test suite aplikasi (`php artisan test`) lulus 100%.
- [x] Kode terformat rapi sesuai standar Laravel Pint.
- [x] Sistem siap didemonstrasikan untuk sidang skripsi dengan skenario komparasi alokasi vs instalasi.
