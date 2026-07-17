# Task 27 — Update `ComplianceDataController`: Scope Data by Role

## Sprint: 5 (Integrasi Pimpinan & Polish)
## Prioritas: Sedang
## Dependensi: Sprint 4 selesai
## Estimasi: 1 jam

---

## Deskripsi

Update `ComplianceDataController` (halaman dashboard kepatuhan) agar data yang ditampilkan sesuai dengan role pengguna yang login.

## Langkah-langkah

### 1. Update Query Data

Buka `app/Http/Controllers/ComplianceDataController.php`.

**Scope berdasarkan role:**

```php
public function index()
{
    $user = auth()->user();
    $query = ComplianceReport::query(); // atau query base yang sudah ada

    if ($user->hasRole('kepala_lab')) {
        // Hanya data lab sendiri
        $query->whereHas('computer', fn($q) =>
            $q->where('laboratory_id', $user->laboratory_id)
        );
    }

    if ($user->hasRole('pimpinan')) {
        // Hanya data dari lab yang approved
        $currentPeriod = now()->format('Y-m');
        $approvedLabIds = ReportApproval::where('status', 'approved')
            ->where('report_type', 'kepatuhan')
            ->where('period', $currentPeriod)
            ->pluck('laboratory_id');

        $query->whereHas('computer', fn($q) =>
            $q->whereIn('laboratory_id', $approvedLabIds)
        );
    }

    // Admin → tanpa filter (semua data)

    // ... lanjutkan logic yang sudah ada
}
```

### 2. Update Statistik Global

Jika halaman compliance menampilkan statistik global (total commercial software, deficit, dll), pastikan statistik juga di-scope per role.

### 3. Update Cache Key (Jika Ada)

Jika halaman compliance menggunakan cache, pastikan cache key memperhitungkan role/lab:

```php
$cacheKey = "compliance.stats.{$user->id}"; // atau scope by role+lab
```

## Referensi
- Baca `ComplianceDataController` yang sudah ada
- Perhatikan pola cache yang digunakan (`compliance.global_stats`)

## File yang Dimodifikasi
- `app/Http/Controllers/ComplianceDataController.php`

## Verifikasi
- [ ] Admin melihat semua data compliance (semua lab)
- [ ] Kepala Lab melihat data compliance lab sendiri saja
- [ ] Pimpinan melihat data compliance dari lab yang approved saja
- [ ] Statistik global akurat sesuai scope
- [ ] Cache tidak menyebabkan data role A muncul di role B
