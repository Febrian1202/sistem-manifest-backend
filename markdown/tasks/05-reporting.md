# Phase 5 — Reporting & Export

**Prioritas:** P0-P1 (campuran: report dasar P0, report lanjutan P1)
**Estimasi:** 2-3 hari kerja
**Depends on:** Phase 2 (Backend Pipeline), Phase 3 (Multi-Lab Access)
**Referensi dokumen:** Bagian 14, 15, 16, 32

---

## Konteks

Report saat ini berbasis **current state** (`software_discoveries`, `compliance_reports`). Setelah revisi, report harus bisa menggunakan **data historis** (`scan_sessions`, `scan_software_results`, `compliance_snapshots`) dan difilter berdasarkan **periode** dan **laboratorium**.

### Jenis Report yang Sudah Ada

Berdasarkan route yang ada di `routes/web.php`:
- Report Eksekutif
- Report Komputer
- Report Software
- Report Kepatuhan
- Report Lisensi

Masing-masing memiliki endpoint preview dan export (PDF/Excel).

### Jenis Report yang Perlu Ditambah/Diubah

```
EXISTING (ubah sumber data agar support histori + periode):
  - Report Eksekutif → tambah section monitoring summary
  - Report Komputer → tambah histori scan per komputer
  - Report Software → sumber dari scan_software_results untuk histori
  - Report Kepatuhan → sumber dari compliance_snapshots untuk histori
  - Report Lisensi → tetap dari license_inventories (current state OK)

BARU:
  - Rekap Monitoring → summary scan sessions per periode
  - Rekap Perubahan → software added/removed/upgraded per periode
```

---

## Task

### 5.1 Rekap Monitoring Report (BARU)

**Tujuan:** Laporan ringkasan aktivitas monitoring selama periode tertentu.

**Route:** Tambahkan ke route report yang ada atau buat baru
**Controller:** `ReportController` (method baru)
**Export:** PDF + Excel

**Parameter input:**
- `period_start` (date, wajib)
- `period_end` (date, wajib)
- `laboratory_id` (opsional, scope otomatis untuk kepala_lab)

**Isi report:**

| Section | Data | Sumber |
|---------|------|--------|
| Header | Periode, Lab, Tanggal cetak | Parameter input |
| Summary | Total komputer, Total scan, Berhasil, Gagal, Persentase | `scan_sessions` |
| Per Laboratorium | Nama lab, Jumlah komputer, Jumlah scan, Rate keberhasilan | `scan_sessions` JOIN `computers` JOIN `laboratories` |
| Per Komputer | Hostname, Lab, Jumlah scan, Terakhir scan, Status | `scan_sessions` |

**Query utama:**
```sql
SELECT
    l.name AS lab_name,
    COUNT(DISTINCT c.id) AS total_computers,
    COUNT(ss.id) AS total_scans,
    SUM(CASE WHEN ss.status = 'completed' THEN 1 ELSE 0 END) AS successful_scans,
    SUM(CASE WHEN ss.status = 'failed' THEN 1 ELSE 0 END) AS failed_scans
FROM scan_sessions ss
JOIN computers c ON ss.computer_id = c.id
JOIN laboratories l ON c.laboratory_id = l.id
WHERE ss.started_at BETWEEN ? AND ?
GROUP BY l.id, l.name
```

**File export yang perlu dibuat:**
```
app/Exports/MonitoringRecapExport.php (Excel)
resources/views/exports/monitoring-recap.blade.php (PDF template)
```

---

### 5.2 Rekap Software Report (Ubah sumber data)

**Tujuan:** Laporan software yang terdeteksi selama periode tertentu, berdasarkan data historis.

**Perubahan dari report existing:**
- Sumber lama: `software_discoveries` (current state)
- Sumber baru: `scan_software_results` + `software_catalogs` (historis)

**Parameter input:**
- `period_start`, `period_end`
- `laboratory_id` (opsional)

**Isi report:**

| Kolom | Data |
|-------|------|
| Software | `raw_name` atau `catalog.normalized_name` |
| Kategori | `catalog.category` |
| Vendor | `vendor` |
| Jumlah Komputer | COUNT DISTINCT computer_id via scan_session |
| Versi Terbaru | MAX version |
| Lab | Daftar lab yang memiliki software ini |
| Pertama Terdeteksi | MIN scan date |
| Terakhir Terdeteksi | MAX scan date |

**Scope:** Gunakan `forUserLab()` scope untuk kepala_lab.

---

### 5.3 Rekap Kepatuhan Report (Ubah sumber data)

**Tujuan:** Laporan status kepatuhan lisensi selama periode tertentu.

**Perubahan dari report existing:**
- Sumber lama: `compliance_reports` (current state)
- Sumber baru: `compliance_snapshots` (historis)

**Parameter input:**
- `period_start`, `period_end`
- `laboratory_id` (opsional)

**Isi report:**

**Summary:**

| Status | Jumlah | Persentase |
|--------|--------|------------|
| Berlisensi | X | Y% |
| Tidak Berlisensi | X | Y% |
| Grace Period | X | Y% |
| Perlu Ditinjau | X | Y% |

**Detail per laboratorium:**

| Lab | Berlisensi | Tidak Berlisensi | Grace Period | Perlu Ditinjau |
|-----|-----------|------------------|-------------|---------------|
| Lab FTI | 45 | 3 | 1 | 2 |
| Lab FKIP | 38 | 5 | 0 | 4 |

**Detail per komputer per software:**
Tabel compliance_snapshots yang difilter per periode.

**Catatan:** Ambil snapshot TERAKHIR per software per komputer dalam periode tersebut sebagai status final.

---

### 5.4 Rekap Perubahan Report (BARU)

**Tujuan:** Laporan perubahan software selama periode tertentu.

**Parameter input:**
- `period_start`, `period_end`
- `laboratory_id` (opsional)

**Isi report:**

| Section | Data |
|---------|------|
| Software Baru | Daftar software yang pertama kali muncul dalam periode |
| Software Dihapus | Daftar software yang hilang dalam periode |
| Upgrade/Downgrade | Software yang versinya berubah |
| Perubahan Status Lisensi | Status compliance yang berubah |

**Implementasi:**
Menggunakan `SoftwareChangeDetectionService` dari Phase 4 (Task 4.4).

**File export:**
```
app/Exports/SoftwareChangesExport.php (Excel)
resources/views/exports/software-changes.blade.php (PDF template)
```

---

### 5.5 Ubah Sumber Data Report Existing

**Tujuan:** Report yang sudah ada harus bisa bekerja dalam dua mode:
1. **Current state** — data terkini (default, seperti sekarang)
2. **Historical** — data berdasarkan periode tertentu (baru)

**Perubahan di controller:**

```php
// Sebelum
$data = ComplianceReport::where('computer_id', $computerId)->get();

// Sesudah
if ($request->filled(['period_start', 'period_end'])) {
    // Mode historis — gunakan compliance_snapshots
    $data = ComplianceSnapshot::forUserLab()
        ->whereBetween('scanned_at', [$request->period_start, $request->period_end])
        ->get();
} else {
    // Mode current — tetap gunakan compliance_reports
    $data = ComplianceReport::forUserLab()->get();
}
```

**Report yang perlu diubah:**

| Report | File | Perubahan |
|--------|------|-----------|
| Report Eksekutif | `ReportController` + export | Tambah summary monitoring |
| Report Komputer | `ReportController` + export | Tambah histori scan |
| Report Software | `ReportController` + export | Dual source (current/historical) |
| Report Kepatuhan | `ReportController` + export | Dual source (current/historical) |
| Report Lisensi | `ReportController` + export | Minimal change (lisensi = current state) |

**JANGAN gunakan** `software_discoveries.created_at` sebagai sumber waktu monitoring. Gunakan `scan_sessions.started_at`.

---

### 5.6 Report Approval Multi-Lab

**Tujuan:** Perbaiki report approval agar mendukung multi-lab dan periode yang jelas.

**File:** `app/Http/Controllers/ReportApprovalController.php`

**Perubahan:**

1. **Periode approval harus eksplisit:**
   ```php
   // Sebelum: period = "Oktober 2026" (string)
   // Sesudah: period_start + period_end (date)
   ```
   Sesuai dengan perubahan di Phase 1 (Task 1.7)

2. **Scope approval:**
   - `kepala_lab` hanya bisa approve report untuk lab-nya
   - `admin` bisa approve semua lab
   - Report yang disubmit harus mencantumkan lab + periode

3. **Keputusan desain:**
   Approval bisa per laboratorium ATAU satu approval untuk laporan universitas. Dokumentasikan keputusan ini.

   **Rekomendasi:** Per laboratorium (lebih granular, sesuai prinsip PJ Lab).

---

## Kriteria Selesai Phase 5

- [x] Rekap Monitoring report bisa digenerate (PDF + Excel)
- [x] Report software menggunakan data historis jika filter periode diberikan
- [x] Report kepatuhan menggunakan data historis jika filter periode diberikan
- [x] Rekap Perubahan report bisa digenerate
- [x] Semua report bisa difilter per laboratorium
- [x] Semua report bisa difilter per periode (period_start, period_end)
- [x] Report approval mendukung periode eksplisit
- [x] Scope lab diterapkan konsisten di semua report
- [x] Export PDF/Excel menampilkan data yang benar
- [x] `vendor/bin/pint --dirty` clean

---

## File yang Akan Dibuat

```
app/Exports/MonitoringRecapExport.php
app/Exports/SoftwareChangesExport.php
resources/views/exports/monitoring-recap.blade.php
resources/views/exports/software-changes.blade.php
```

## File yang Akan Diubah

```
app/Http/Controllers/ReportController.php
app/Http/Controllers/ReportApprovalController.php
app/Http/Controllers/ReportSubmissionController.php
app/Exports/... (semua file export yang ada)
resources/views/reports/... (semua view report)
resources/views/exports/... (semua template export)
routes/web.php (tambah route report baru)
```
