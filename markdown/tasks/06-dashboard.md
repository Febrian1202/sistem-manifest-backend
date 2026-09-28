# Phase 6 — Dashboard Enhancement

**Prioritas:** P1-P2 (statistik dasar P1, trend chart P2)
**Estimasi:** 1-2 hari kerja
**Depends on:** Phase 2 (Backend Pipeline), Phase 4 (Monitoring UI)
**Referensi dokumen:** Bagian 13

---

## Konteks

Dashboard saat ini kemungkinan menampilkan statistik berbasis current state (jumlah komputer, software, compliance). Setelah revisi, dashboard harus mencerminkan **monitoring berkala**, bukan hanya inventory statis.

Dashboard harus bisa menjawab pertanyaan:
- Berapa komputer yang sudah di-scan hari ini?
- Berapa scan yang gagal?
- Bagaimana tren compliance minggu ini vs minggu lalu?
- Lab mana yang paling banyak temuan?

---

## Task

### 6.1 Statistik Monitoring (Card/Widget)

**File:** `app/Http/Controllers/DashboardController.php` + view dashboard

**Tambahkan statistik berikut:**

| Statistik | Query | Keterangan |
|-----------|-------|------------|
| Total Laboratorium | `Laboratory::count()` | Semua lab terdaftar |
| Total Komputer | `Computer::active()->forUserLab()->count()` | Hanya komputer aktif |
| Komputer Aktif (Scan Hari Ini) | Komputer yang punya scan hari ini | `scan_sessions` WHERE started_at = today |
| Komputer Belum Scan | Komputer aktif yang belum scan hari ini | Total - Aktif |
| Scan Berhasil (Hari Ini) | `scan_sessions` WHERE status=completed AND today | |
| Scan Gagal (Hari Ini) | `scan_sessions` WHERE status=failed AND today | |
| Software Teridentifikasi | `ScanSoftwareResult` distinct raw_name dari scan terbaru | Atau dari `software_catalogs` |
| Temuan Perlu Ditinjau | `compliance_snapshots` WHERE status='Perlu Ditinjau' dari scan terbaru | |

**Implementasi:**
```php
// Di DashboardController
$stats = [
    'total_labs' => Laboratory::count(),
    'total_computers' => Computer::active()->forUserLab()->count(),
    'scanned_today' => ScanSession::forUserLab()
        ->whereDate('started_at', today())
        ->where('status', 'completed')
        ->distinct('computer_id')
        ->count('computer_id'),
    // ... dst
];
```

**Scope:** `kepala_lab` hanya melihat statistik lab-nya. `admin`/`pimpinan` melihat keseluruhan.

---

### 6.2 Trend Chart

**Tujuan:** Grafik visual tren monitoring dan compliance.

**Chart yang perlu ditampilkan:**

**Chart 1 — Jumlah Scan per Hari (7-30 hari terakhir)**
```
Tipe: Bar chart / Line chart
X-axis: Tanggal
Y-axis: Jumlah scan
Series: Berhasil (hijau), Gagal (merah)
```

**Chart 2 — Persentase Keberhasilan Scan**
```
Tipe: Line chart
X-axis: Tanggal/Minggu
Y-axis: Persentase (0-100%)
```

**Chart 3 — Software Baru vs Hilang per Minggu**
```
Tipe: Bar chart (stacked)
X-axis: Minggu
Y-axis: Jumlah
Series: Baru (hijau), Hilang (merah)
```

**Chart 4 — Trend Status Compliance**
```
Tipe: Stacked area chart / Line chart
X-axis: Tanggal/Minggu
Y-axis: Jumlah
Series: Berlisensi, Tidak Berlisensi, Grace Period, Perlu Ditinjau
```

**Implementasi:**
- Gunakan library chart yang sudah ada di project (cek apakah sudah pakai Chart.js, ApexCharts, atau lainnya)
- Jika belum ada, rekomendasi: **Chart.js** (ringan, sudah umum di Laravel/Blade)
- Data di-fetch via AJAX endpoint atau langsung di-pass ke view

**Data endpoint (jika AJAX):**
```php
Route::get('/dashboard/chart-data', [DashboardController::class, 'chartData'])
    ->name('dashboard.chart-data');
```

Response format:
```json
{
    "scan_trend": {
        "labels": ["2026-10-01", "2026-10-02", ...],
        "completed": [45, 42, 48, ...],
        "failed": [2, 5, 1, ...]
    },
    "compliance_trend": {
        "labels": ["Week 1", "Week 2", ...],
        "berlisensi": [120, 125, ...],
        "tidak_berlisensi": [15, 12, ...],
        "grace_period": [3, 2, ...],
        "perlu_ditinjau": [8, 5, ...]
    }
}
```

---

### 6.3 Filter Global Dashboard

**Tujuan:** Dashboard bisa difilter agar statistik dan chart berubah sesuai scope yang dipilih.

**Filter yang tersedia:**

| Filter | Tipe | Default |
|--------|------|---------|
| Periode | Date range atau preset (7 hari, 30 hari, 3 bulan) | 7 hari terakhir |
| Laboratorium | Select | Semua (admin), Lab sendiri (kepala_lab) |
| Fakultas | Select | Semua (jika ada entity fakultas) |

**Implementasi:**
- Filter via query parameter (`?period=7d&lab=1`)
- Atau via form submit (GET request)
- Pastikan state filter terpertahankan saat refresh

**Catatan:** Untuk `kepala_lab`, filter laboratorium tidak ditampilkan (otomatis lab-nya sendiri).

---

### 6.4 Dashboard Per Role

**Tujuan:** Setiap role melihat dashboard yang sesuai kebutuhannya.

**Admin:**
```
Statistik lintas lab
Chart trend semua lab
Quick actions: manage computers, manage labs, view reports
Tabel: komputer yang belum scan hari ini
Tabel: temuan compliance terbaru
```

**Kepala Lab:**
```
Statistik lab sendiri
Chart trend lab sendiri
Quick actions: view inventory, review reports
Tabel: komputer lab yang belum scan hari ini
Tabel: temuan compliance lab
```

**Pimpinan:**
```
Statistik ringkas seluruh universitas
Chart trend lintas lab
Perbandingan antar-lab (compliance rate per lab)
Link ke report
```

**Implementasi:**
Bisa menggunakan satu controller dengan logic per role, atau partial blade yang berbeda per role.

---

## Kriteria Selesai Phase 6

- [ ] Dashboard menampilkan statistik monitoring (scan hari ini, gagal, dll)
- [ ] Minimal 2 chart trend berfungsi (scan trend + compliance trend)
- [ ] Dashboard ter-scope per role (kepala_lab hanya lab-nya)
- [ ] Filter periode berfungsi
- [ ] Dashboard merespons data real-time dari `scan_sessions` dan `compliance_snapshots`
- [ ] `vendor/bin/pint --dirty` clean

---

## File yang Akan Diubah

```
app/Http/Controllers/DashboardController.php
resources/views/dashboard/... (semua view dashboard)
```

## File yang Mungkin Ditambah

```
resources/views/dashboard/partials/monitoring-stats.blade.php
resources/views/dashboard/partials/trend-charts.blade.php
routes/web.php (jika perlu endpoint chart data)
```

## Dependencies External

- Chart.js atau library chart lain (cek apakah sudah terinstall di `package.json`)
- Jika belum ada: `npm install chart.js`
