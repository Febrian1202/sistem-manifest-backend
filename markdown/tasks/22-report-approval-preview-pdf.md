# Task 22 — Fitur Preview PDF di Halaman Review PJ Lab

## Sprint: 4 (Fitur PJ Lab + Kirim Laporan)
## Prioritas: Sedang
## Dependensi: Task 21
## Estimasi: 1-2 jam

---

## Deskripsi

Tambah fitur preview PDF di halaman review laporan PJ Lab. PJ Lab bisa melihat bentuk laporan final sebelum memberikan keputusan approve/reject.

## Langkah-langkah

### 1. Tambah Method di `ReportApprovalController`

```php
public function previewPdf(ReportApproval $reportApproval)
{
    $this->authorizeLabAccess($reportApproval);

    $lab = $reportApproval->laboratory;

    // Ambil data compliance untuk lab ini
    $complianceData = ComplianceReport::whereHas('computer', fn($q) =>
            $q->where('laboratory_id', $lab->id))
        ->with(['computer', 'softwareCatalog', 'licenseInventory'])
        ->get();

    $computers = Computer::where('laboratory_id', $lab->id)
        ->with('softwareDiscoveries.softwareCatalog')
        ->get();

    // Generate PDF menggunakan DomPDF (sudah ada di proyek: barryvdh/laravel-dompdf)
    $pdf = Pdf::loadView('report-approvals.preview-pdf', [
        'lab' => $lab,
        'period' => $reportApproval->period,
        'complianceData' => $complianceData,
        'computers' => $computers,
        'printedBy' => auth()->user()->name,
        'printedAt' => now(),
    ]);

    // Stream (tampilkan di browser, bukan download)
    return $pdf->stream("preview-laporan-{$lab->code}-{$reportApproval->period}.pdf");
}
```

### 2. Daftarkan Route

Di `routes/web.php`, dalam group middleware `role:kepala_lab`:
```php
Route::get('/lab/reports/{reportApproval}/preview-pdf', [ReportApprovalController::class, 'previewPdf'])
    ->name('lab.reports.preview-pdf');
```

### 3. Buat View PDF

**`resources/views/report-approvals/preview-pdf.blade.php`**

Referensikan layout dan format dari view laporan PDF yang sudah ada di proyek (lihat `app/Exports/` dan view PDF existing). Konten:

- Header: Logo/nama institusi, judul "Laporan Kepatuhan Perangkat Lunak"
- Info: Nama lab, periode, tanggal cetak
- Ringkasan: total komputer, tingkat kepatuhan, jumlah temuan
- Tabel detail per komputer: hostname, software bermasalah, status
- Footer: "Preview — Belum Disetujui" (watermark atau teks)

### 4. Tambah Tombol di View Show

Di `resources/views/report-approvals/show.blade.php`, tambahkan tombol "Preview PDF":

```html
<a href="{{ route('lab.reports.preview-pdf', $reportApproval) }}"
   target="_blank"
   class="...">
    Preview PDF
</a>
```

## Referensi
- Lihat `ReportController` dan view PDF yang sudah ada untuk format dan styling PDF
- Proyek sudah menggunakan `barryvdh/laravel-dompdf` — gunakan package yang sama
- Lihat `app/Exports/` untuk referensi format export

## File yang Dibuat
- `resources/views/report-approvals/preview-pdf.blade.php`

## File yang Dimodifikasi
- `app/Http/Controllers/ReportApprovalController.php` — tambah method `previewPdf()`
- `resources/views/report-approvals/show.blade.php` — tambah tombol Preview PDF
- `routes/web.php` — tambah route

## Verifikasi
- [x] Tombol "Preview PDF" muncul di halaman review
- [x] PDF ter-generate dan ditampilkan di tab baru browser
- [x] PDF berisi data lab yang benar (bukan lab lain)
- [x] PDF menampilkan watermark "Preview" atau "Belum Disetujui"
- [x] PJ Lab tidak bisa akses preview PDF lab lain (403)
