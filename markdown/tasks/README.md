# Dokumentasi Implementasi Revisi Sistem Manifest Software

Dokumentasi ini merangkum seluruh pekerjaan revisi sistem manifest software berbasis peran (Admin, Kepala Lab / PJ Lab, dan Pimpinan) yang diselesaikan dalam 5 Sprint (Task 01 – Task 30).

---

## 1. Ringkasan Arsitektur & Perubahan

Revisi sistem berfokus pada pemisahan tanggung jawab inventarisasi perangkat lunak dan kepatuhan lisensi:
1. **Admin**: Mengelola data master, mengunduh scanner berkonfigurasi lab, memantau seluruh komputer kampus, dan mengirimkan draft laporan audit ke Penanggung Jawab Laboratorium.
2. **Kepala Lab / PJ Lab**: Memantau inventaris komputer dan software di laboratorium masing-masing, melakukan verifikasi/evaluasi draft laporan, dan memberikan persetujuan (*Approve*) atau penolakan (*Reject*).
3. **Pimpinan**: Mengakses dashboard eksekutif dan laporan audit kepatuhan yang hanya bersumber dari laboratorium yang telah disetujui (*Approved*) oleh PJ Lab pada periode berjalan.

---

## 2. Rincian Pengerjaan per Sprint

### Sprint 1 — Fondasi & Skema Database (Task 01 – 10)
- **Migrasi Database:**
  - Tabel `laboratories` (nama lab, kode lab, deskripsi, lokasi, status aktif).
  - Foreign key `laboratory_id` pada tabel `computers` dan tabel `users` (nullable, `onDelete('set null')`).
  - Tabel `report_approvals` (relasi lab, periode Y-m, tipe laporan, status `pending`/`approved`/`rejected`, catatan evaluasi, reviewer ID, timestamp verifikasi).
- **Model Eloquent:**
  - Model `Laboratory` dengan relasi `computers()`, `users()`, dan `reportApprovals()`.
  - Model `ReportApproval` dengan relasi `laboratory()` dan `reviewer()`.
  - Penambahan relasi `laboratory()` pada Model `Computer` dan `User`.
- **Seeder & RBAC:**
  - Pendaftaran role `kepala_lab` pada `RoleAndPermissionSeeder`.
  - Seeder default akun PJ Lab dan laboratorium percontohan.

### Sprint 2 — Manajemen Laboratorium & Akun (Task 11 – 14)
- **CRUD Laboratorium:**
  - `LaboratoryController` untuk pengelolaan data laboratorium lengkap dengan validasi kode unik dan pencarian.
- **Manajemen Akun PJ Lab:**
  - Pembaruan `AccountController`: penugasan role `kepala_lab` dan pemilihan laboratorium yang dikelola.
- **Navigasi Dinamis:**
  - Pembaruan `side-bar.blade.php` dengan menu terpisah per role (`admin`, `kepala_lab`, `pimpinan`).
- **Pengujian:** `LaboratoryManagementTest` & `AccountKepalaLabTest`.

### Sprint 3 — Integrasi Scanner & Registrasi Perangkat (Task 15 – 18)
- **Distribusi Scanner:**
  - Pembaruan `AgentDownloadController`: formulir pemilihan laboratorium sebelum mengunduh scanner; file `config.json` di-generate dinamis memuat `laboratoryId`.
- **Registrasi API:**
  - Pembaruan `AgentRegisterController`: validasi dan penyimpanan `laboratory_id` saat komputer pertama kali mendaftar via API.
- **Skrip Pemindai Client:**
  - Pembaruan `script/agent/scanner.ps1`: membaca `laboratoryId` dari `config.json` dan menyertakannya pada payload JSON registrasi.
- **Pengujian:** `AgentLabRegistrationTest`.

### Sprint 4 — Fitur PJ Lab & Alur Pengiriman/Persetujuan (Task 19 – 24)
- **Pengiriman Laporan oleh Admin:**
  - `ReportSubmissionController`: Admin mengirimkan permintaan verifikasi laporan periodik ke PJ Lab per laboratorium.
- **Inventaris Khusus PJ Lab:**
  - `LabInventoryController`: PJ Lab hanya melihat komputer dan software yang berada di laboratorium binaannya.
- **Review & Persetujuan Laporan:**
  - `ReportApprovalController`: Halaman evaluasi laporan, preview PDF inline di browser, aksi setujui (*Approve*) atau tolak (*Reject*) disertai catatan.
- **Dashboard Terisolasi:**
  - Pembaruan `DashboardController`: dashboard khusus `kepala_lab` (metrik lab sendiri) dan `pimpinan` (metrik lab approved).
- **Pengujian:** `ReportSubmissionTest`, `KepalaLabRoleTest`, `ReportApprovalTest`, dan `LabScopedDashboardTest`.

### Sprint 5 — Integrasi Pimpinan, Metadata Audit & Polishing (Task 25 – 30)
- **Filter Laporan Pimpinan Berbasis Approval:**
  - `ReportController`: Ringkasan Eksekutif, Komputer, Software, dan Kepatuhan hanya menampilkan data dari lab berstatus `approved` saat diakses oleh `pimpinan`.
  - Pesan informatif (*empty state*) jika belum ada laboratorium yang disetujui pada periode yang dipilih.
- **Audit Trail & Metadata Persetujuan:**
  - Ekspor PDF (`eksekutif-pdf`, `komputer-pdf`, `software-pdf`, `kepatuhan-pdf`, `lisensi-pdf`) menyertakan tabel status verifikasi PJ Lab.
  - Ekspor Excel (`KomputerExport`, `SoftwareExport`, `KepatuhanExport`, `LisensiExport`) menyertakan baris metadata persetujuan penanggung jawab laboratorium.
- **Scoping Audit Kepatuhan:**
  - `ComplianceDataController`: agregasi dan cache key dibedakan berdasarkan role (`admin`, `kepala_lab`, `pimpinan`).
- **Penyempurnaan Tampilan Komputer:**
  - Menampilkan nama dan kode laboratorium pada tabel komputer, detail komputer, serta filter dropdown laboratorium.
- **Revisi Narasi UI:**
  - Standardisasi istilah teknis dari "Agen Scanner" menjadi "Tools Pemindai" / "Scanner".
- **Pengujian:** `PimpinanApprovedReportsTest`.

---

## 3. Hasil Verifikasi & Testing

Seluruh unit kerja telah diverifikasi melalui pengujian otomatis Pest framework:
- **Total Tests:** 152 passed
- **Total Assertions:** 524 assertions
- **Regresi:** 0 failures
- **Code Style:** Standar Laravel Pint (Passed)
