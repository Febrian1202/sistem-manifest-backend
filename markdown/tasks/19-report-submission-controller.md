# Task 19 — Controller + View: `ReportSubmissionController` (Admin Kirim ke PJ Lab)

## Sprint: 4 (Fitur PJ Lab + Kirim Laporan)
## Prioritas: Tinggi
## Dependensi: Sprint 1 selesai
## Estimasi: 2-3 jam

---

## Deskripsi

Buat controller dan view untuk fitur "Kirim Laporan ke PJ Lab". Ini adalah halaman di mana Admin memilih laboratorium dan periode, lalu mengirimkan laporan kepatuhan ke PJ Lab untuk di-review.

## Langkah-langkah

### 1. Buat Controller

```bash
php artisan make:controller ReportSubmissionController
```

### 2. Implementasi Method

**`index()` — Halaman utama (GET `/reports/submit-to-lab`)**

Tampilkan daftar semua laboratorium beserta:
- Jumlah komputer total per lab
- Jumlah komputer yang sudah ter-scan di periode yang dipilih (komputer dengan `last_seen_at` di bulan tersebut)
- Status pengiriman terakhir per lab per periode (belum dikirim / pending / approved / rejected)

```php
public function index(Request $request)
{
    $period = $request->get('period', now()->format('Y-m'));

    $laboratories = Laboratory::withCount('computers')
        ->with(['reportApprovals' => function ($q) use ($period) {
            $q->where('period', $period)
              ->where('report_type', 'kepatuhan')
              ->latest();
        }])
        ->get();

    // Hitung komputer yang sudah scan di periode ini per lab
    // (komputer dengan last_seen_at di bulan $period)

    return view('report-submissions.index', compact('laboratories', 'period'));
}
```

**`submit()` — Kirim laporan (POST `/reports/submit-to-lab`)**

```php
public function submit(Request $request)
{
    $request->validate([
        'laboratory_id' => 'required|exists:laboratories,id',
        'period' => 'required|date_format:Y-m',
    ]);

    $labId = $request->laboratory_id;
    $period = $request->period;

    // Cek apakah sudah ada pending
    $hasPending = ReportApproval::where([
        'laboratory_id' => $labId,
        'report_type' => 'kepatuhan',
        'period' => $period,
        'status' => 'pending',
    ])->exists();

    if ($hasPending) {
        return back()->with('error', 'Lab ini masih memiliki laporan pending yang belum di-review.');
    }

    // Buat record approval baru
    ReportApproval::create([
        'laboratory_id' => $labId,
        'report_type' => 'kepatuhan',
        'period' => $period,
        'status' => 'pending',
        'reviewed_by' => User::role('kepala_lab')
                            ->where('laboratory_id', $labId)
                            ->first()?->id,
    ]);

    activity()
        ->causedBy(auth()->user())
        ->withProperties(['laboratory_id' => $labId, 'period' => $period])
        ->log("Mengirim laporan kepatuhan ke PJ Lab untuk periode {$period}");

    return back()->with('success', 'Laporan berhasil dikirim ke PJ Lab.');
}
```

### 3. Buat View

**`resources/views/report-submissions/index.blade.php`**

Mockup layout:
```
┌─────────────────────────────────────────────────────────┐
│  Kirim Laporan ke PJ Lab                                │
│                                                         │
│  Periode: [Juli 2026 ▼]                                 │
│                                                         │
│  ┌──────────────┬──────────┬────────┬─────────┬──────┐  │
│  │ Laboratorium │ Komputer │ Sudah  │ Status  │ Aksi │  │
│  │              │ Total    │ Scan   │         │      │  │
│  ├──────────────┼──────────┼────────┼─────────┼──────┤  │
│  │ Lab Komp 1   │ 25       │ 25/25  │ Siap    │[Kirim]│ │
│  │ Lab Komp 2   │ 20       │ 18/20  │ Belum   │[Kirim]│ │
│  │ Lab Jaringan │ 15       │ 15/15  │ Pending │ -    │  │
│  └──────────────┴──────────┴────────┴─────────┴──────┘  │
│                                                         │
│  Keterangan Status:                                     │
│  - Siap: Semua komputer sudah scan, belum dikirim       │
│  - Belum: Ada komputer yang belum scan                  │
│  - Pending: Sudah dikirim, menunggu review PJ Lab       │
│  - Approved: PJ Lab sudah menyetujui                    │
│  - Rejected: PJ Lab menolak, perlu tindak lanjut        │
└─────────────────────────────────────────────────────────┘
```

Aturan UI:
- Tombol "Kirim" disabled jika status = pending (sudah dikirim belum di-review)
- Tombol "Kirim" tetap muncul jika status = approved/rejected (untuk kirim ulang)
- Jika lab belum lengkap scan, tetap bisa kirim tapi tampilkan warning
- Dropdown periode untuk memilih bulan (default: bulan ini)

### 4. Daftarkan Route

Di `routes/web.php`, dalam group middleware `role:admin`:
```php
Route::get('/reports/submit-to-lab', [ReportSubmissionController::class, 'index'])->name('report-submissions.index');
Route::post('/reports/submit-to-lab', [ReportSubmissionController::class, 'submit'])->name('report-submissions.submit');
```

## File yang Dibuat
- `app/Http/Controllers/ReportSubmissionController.php`
- `resources/views/report-submissions/index.blade.php`

## File yang Dimodifikasi
- `routes/web.php` — tambah routes

## Verifikasi
- [ ] Halaman menampilkan daftar lab dengan jumlah komputer dan status kesiapan
- [ ] Bisa kirim laporan ke lab yang belum ada pending
- [ ] Tidak bisa kirim ulang jika masih ada pending
- [ ] Bisa kirim ulang setelah PJ Lab approve/reject
- [ ] Activity log tercatat saat kirim
- [ ] Flash message sukses/error tampil
- [ ] Role selain admin tidak bisa akses (403)
