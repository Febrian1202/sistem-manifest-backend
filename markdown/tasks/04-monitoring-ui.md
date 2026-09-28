# Phase 4 — Monitoring UI (Halaman Baru)

**Prioritas:** P1 PENTING
**Estimasi:** 3-4 hari kerja
**Depends on:** Phase 2 (Backend Pipeline), Phase 3 (Multi-Lab Access)
**Referensi dokumen:** Bagian 12, 13

---

## Konteks

Saat ini belum ada halaman khusus untuk melihat riwayat monitoring, histori scan per komputer, dan perubahan software dari waktu ke waktu. Halaman-halaman ini diperlukan agar:

- Admin/PJ Lab bisa melihat aktivitas scan terkini
- Perubahan software bisa dilacak antar-scan
- Kegagalan scan terdeteksi
- Data historis bisa ditelusuri untuk keperluan penelitian

### Halaman yang perlu dibuat:

```
/monitoring               → Riwayat semua scan session
/monitoring/{session}     → Detail satu scan session
/computers/{id}/history   → Histori scan per komputer
/monitoring/changes       → Log perubahan software
/monitoring/compliance    → Histori compliance
```

---

## Task

### 4.1 Halaman Riwayat Monitoring (Scan Sessions)

**Route:** `GET /monitoring`
**Controller:** `MonitoringController@index` (baru)
**View:** `resources/views/monitoring/index.blade.php`
**Akses:** admin, kepala_lab, pimpinan

**Filter yang tersedia:**

| Filter | Tipe | Keterangan |
|--------|------|------------|
| Periode (dari-sampai) | date range | Filter berdasarkan `started_at` |
| Laboratorium | select | Daftar lab (kepala_lab: hanya lab-nya) |
| Komputer | select/search | Filter per komputer |
| Status scan | select | pending, running, completed, failed, partial |
| Trigger | select | scheduled, manual, on_demand |

**Kolom tabel:**

| Kolom | Sumber Data |
|-------|-------------|
| Tanggal | `scan_sessions.started_at` |
| Laboratorium | `computer.laboratory.name` |
| Komputer | `computer.hostname` |
| Waktu Mulai | `scan_sessions.started_at` |
| Waktu Selesai | `scan_sessions.completed_at` |
| Durasi | Hitung dari `started_at` dan `completed_at` |
| Jumlah Software | `scan_sessions.software_count` |
| Status | `scan_sessions.status` (badge warna) |

**Fitur tambahan:**
- Pagination
- Sort per kolom
- Badge warna untuk status: hijau=completed, merah=failed, kuning=partial, abu=pending
- Link ke detail scan session
- Scope lab otomatis untuk kepala_lab

**Perintah untuk membuat:**
```bash
php artisan make:controller MonitoringController --no-interaction
```

---

### 4.2 Halaman Detail Scan Session

**Route:** `GET /monitoring/{scanSession}`
**Controller:** `MonitoringController@show`
**View:** `resources/views/monitoring/show.blade.php`
**Akses:** admin, kepala_lab (lab-nya), pimpinan

**Informasi yang ditampilkan:**

**Header:**
```
Scan Session #9821
Komputer: LAB-FTI-001 (Lab FTI)
Status: Completed
Mulai: 2026-10-01 08:00:00
Selesai: 2026-10-01 08:02:13
Durasi: 2 menit 13 detik
Trigger: Scheduled
Agent Version: 1.2.0
Software Ditemukan: 45
```

**Tab 1 — Daftar Software:**
Tabel semua `scan_software_results` untuk session ini:

| Kolom | Data |
|-------|------|
| Nama Software | `raw_name` |
| Versi | `version` |
| Vendor | `vendor` |
| Tanggal Install | `install_date` |
| Katalog | `catalog.normalized_name` (jika ter-match) |
| Kategori | `catalog.category` |

**Tab 2 — Status Compliance:**
Tabel semua `compliance_snapshots` untuk session ini:

| Kolom | Data |
|-------|------|
| Software | `software_name` |
| Versi | `software_version` |
| Status | `status` (badge warna) |
| Keterangan | `keterangan` |

**Tab 3 — Perubahan dari Scan Sebelumnya:**
Perbandingan dengan scan sebelumnya (jika ada):
- Software baru (ada di scan ini, tidak ada di scan sebelumnya)
- Software hilang (ada di scan sebelumnya, tidak ada di scan ini)
- Versi berubah
- Status compliance berubah

---

### 4.3 Halaman Histori Komputer

**Route:** `GET /computers/{computer}/history`
**Controller:** `ComputerDataController@history` (method baru) atau `MonitoringController@computerHistory`
**View:** `resources/views/monitoring/computer-history.blade.php`
**Akses:** admin, kepala_lab (lab-nya), pimpinan

**Tampilan:**

```
PC-01 — Lab FTI — Histori Monitoring

Timeline / Daftar Scan:
──────────────────────
01 Oktober 2026 (Scan #101 — Completed)
  ✓ Google Chrome 140
  ✓ Microsoft Office 2021
  ✓ VS Code 1.105

01 November 2026 (Scan #102 — Completed)
  ✓ Google Chrome 141
  ✓ VS Code 1.105
  ✗ Microsoft Office 2021 ← HILANG

01 Desember 2026 (Scan #103 — Completed)
  ✓ Google Chrome 142
  ✓ Microsoft Office 2021 ← KEMBALI
  ✓ VS Code 1.106 ← UPGRADE
```

**Filter:**
- Periode (dari-sampai)
- Status scan

**Fitur:**
- Timeline kronologis scan sessions
- Setiap scan menampilkan daftar software
- Highlight perubahan: software baru (hijau), hilang (merah), versi berubah (kuning)

---

### 4.4 Halaman Perubahan Software (Change Log)

**Route:** `GET /monitoring/changes`
**Controller:** `MonitoringController@changes`
**View:** `resources/views/monitoring/changes.blade.php`
**Akses:** admin, kepala_lab (lab-nya), pimpinan

**Tujuan:** Menampilkan semua perubahan software yang terdeteksi dari perbandingan antar-scan.

**Tipe perubahan yang dideteksi:**

| Tipe | Deteksi | Ikon |
|------|---------|------|
| Software Baru | Ada di scan N, tidak ada di scan N-1 | + (hijau) |
| Software Hilang | Ada di scan N-1, tidak ada di scan N | - (merah) |
| Versi Berubah | Nama sama, versi berbeda antar scan | ↑ (kuning) |
| Software Kembali | Ada di scan N, tidak ada di scan N-1, tapi ada di scan sebelumnya | ↺ (biru) |

**Filter:**
- Periode
- Laboratorium
- Komputer
- Tipe perubahan

**Kolom tabel:**

| Kolom | Data |
|-------|------|
| Tanggal | Waktu scan |
| Laboratorium | Nama lab |
| Komputer | Hostname |
| Software | Nama software |
| Tipe Perubahan | Baru/Hilang/Upgrade/Kembali |
| Detail | Versi lama → Versi baru (untuk upgrade) |

**Catatan implementasi:**
Perubahan dideteksi dengan membandingkan `scan_software_results` antara dua scan berurutan untuk komputer yang sama. Bisa dihitung on-the-fly atau disimpan di tabel terpisah (`software_changes`) jika performa menjadi masalah.

**Service yang mungkin perlu dibuat:**
```
app/Services/SoftwareChangeDetectionService.php
```

Logic:
```
1. Ambil 2 scan berurutan untuk 1 komputer
2. Bandingkan daftar software (by raw_name)
3. Yang ada di scan baru tapi tidak di scan lama = BARU
4. Yang ada di scan lama tapi tidak di scan baru = HILANG
5. Yang ada di keduanya tapi versi berbeda = UPGRADE/DOWNGRADE
```

---

### 4.5 Halaman Compliance History

**Route:** `GET /monitoring/compliance`
**Controller:** `MonitoringController@compliance`
**View:** `resources/views/monitoring/compliance.blade.php`
**Akses:** admin, kepala_lab (lab-nya), pimpinan

**Tujuan:** Menampilkan perubahan status compliance dari waktu ke waktu.

**Filter:**
- Periode
- Laboratorium
- Komputer
- Status compliance

**Kolom tabel:**

| Kolom | Data |
|-------|------|
| Tanggal Scan | `compliance_snapshots.scanned_at` |
| Laboratorium | Via computer → laboratory |
| Komputer | Hostname |
| Software | `software_name` |
| Versi | `software_version` |
| Status | Badge warna |
| Keterangan | `keterangan` |

**Highlight perubahan status:**
- Jika status berubah dari scan sebelumnya, tampilkan indikator (misal: "Tidak Berlisensi → Berlisensi")

---

### 4.6 Tambah Route & Menu Navigasi

**File:** `routes/web.php`

Tambahkan route group:
```php
Route::middleware(['auth', 'role:admin|kepala_lab|pimpinan'])->group(function () {
    Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
    Route::get('/monitoring/changes', [MonitoringController::class, 'changes'])->name('monitoring.changes');
    Route::get('/monitoring/compliance', [MonitoringController::class, 'compliance'])->name('monitoring.compliance');
    Route::get('/monitoring/{scanSession}', [MonitoringController::class, 'show'])->name('monitoring.show');
});

// Histori komputer (tambah ke route computers yang ada)
Route::get('/computers/{computer}/history', [ComputerDataController::class, 'history'])
    ->name('computers.history');
```

**Menu navigasi (sidebar):**
Tambahkan section "Monitoring" di sidebar layout:
```
📊 Dashboard
📋 Monitoring           ← BARU
   ├── Riwayat Scan     ← BARU
   ├── Perubahan Software ← BARU
   └── Histori Compliance  ← BARU
💻 Komputer
📦 Software
🔑 Lisensi
✅ Compliance
📄 Laporan
```

**File layout yang perlu diubah:**
- Cek sidebar/nav component di `resources/views/components/` atau layout utama

---

## Kriteria Selesai Phase 4

- [ ] Halaman riwayat monitoring menampilkan daftar scan sessions dengan filter
- [ ] Detail scan session menampilkan software + compliance untuk scan tersebut
- [ ] Histori komputer menampilkan timeline scan dengan perubahan software
- [ ] Perubahan software (baru/hilang/upgrade) terdeteksi dan ditampilkan
- [ ] Compliance history menampilkan perubahan status dari waktu ke waktu
- [ ] Semua halaman ter-scope berdasarkan role (kepala_lab hanya lab-nya)
- [ ] Route dan menu navigasi terintegrasi
- [ ] Pagination dan sorting berfungsi
- [ ] `vendor/bin/pint --dirty` clean

---

## File yang Akan Dibuat

```
app/Http/Controllers/MonitoringController.php
app/Services/SoftwareChangeDetectionService.php (opsional)
resources/views/monitoring/index.blade.php
resources/views/monitoring/show.blade.php
resources/views/monitoring/computer-history.blade.php
resources/views/monitoring/changes.blade.php
resources/views/monitoring/compliance.blade.php
```

## File yang Akan Diubah

```
routes/web.php                                    — tambah route monitoring
app/Http/Controllers/ComputerDataController.php   — tambah method history
resources/views/components/...                    — tambah menu sidebar
```
