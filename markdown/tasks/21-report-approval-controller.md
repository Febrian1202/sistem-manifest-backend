# Task 21 — Controller + Views: `ReportApprovalController` (Review + Approve/Reject)

## Sprint: 4 (Fitur PJ Lab + Kirim Laporan)
## Prioritas: Tinggi
## Dependensi: Sprint 1 selesai, Task 19
## Estimasi: 3-4 jam

---

## Deskripsi

Buat controller dan views untuk fitur review dan approval laporan oleh PJ Lab. Ini adalah fitur inti dari alur revisi.

## Langkah-langkah

### 1. Buat Controller

```bash
php artisan make:controller ReportApprovalController
```

### 2. Implementasi Method

**`index()` — Daftar laporan (GET `/lab/reports`)**

```php
public function index()
{
    $user = auth()->user();
    $labId = $user->laboratory_id;

    if (!$labId) {
        abort(403, 'Anda belum ditugaskan ke laboratorium manapun.');
    }

    $approvals = ReportApproval::where('laboratory_id', $labId)
        ->with('laboratory')
        ->orderByDesc('created_at')
        ->paginate(15);

    return view('report-approvals.index', compact('approvals'));
}
```

**`show($reportApproval)` — Detail laporan untuk review (GET `/lab/reports/{reportApproval}`)**

```php
public function show(ReportApproval $reportApproval)
{
    $this->authorizeLabAccess($reportApproval);

    $lab = $reportApproval->laboratory;
    $period = $reportApproval->period;

    // Ambil data komputer dan compliance untuk lab + periode ini
    $computers = Computer::where('laboratory_id', $lab->id)
        ->with(['complianceReports' => function ($q) {
            $q->with('softwareCatalog');
        }])
        ->get();

    // Hitung ringkasan
    $stats = [
        'total_computers' => $computers->count(),
        'scanned' => $computers->where('last_seen_at', '>=', Carbon::parse($period . '-01'))->count(),
        'compliant' => ...,      // hitung dari compliance reports
        'non_compliant' => ...,
        'compliance_rate' => ...,
    ];

    // Daftar temuan pelanggaran
    $violations = ComplianceReport::whereHas('computer', fn($q) =>
            $q->where('laboratory_id', $lab->id))
        ->where('status', '!=', 'Berlisensi')
        ->with(['computer', 'softwareCatalog'])
        ->get();

    return view('report-approvals.show', compact(
        'reportApproval', 'lab', 'computers', 'stats', 'violations'
    ));
}
```

**`approve($reportApproval)` — Setujui laporan (POST `/lab/reports/{id}/approve`)**

```php
public function approve(Request $request, ReportApproval $reportApproval)
{
    $this->authorizeLabAccess($reportApproval);

    if ($reportApproval->status !== 'pending') {
        return back()->with('error', 'Laporan ini sudah di-review sebelumnya.');
    }

    $request->validate(['notes' => 'nullable|string']);

    $reportApproval->update([
        'status' => 'approved',
        'notes' => $request->notes,
        'reviewed_at' => now(),
    ]);

    return redirect()->route('lab.reports.index')
        ->with('success', 'Laporan berhasil disetujui.');
}
```

**`reject($reportApproval)` — Tolak laporan (POST `/lab/reports/{id}/reject`)**

```php
public function reject(Request $request, ReportApproval $reportApproval)
{
    $this->authorizeLabAccess($reportApproval);

    if ($reportApproval->status !== 'pending') {
        return back()->with('error', 'Laporan ini sudah di-review sebelumnya.');
    }

    $request->validate(['notes' => 'required|string']); // Wajib saat reject

    $reportApproval->update([
        'status' => 'rejected',
        'notes' => $request->notes,
        'reviewed_at' => now(),
    ]);

    return redirect()->route('lab.reports.index')
        ->with('success', 'Laporan ditolak. Catatan telah disimpan.');
}
```

**Helper: `authorizeLabAccess()`**
```php
private function authorizeLabAccess(ReportApproval $reportApproval): void
{
    if ($reportApproval->laboratory_id !== auth()->user()->laboratory_id) {
        abort(403);
    }
}
```

### 3. Buat Views

**`resources/views/report-approvals/index.blade.php`**
- Tabel: Periode, Tipe Laporan, Status (badge warna), Tanggal Review, Aksi
- Aksi: "Tinjau" (jika pending), "Lihat Detail" (jika sudah approved/rejected)
- Pisahkan: laporan pending di atas, riwayat di bawah

**`resources/views/report-approvals/show.blade.php`**
- Bagian A: Ringkasan (total komputer, sudah scan, tingkat kepatuhan)
- Bagian B: Daftar temuan pelanggaran (tabel: komputer, software, status)
- Bagian C: Daftar semua komputer (expandable detail)
- Bagian D: Form keputusan (textarea catatan + tombol Approve/Reject)
- Tombol "Preview PDF" (link ke Task 22)
- Jika status sudah approved/rejected: tampilkan catatan & timestamp, sembunyikan form

### 4. Daftarkan Route

Di `routes/web.php`, dalam group middleware `role:kepala_lab`:
```php
Route::get('/lab/reports', [ReportApprovalController::class, 'index'])->name('lab.reports.index');
Route::get('/lab/reports/{reportApproval}', [ReportApprovalController::class, 'show'])->name('lab.reports.show');
Route::post('/lab/reports/{reportApproval}/approve', [ReportApprovalController::class, 'approve'])->name('lab.reports.approve');
Route::post('/lab/reports/{reportApproval}/reject', [ReportApprovalController::class, 'reject'])->name('lab.reports.reject');
```

## Aturan Bisnis — PENTING
- Approval **per lab per periode** — semua atau tidak sama sekali
- Sekali approved/rejected → **final** (tidak bisa diubah)
- Catatan (`notes`) opsional saat approve, **wajib** saat reject
- PJ Lab hanya bisa akses laporan lab sendiri

## File yang Dibuat
- `app/Http/Controllers/ReportApprovalController.php`
- `resources/views/report-approvals/index.blade.php`
- `resources/views/report-approvals/show.blade.php`

## File yang Dimodifikasi
- `routes/web.php` — tambah routes

## Verifikasi
- [x] Daftar laporan hanya menampilkan lab sendiri
- [x] Detail laporan menampilkan ringkasan, temuan, dan daftar komputer
- [x] Bisa approve laporan pending (status berubah, timestamp terisi)
- [x] Bisa reject laporan pending (notes wajib, status berubah)
- [x] Tidak bisa approve/reject laporan yang sudah di-review
- [x] Tidak bisa akses laporan lab lain (403)
- [x] Activity log tercatat saat approve/reject
