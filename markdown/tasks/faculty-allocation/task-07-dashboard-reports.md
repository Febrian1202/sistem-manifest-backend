# Task 07: Dashboard Pimpinan & Laporan Kebutuhan Lisensi (Executive Dashboard & License Needs Report)

## 1. Ringkasan Task
Meningkatkan kapabilitas pengambilan keputusan (*decision support*) bagi pemangku kepentingan (Pimpinan Universitas & Dekan Fakultas) melalui dua komponen utama:
1. **Peningkatan Dashboard Pimpinan:** Menampilkan metrik komparasi lintas fakultas, daftar defisit terbesar, dan kemampuan *drill-down* hierarkis (`Fakultas → Lab → Komputer`).
2. **Laporan Baru: "Laporan Analisis Kebutuhan dan Alokasi Lisensi Software":** Laporan komprehensif yang memetakan kepemilikan universitas, distribusi alokasi ke fakultas, instalasi aktual di laboratorium, serta rekomendasi indikatif kebutuhan pengadaan lisensi baru, lengkap dengan fasilitas ekspor **PDF** dan **Excel**.

- **Status Dependensi:** Bergantung pada [Task 06 — Analisis Compliance Bertingkat per Fakultas](./task-06-faculty-compliance.md).
- **Target File yang Dibuat / Diubah:**
  - `app/Http/Controllers/DashboardController.php` (Update data pimpinan)
  - `resources/views/dashboard/pimpinan.blade.php` (Update UI tampilan pimpinan)
  - `app/Http/Controllers/ReportController.php` (Menambahkan endpoint report kebutuhan lisensi)
  - `app/Exports/LicenseNeedsExport.php` (Baru: Export Excel)
  - `resources/views/reports/kebutuhan-lisensi.blade.php` (Baru: Preview web)
  - `resources/views/reports/pdf/kebutuhan-lisensi-pdf.blade.php` (Baru: Cetak PDF)
  - `routes/web.php`
  - `tests/Feature/ExecutiveDashboardAndReportTest.php` (Baru)

---

## 2. Rincian Spesifikasi Teknis

### 2.1 Peningkatan Dashboard Pimpinan (`DashboardController.php` & Blade)

#### Backend (`DashboardController::pimpinanDashboard()`):
Kumpulkan data lintas unit menggunakan `LicenseComplianceService`:
```php
$facultyMatrix = $this->complianceService->getCrossFacultyMatrix();

$globalStats = [
    'total_faculties' => Faculty::count(),
    'total_laboratories' => Laboratory::count(),
    'total_computers' => Computer::where('status', 'active')->count(),
    'total_owned_licenses' => LicenseInventory::sum('quota_limit'),
    'total_allocated_seats' => LicenseAllocation::where('status', 'active')->sum('allocated_quota'),
    'total_software_deficits' => $facultyMatrix->sum('total_deficit'),
];

// Top 5 software dengan defisit kumulatif tertinggi di seluruh fakultas
$topDeficitSoftwares = DB::table('software_catalogs')
    ->where('category', 'Commercial')
    ->get()
    ->map(function ($catalog) {
        $entitlement = $this->complianceService->getActiveEntitlement($catalog->id);
        $installed = $this->complianceService->getInstalledCount($catalog->id);
        $deficit = max(0, $installed - $entitlement);
        return [
            'name' => $catalog->normalized_name,
            'owned' => $entitlement,
            'installed' => $installed,
            'deficit' => $deficit,
        ];
    })
    ->filter(fn ($item) => $item['deficit'] > 0)
    ->sortByDesc('deficit')
    ->take(5);
```

#### Antarmuka (`resources/views/dashboard/pimpinan.blade.php`):
1. **Baris Kartu Ringkasan (Top Stat Cards):**
   - Total Fakultas
   - Total Laboratorium
   - Total Komputer Terdata
   - Total Hak Lisensi Universitas
   - Total Kursi Terdistribusi
   - Total Defisit Lisensi (Warna merah jika $> 0$)
2. **Tabel Ringkasan Status per Fakultas:**
   | Fakultas | Lab | Komputer | Alokasi Seat | Terpasang | Defisit | Surplus | Status Compliance |
   |---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
   | FTI | 5 | 120 | 90 | 105 | 15 | 0 | Defisit (Perlu Atensi) |
   | FKIP | 4 | 100 | 110 | 92 | 0 | 18 | Surplus (Optimal) |
3. **Widget Insight Pengadaan:**
   - Menampilkan card interaktif "Software Prioritas Pengadaan": Daftar software yang defisit beserta total kebutuhan unit penambahan seat.
4. **Fasilitas Drill-Down:**
   - Link pada nama Fakultas yang mengarahkan ke halaman detail fakultas / filter kepatuhan fakultas tersebut.

---

### 2.2 Laporan Analisis Kebutuhan & Alokasi Lisensi

#### Judul Resmi Laporan:
> **"Laporan Analisis Kebutuhan dan Alokasi Lisensi Software"**
> *(Sebagai Dokumen Pendukung Pengambilan Keputusan Pengelolaan dan Pengadaan Lisensi)*

#### Struktur Konten Laporan:
1. **Bagian I: Ringkasan Kapasitas Lisensi Universitas**
   - Rekap total software komersial, total lisensi dimiliki (`Owned`), total alokasi terdistribusi (`Allocated`), sisa kuota universitas belum dialokasi (`Unallocated`).
2. **Bagian II: Distribusi & Kepatuhan per Fakultas**
   - Tabel per fakultas memuat: Nama Software, Kuota Alokasi Fakultas, Jumlah Instalasi Terdeteksi, Status (Cukup / Defisit / Surplus), Rekomendasi Alokasi.
3. **Bagian III: Rekapitulasi Defisit & Kebutuhan Pengadaan (Procurement Insights)**
   - Daftar software yang mengalami defisit bersih di level universitas.
   - Contoh penyajian:
     ```text
     Microsoft Office Professional:
       - Kepemilikan USN: 50 seat
       - Total Terpasang: 65 unit
       - Defisit Universitas: 15 unit
       - Sebaran Defisit: FTI (10 unit), FISIP (5 unit)
       - Catatan Rekomendasi: Evaluasi redistribusi alokasi dari FKIP (surplus 10) atau pengadaan baru 5 unit.
     ```

#### Endpoint di `routes/web.php`:
```php
Route::middleware(['auth', 'role:admin|pimpinan'])->group(function () {
    Route::get('/reports/kebutuhan-lisensi', [ReportController::class, 'showKebutuhanLisensi'])->name('reports.kebutuhan-lisensi');
    Route::get('/reports/kebutuhan-lisensi/export', [ReportController::class, 'exportKebutuhanLisensi'])->name('reports.kebutuhan-lisensi.export');
});
```

#### Export Excel: `app/Exports/LicenseNeedsExport.php`
Menggunakan `Maatwebsite\Excel\Concerns\FromCollection`, `WithHeadings`, `WithStyles`, `ShouldAutoSize`.
Menghasilkan 2 sheet:
- Sheet 1: Ringkasan Kebutuhan Lisensi per Fakultas
- Sheet 2: Analisis Rekomendasi Pengadaan

#### Cetak PDF: `resources/views/reports/pdf/kebutuhan-lisensi-pdf.blade.php`
- Layout cetak resmi DomPDF dengan kop surat Universitas Sembilanbelas November Kolaka.
- Tanda tangan pengesahan pimpinan / penanggung jawab.

---

## 3. Langkah-Langkah Pengerjaan (Step-by-Step)

1. Perbarui method `pimpinanDashboard()` pada `DashboardController.php` untuk menghitung matriks komparasi fakultas dan top software defisit.
2. Desain ulang view `resources/views/dashboard/pimpinan.blade.php` sesuai mockup tabel komparasi dan widget defisit.
3. Tambahkan method `showKebutuhanLisensi()` dan `exportKebutuhanLisensi()` pada `ReportController.php`.
4. Buat export class:
   ```bash
   php artisan make:export LicenseNeedsExport
   ```
5. Buat view preview `resources/views/reports/kebutuhan-lisensi.blade.php`.
6. Buat view PDF template `resources/views/reports/pdf/kebutuhan-lisensi-pdf.blade.php`.
7. Tambahkan tautan menu baru pada sidebar dan pusat laporan (`reports.blade.php`).
8. Buat dan jalankan test otomatis di `tests/Feature/ExecutiveDashboardAndReportTest.php`.

---

## 4. Kriteria Keberhasilan (Acceptance Criteria)
- [x] Dashboard pimpinan memuat kartu ringkasan universitas dan tabel perbandingan fakultas yang akurat.
- [x] Pimpinan dapat mengidentifikasi fakultas mana yang surplus dan fakultas mana yang defisit dalam satu layar.
- [x] Halaman preview Laporan Kebutuhan Lisensi menampilkan kalkulasi data yang sinkron dengan database.
- [x] Ekspor PDF berhasil di-generate dengan layout rapi dan format kop surat resmi.
- [x] Ekspor Excel menghasilkan spreadsheet dengan header dan styling sel yang terbaca jelas.
- [x] Teks laporan menjaga netralitas ilmiah: menyatakan data sebagai "bahan pertimbangan evaluasi dan pengambilan keputusan pengadaan".

---

## 5. Rencana Pengujian Otomatis (Pest Test)
Buat file `tests/Feature/ExecutiveDashboardAndReportTest.php`:
1. `it('displays cross-faculty summary cards and comparison table on pimpinan dashboard')`
2. `it('shows top deficit software ranking correctly')`
3. `it('renders license needs report preview successfully for admin and pimpinan')`
4. `it('exports license needs report as PDF without errors')`
5. `it('exports license needs report as Excel spreadsheet successfully')`
6. `it('prevents unauthorized roles from accessing executive report exports')`
