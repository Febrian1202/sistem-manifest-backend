> **Tingkat Kompleksitas**: Tinggi  
> **Estimasi File**: 16 file baru/modifikasi  
> **Dependensi Terinstall**: `barryvdh/laravel-dompdf`, `maatwebsite/excel`

---

## RINGKASAN

Bangun modul **Reports & Export** lengkap untuk aplikasi Sistem Manifest (Laravel 12).
Modul ini terdiri dari **5 tipe laporan**, masing-masing memiliki:
- Halaman preview (filter + tabel + tombol export)
- Export PDF (semua laporan)
- Export Excel (4 dari 5 laporan)

---

## DAFTAR LAPORAN

| # | Nama Laporan | Slug | PDF | Excel |
|---|-------------|------|-----|-------|
| 1 | Ringkasan Eksekutif | `eksekutif` | ✅ | ❌ |
| 2 | Inventaris Komputer | `komputer` | ✅ | ✅ |
| 3 | Inventaris Software | `software` | ✅ | ✅ |
| 4 | Kepatuhan Lisensi | `kepatuhan` | ✅ | ✅ |
| 5 | Status Lisensi | `lisensi` | ✅ | ✅ |

---

## DAFTAR FILE YANG HARUS DIBUAT/DIMODIFIKASI

Kerjakan **sesuai urutan** berikut:

| # | Aksi | Path File |
|---|------|-----------|
| 1 | MODIFY | `routes/web.php` |
| 2 | MODIFY | `app/Http/Controllers/ReportController.php` |
| 3 | CREATE | `app/Exports/KomputerExport.php` |
| 4 | CREATE | `app/Exports/SoftwareExport.php` |
| 5 | CREATE | `app/Exports/KepatuhanExport.php` |
| 6 | CREATE | `app/Exports/LisensiExport.php` |
| 7 | CREATE | `resources/views/reports/eksekutif.blade.php` |
| 8 | CREATE | `resources/views/reports/komputer.blade.php` |
| 9 | CREATE | `resources/views/reports/software.blade.php` |
| 10 | CREATE | `resources/views/reports/kepatuhan.blade.php` |
| 11 | CREATE | `resources/views/reports/lisensi.blade.php` |
| 12 | CREATE | `resources/views/reports/pdf/eksekutif-pdf.blade.php` |
| 13 | CREATE | `resources/views/reports/pdf/komputer-pdf.blade.php` |
| 14 | CREATE | `resources/views/reports/pdf/software-pdf.blade.php` |
| 15 | CREATE | `resources/views/reports/pdf/kepatuhan-pdf.blade.php` |
| 16 | CREATE | `resources/views/reports/pdf/lisensi-pdf.blade.php` |

---

## REFERENSI SKEMA DATABASE

Sebelum mulai, pahami struktur tabel berikut (detail ada di `markdown/DB.md`):

### Tabel `computers`
Kolom penting: `id`, `hostname`, `ip_address`, `mac_address`, `processor`, `ram_gb`, `os_name`, `last_seen_at`, `created_at`
- Relasi: `hasMany` → `software_discoveries`, `hasMany` → `compliance_reports`

### Tabel `software_catalogs`
Kolom penting: `id`, `normalized_name`, `category` (enum: Freeware/Commercial/OpenSource), `status` (enum: Whitelist/Blacklist/Unreviewed)
- Relasi: `hasMany` → `software_discoveries`, `hasMany` → `license_inventories`

### Tabel `software_discoveries`
Kolom penting: `id`, `computer_id` (FK), `catalog_id` (FK), `raw_name`, `version`, `vendor`, `install_date`, `created_at`
- Relasi: `belongsTo` → `computers`, `belongsTo` → `software_catalogs`

### Tabel `license_inventories`
Kolom penting: `id`, `catalog_id` (FK), `license_key`, `quota_limit`, `purchase_date`, `expiry_date`, `created_at`
- Relasi: `belongsTo` → `software_catalogs`

### Tabel `compliance_reports`
Kolom penting: `id`, `computer_id` (FK), `status` (enum: Safe/Warning/Critical), `total_software_installed`, `unlicensed_count`, `blacklisted_count`, `violation_details` (JSON), `scanned_at`, `created_at`
- Relasi: `belongsTo` → `computers`

---

## TASK 1: MODIFIKASI ROUTES

**File**: `routes/web.php`

**Instruksi**: Tambahkan route group baru di dalam blok `Route::middleware(['auth', 'role:admin|pimpinan'])`. Jangan hapus route `/reports` dan `/reports/export` yang sudah ada.

**Route yang ditambahkan**:

```php
Route::prefix('reports')->name('reports.')->group(function () {
    // Preview pages
    Route::get('/eksekutif',  [ReportController::class, 'showEksekutif'])->name('eksekutif');
    Route::get('/komputer',   [ReportController::class, 'showKomputer'])->name('komputer');
    Route::get('/software',   [ReportController::class, 'showSoftware'])->name('software');
    Route::get('/kepatuhan',  [ReportController::class, 'showKepatuhan'])->name('kepatuhan');
    Route::get('/lisensi',    [ReportController::class, 'showLisensi'])->name('lisensi');

    // Export endpoints
    Route::get('/eksekutif/export',  [ReportController::class, 'exportEksekutif'])->name('eksekutif.export');
    Route::get('/komputer/export',   [ReportController::class, 'exportKomputer'])->name('komputer.export');
    Route::get('/software/export',   [ReportController::class, 'exportSoftware'])->name('software.export');
    Route::get('/kepatuhan/export',  [ReportController::class, 'exportKepatuhan'])->name('kepatuhan.export');
    Route::get('/lisensi/export',    [ReportController::class, 'exportLisensi'])->name('lisensi.export');
});
```

> ⚠️ Pastikan route ini berada di dalam middleware group `['auth', 'role:admin|pimpinan']` yang sudah ada (line 25 pada `web.php` saat ini).

---

## TASK 2: MODIFIKASI REPORT CONTROLLER

**File**: `app/Http/Controllers/ReportController.php`

**Instruksi**: Pertahankan method `index()` dan `export()` yang sudah ada. Tambahkan 10 method baru (5 pasang show+export).

### Aturan Umum Controller

1. **Setiap laporan memiliki sepasang method**: `show{Name}()` untuk preview, `export{Name}()` untuk download
2. **Date filter wajib ada di semua method**:
   - Parameter: `start_date` (nullable, date), `end_date` (nullable, date, after_or_equal:start_date)
   - Default: bulan berjalan jika tidak diisi (`now()->startOfMonth()` s/d `now()->endOfMonth()`)
3. **Export method** harus menentukan format dari query param `?format=pdf` atau `?format=excel`
4. Import model yang dibutuhkan: `Computer`, `SoftwareDiscovery`, `SoftwareCatalog`, `LicenseInventory`, `ComplianceReport`

### Method 2a: `showEksekutif(Request $request)` & `exportEksekutif(Request $request)`

- **Filter column**: `compliance_reports.created_at`
- **Data yang dikumpulkan**:
  1. `total_komputer_aktif` → `Computer::count()`
  2. `total_software_terdeteksi` → `SoftwareDiscovery::count()` (dalam periode)
  3. `persentase_kepatuhan` → (jumlah licensed / total) × 100, 2 desimal
  4. `jumlah_peringatan_kritis` → jumlah software unlicensed
  5. Tabel breakdown: Status (Licensed/Grace Period/Action Required), Jumlah Komputer, Persentase
  6. Top 5 software tidak berlisensi: Nama Software, Jumlah Komputer
- **Export**: PDF only (gunakan `Pdf::loadView('reports.pdf.eksekutif-pdf', $data)->setPaper('a4', 'portrait')`)
- **Preview**: render `reports.eksekutif` dengan data + pagination 15 rows

### Method 2b: `showKomputer(Request $request)` & `exportKomputer(Request $request)`

- **Filter column**: `computers.created_at`
- **Data query**: `Computer::withCount('softwares')` dengan kolom:
  - No, Hostname, IP Address, MAC Address, CPU (`processor`), RAM (`ram_gb`), OS (`os_name`), Status, Last Seen, Jumlah Software
- **Logika "Status"**: Jika `last_seen_at` > 7 hari lalu → "Tidak Aktif", selain itu → "Aktif"
- **Sort**: hostname ASC
- **Export PDF**: orientation **landscape** (`->setPaper('a4', 'landscape')`)
- **Export Excel**: gunakan `KomputerExport` class

### Method 2c: `showSoftware(Request $request)` & `exportSoftware(Request $request)`

- **Filter column**: `software_discoveries.created_at`
- **Data**: Group by software (dari `SoftwareDiscovery` join `SoftwareCatalog`), kolom:
  - No, Nama Software (`normalized_name`), Versi, Jumlah Komputer (count distinct `computer_id`), Status Lisensi, Kategori
- **Logika "Status Lisensi"**: Cek apakah ada record di `license_inventories` melalui `software_catalogs`. Jika tidak ada → "Tidak Berlisensi"
- **Sort**: jumlah komputer DESC
- **Export Excel**: gunakan `SoftwareExport` class

### Method 2d: `showKepatuhan(Request $request)` & `exportKepatuhan(Request $request)`

- **Filter column**: `compliance_reports.created_at`
- **Data**: Join `ComplianceReport` → `Computer` → `SoftwareDiscovery`, kolom:
  - No, Nama Komputer (`hostname`), IP Address, Nama Software, Status, Tanggal Deteksi, Keterangan
- **Logika "Status"**: "Berlisensi", "Grace Period", "Tidak Berlisensi"
- **Logika "Keterangan"**: "Lisensi aktif", "Masa tenggang aktif", "Lisensi tidak ditemukan"
- **Sort**: status (Tidak Berlisensi dulu), lalu hostname ASC
- **Export Excel**: gunakan `KepatuhanExport` class

### Method 2e: `showLisensi(Request $request)` & `exportLisensi(Request $request)`

- **Filter column**: `license_inventories.created_at`
- **Data**: dari `LicenseInventory` join `SoftwareCatalog`, kolom:
  - No, Nama Software, Tipe Lisensi, Total Seat (`quota_limit`), Terpakai (count dari SoftwareDiscovery), Sisa, % Penggunaan, Status, Expired (`expiry_date`)
- **Logika "Terpakai"**: count `SoftwareDiscovery` yang `catalog_id` sama
- **Logika "Sisa"**: `quota_limit - terpakai`
- **Logika "% Penggunaan"**: `(terpakai / quota_limit) × 100`, 1 desimal
- **Logika "Status"**:
  - sisa = 0 → "Penuh"
  - % penggunaan >= 80 → "Hampir Habis"
  - `expiry_date` < hari ini → "Kedaluwarsa"
  - selain itu → "Tersedia"
- **Sort**: % penggunaan DESC
- **Export Excel**: gunakan `LisensiExport` class

---

## TASK 3: BUAT 4 FILE EXPORT EXCEL

Setiap file Export class harus mengimplementasikan interface berikut:
```
FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
```

### Aturan Umum Semua Export Excel

1. **Baris pertama** (heading): bold, background biru muda (`#DBEAFE`)
2. **Sheet title**: nama laporan dalam Bahasa Indonesia
3. **Format tanggal**: `DD/MM/YYYY`
4. **Nama file download**: `{report-slug}_{start_date}_{end_date}.xlsx`
   Contoh: `kepatuhan-lisensi_2025-01-01_2025-01-31.xlsx`
5. Tambahkan **summary row** di baris terakhir jika diminta

### 3a: `app/Exports/KomputerExport.php`

- **Headings**: No, Hostname, IP Address, MAC Address, CPU, RAM, OS, Status, Last Seen, Jumlah Software
- **Sheet title**: "Inventaris Komputer"
- **Highlight**: Baris dengan Status = "Tidak Aktif" → background merah muda (`#FEE2E2`)
- **Summary row**: "Total: X komputer | X aktif | X tidak aktif"

### 3b: `app/Exports/SoftwareExport.php`

- **Headings**: No, Nama Software, Versi, Jumlah Komputer, Status Lisensi, Kategori
- **Sheet title**: "Inventaris Software"
- **Highlight**: Baris dengan Status Lisensi = "Tidak Berlisensi" → background merah muda (`#FEE2E2`)

### 3c: `app/Exports/KepatuhanExport.php`

- **Headings**: No, Nama Komputer, IP Address, Nama Software, Status, Tanggal Deteksi, Keterangan
- **Sheet title**: "Kepatuhan Lisensi"
- **Color-code kolom Status**:
  - "Berlisensi" → hijau (`#DCFCE7`)
  - "Grace Period" → kuning (`#FEF9C3`)
  - "Tidak Berlisensi" → merah (`#FEE2E2`)
- **Summary row**: "Total: X berlisensi | X grace period | X tidak berlisensi"

### 3d: `app/Exports/LisensiExport.php`

- **Headings**: No, Nama Software, Tipe Lisensi, Total Seat, Terpakai, Sisa, % Penggunaan, Status, Expired
- **Sheet title**: "Status Lisensi"
- **Highlight baris**:
  - Status "Penuh" atau "Kedaluwarsa" → merah muda (`#FEE2E2`)
  - Status "Hampir Habis" → kuning muda (`#FEF9C3`)

### Contoh Struktur Export Class

```php
<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KomputerExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected $data;
    protected $startDate;
    protected $endDate;

    public function __construct($data, $startDate, $endDate)
    {
        $this->data = $data;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        // Return collection of rows (termasuk summary row di akhir)
    }

    public function headings(): array
    {
        return ['No', 'Hostname', 'IP Address', /* ... */];
    }

    public function title(): string
    {
        return 'Inventaris Komputer';
    }

    public function styles(Worksheet $sheet)
    {
        // 1. Heading row: bold + background #DBEAFE
        // 2. Conditional row highlighting
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'DBEAFE'],
                ],
            ],
        ];
    }
}
```

---

## TASK 4: BUAT 5 HALAMAN PREVIEW (BLADE)

Setiap halaman preview menggunakan layout: `<x-layout.app>` (komponen layout yang sudah ada).

### Aturan Umum Semua Preview Page

1. **Extends layout**: `<x-layout.app>` — lihat contoh di `resources/views/pages/admin/`
2. **Filter form** di bagian atas:
   - Input `start_date` (type=date)
   - Input `end_date` (type=date)
   - Tombol "Terapkan Filter" (submit)
   - Tombol "Reset" (link ke halaman tanpa parameter)
3. **Tombol export** di kanan atas:
   - "Export PDF" → link ke `route('reports.{slug}.export', ['format' => 'pdf'] + filter params)`, `target="_blank"`
   - "Export Excel" → sama, `format=excel` (hanya untuk laporan yang support Excel)
4. **Info record count**: "Menampilkan X data untuk periode [start] s/d [end]"
5. **Tabel data** dengan pagination (15 rows per page): `{{ $data->links() }}`
6. Kolom tabel sama persis dengan kolom export masing-masing laporan
7. Semua teks user-facing dalam **Bahasa Indonesia**
8. Gunakan **Tailwind CSS** untuk styling

### File yang dibuat:
- `resources/views/reports/eksekutif.blade.php` — tanpa tombol Export Excel
- `resources/views/reports/komputer.blade.php`
- `resources/views/reports/software.blade.php`
- `resources/views/reports/kepatuhan.blade.php`
- `resources/views/reports/lisensi.blade.php`

---

## TASK 5: BUAT 5 TEMPLATE PDF (BLADE)

Setiap PDF view adalah file HTML standalone (TIDAK extends layout apapun). DomPDF tidak support external CSS — gunakan **inline CSS only**.

### Aturan Wajib Semua Template PDF

**Header institusi** (di bagian paling atas setiap PDF):
```html
<!-- Logo placeholder -->
<div style="text-align:center; padding:10px; border:2px solid #333; display:inline-block; font-weight:bold;">
    [LOGO USN KOLAKA]
</div>
<h2 style="text-align:center; margin:5px 0;">Universitas Sembilanbelas November Kolaka</h2>
<p style="text-align:center; color:#555;">Sistem Manifest — Laporan [NAMA LAPORAN]</p>
```

**Metadata laporan** (di bawah header):
```
Periode    : [start_date] s/d [end_date]
Dicetak pada : [tanggal & jam saat ini]
Dicetak oleh : [nama user + role]
```

**Footer di setiap halaman** (gunakan CSS `@page` atau fixed positioning):
```
Halaman X dari Y
Dokumen ini dicetak secara otomatis oleh Sistem Manifest
```

**Blok tanda tangan** (hanya untuk Eksekutif & Kepatuhan):
```
Mengetahui,

(________________________)
Pimpinan
```

**Paper**: A4 portrait. **Kecuali** Inventaris Komputer → A4 **landscape**.

### 5a: `reports/pdf/eksekutif-pdf.blade.php`

Konten PDF (urut):
1. **4 kartu statistik** (styled sebagai grid 2×2 menggunakan `<table>`):
   - Total Komputer Aktif
   - Total Software Terdeteksi
   - Persentase Kepatuhan (2 desimal)
   - Jumlah Peringatan Kritis
2. **Tabel breakdown compliance**: Status | Jumlah Komputer | Persentase
   - Rows: Licensed, Grace Period, Action Required
3. **Tabel Top 5 software tidak berlisensi**: Nama Software | Jumlah Komputer Terinstall
4. **Catatan penutup** (teks statis):
   > "Laporan ini merupakan ringkasan kondisi kepatuhan lisensi perangkat lunak di lingkungan institusi pada periode yang tertera."
5. **Blok tanda tangan**

### 5b: `reports/pdf/komputer-pdf.blade.php`

- **Orientation**: landscape
- **Tabel**: No | Hostname | IP Address | MAC Address | CPU | RAM | OS | Status | Last Seen | Jumlah Software

### 5c: `reports/pdf/software-pdf.blade.php`

- **Tabel**: No | Nama Software | Versi | Jumlah Komputer | Status Lisensi | Kategori
- "Tidak Berlisensi" ditampilkan dengan **warna merah** (`color: red`)

### 5d: `reports/pdf/kepatuhan-pdf.blade.php`

- **Tabel**: No | Nama Komputer | IP Address | Nama Software | Status | Tanggal Deteksi | Keterangan
- **Status badge berwarna**:
  - Berlisensi → `background-color: #DCFCE7` (hijau)
  - Grace Period → `background-color: #FEF9C3` (kuning)
  - Tidak Berlisensi → `background-color: #FEE2E2` (merah)
- **Blok tanda tangan** di akhir

### 5e: `reports/pdf/lisensi-pdf.blade.php`

- **Tabel**: No | Nama Software | Tipe Lisensi | Total Seat | Terpakai | Sisa | % Penggunaan | Status | Expired
- Status berwarna:
  - "Penuh" & "Kedaluwarsa" → merah
  - "Hampir Habis" → kuning
  - "Tersedia" → hijau

---

## ATURAN KUALITAS KODE

1. ✅ Setiap file harus **LENGKAP** — tidak boleh ada placeholder, TODO, atau komentar "implement here"
2. ✅ Semua teks user-facing dalam **Bahasa Indonesia**
3. ✅ Komentar inline dalam **Bahasa Inggris** untuk logika non-obvious
4. ✅ Jika nama kolom tidak pasti, gunakan asumsi terbaik dan tandai: `// ASSUMPTION: adjust if column differs`
5. ✅ Gunakan `use` statement yang benar di setiap file PHP
6. ❌ Jangan menghapus kode yang sudah ada di `ReportController.php` (method `index()`, `export()`, `getComplianceData()`)
7. ❌ Jangan menghapus route yang sudah ada di `web.php`

---

## CHECKLIST VERIFIKASI

Setelah selesai, pastikan:

- [ ] `php artisan route:list` menampilkan semua 10 route baru tanpa error
- [ ] Halaman preview bisa diakses di browser untuk kelima laporan
- [ ] Filter tanggal bekerja (default bulan ini jika kosong)
- [ ] Export PDF menghasilkan file PDF yang bisa didownload
- [ ] Export Excel menghasilkan file .xlsx yang bisa didownload
- [ ] Heading Excel bold + background biru muda
- [ ] Row highlighting Excel bekerja sesuai spesifikasi
- [ ] PDF memiliki header institusi, metadata, footer halaman
- [ ] Template Eksekutif & Kepatuhan memiliki blok tanda tangan
- [ ] Inventaris Komputer PDF menggunakan orientation landscape
- [ ] Semua teks tampil dalam Bahasa Indonesia
