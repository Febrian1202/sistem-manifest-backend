# Task 23 — Update `DashboardController`: Dashboard Per Role

## Sprint: 4 (Fitur PJ Lab + Kirim Laporan)
## Prioritas: Tinggi
## Dependensi: Sprint 1 selesai
## Estimasi: 2-3 jam

---

## Deskripsi

Update `DashboardController` agar menampilkan dashboard yang berbeda berdasarkan role pengguna yang login.

## Langkah-langkah

### 1. Update Method `index()`

```php
public function index()
{
    $user = auth()->user();

    if ($user->hasRole('kepala_lab')) {
        return $this->labDashboard($user);
    }

    if ($user->hasRole('pimpinan')) {
        return $this->pimpinanDashboard();
    }

    return $this->adminDashboard(); // yang sudah ada
}
```

### 2. Method `adminDashboard()` — Tidak Berubah

Refactor method `index()` yang sudah ada menjadi `adminDashboard()`. Semua logic dan view yang ada tetap dipertahankan. Ini menampilkan data dari SEMUA laboratorium.

### 3. Method `labDashboard($user)` — BARU

Dashboard khusus PJ Lab yang hanya menampilkan data lab sendiri:

```php
private function labDashboard(User $user)
{
    $lab = $user->laboratory;

    if (!$lab) {
        return view('dashboard.no-lab'); // atau tampilkan pesan error
    }

    $labId = $lab->id;

    $stats = [
        'total_computers' => Computer::where('laboratory_id', $labId)->count(),
        'scanned_this_month' => Computer::where('laboratory_id', $labId)
            ->whereMonth('last_seen_at', now()->month)
            ->whereYear('last_seen_at', now()->year)
            ->count(),
        'total_software' => SoftwareDiscovery::whereHas('computer', fn($q) =>
            $q->where('laboratory_id', $labId))->count(),
        'compliance_rate' => ..., // hitung dari compliance_reports lab ini
        'pending_reports' => ReportApproval::where('laboratory_id', $labId)
            ->where('status', 'pending')->count(),
    ];

    // Top software di lab ini
    // Temuan terbaru di lab ini

    return view('dashboard.kepala-lab', compact('lab', 'stats'));
}
```

### 4. Method `pimpinanDashboard()` — Update

Dashboard Pimpinan yang hanya menampilkan data dari lab yang sudah approved:

```php
private function pimpinanDashboard()
{
    $currentPeriod = now()->format('Y-m');

    $approvedLabIds = ReportApproval::where('status', 'approved')
        ->where('report_type', 'kepatuhan')
        ->where('period', $currentPeriod)
        ->pluck('laboratory_id');

    // Hitung stats hanya dari lab yang approved
    $stats = [
        'total_computers' => Computer::whereIn('laboratory_id', $approvedLabIds)->count(),
        'approved_labs' => $approvedLabIds->count(),
        'total_labs' => Laboratory::count(),
        // ... stats lain yang di-scope ke approved labs
    ];

    return view('dashboard.pimpinan', compact('stats', 'approvedLabIds'));
}
```

### 5. Buat Views Dashboard Baru

**`resources/views/dashboard/kepala-lab.blade.php`**
- Nama lab + kode
- Card statistik: total komputer, sudah scan, tingkat kepatuhan, laporan pending
- Shortcut: "Lihat Inventaris", "Review Laporan"

**`resources/views/dashboard/pimpinan.blade.php`** (atau update view existing)
- Statistik global dari lab yang approved
- Daftar lab + status approval (approved/pending/belum dikirim)
- Shortcut ke laporan

## Referensi
- Baca `DashboardController` yang sudah ada untuk memahami pola stats, cache, dan chart
- Pertahankan mekanisme cache yang sudah ada (tapi cache key perlu di-scope per lab jika memungkinkan)

## File yang Dimodifikasi
- `app/Http/Controllers/DashboardController.php`

## File yang Dibuat
- `resources/views/dashboard/kepala-lab.blade.php`
- `resources/views/dashboard/pimpinan.blade.php` (atau modifikasi view existing)

## Verifikasi
- [x] Admin melihat dashboard penuh (semua data, tidak berubah dari sebelumnya)
- [x] Kepala Lab melihat dashboard lab sendiri saja
- [x] Pimpinan melihat dashboard hanya dari lab yang approved
- [x] Kepala Lab yang belum ditugaskan ke lab melihat pesan informatif
- [x] Statistik akurat sesuai scope masing-masing role
