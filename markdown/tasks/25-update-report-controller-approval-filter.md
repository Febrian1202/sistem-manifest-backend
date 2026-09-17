# Task 25 — Update `ReportController`: Filter Laporan by Approval Status

## Sprint: 5 (Integrasi Pimpinan & Polish)
## Prioritas: Tinggi
## Dependensi: Sprint 4 selesai
## Estimasi: 1-2 jam

---

## Deskripsi

Modifikasi `ReportController` agar laporan yang ditampilkan ke Pimpinan hanya berisi data dari laboratorium yang laporannya sudah di-approve oleh PJ Lab.

## Langkah-langkah

### 1. Update Setiap Method Preview dan Export

Buka `app/Http/Controllers/ReportController.php`. Pada setiap method yang menampilkan data laporan (preview dan export untuk semua 5 tipe), tambahkan filter:

```php
// Helper method (taruh di controller atau trait)
private function getApprovedLabIds(string $reportType, string $period): Collection
{
    return ReportApproval::where('status', 'approved')
        ->where('report_type', $reportType)
        ->where('period', $period)
        ->pluck('laboratory_id');
}

// Pada setiap query yang mengambil data compliance/computer:
if (auth()->user()->hasRole('pimpinan')) {
    $approvedLabIds = $this->getApprovedLabIds('kepatuhan', $period);

    // Filter computer-based queries
    $query->whereHas('computer', fn($q) =>
        $q->whereIn('laboratory_id', $approvedLabIds)
    );

    // Atau untuk query langsung ke computers:
    $query->whereIn('laboratory_id', $approvedLabIds);
}
```

### 2. Tipe Laporan yang Perlu Difilter

| Tipe | Query yang Perlu Difilter |
|------|--------------------------|
| Eksekutif | Semua statistik global → scope ke approved labs |
| Komputer | Daftar komputer → filter by `laboratory_id` |
| Software | Daftar software → filter by komputer di approved labs |
| Kepatuhan | Compliance reports → filter by komputer di approved labs |
| Lisensi | Status lisensi → tetap global (lisensi tidak per lab) |

### 3. Handle Case: Tidak Ada Lab yang Approved

```php
if (auth()->user()->hasRole('pimpinan') && $approvedLabIds->isEmpty()) {
    // Tampilkan pesan: "Belum ada laporan yang disetujui untuk periode ini"
    // Jangan tampilkan data kosong tanpa penjelasan
}
```

### 4. Admin Tetap Melihat Semua Data

Filter ini HANYA berlaku untuk role `pimpinan`. Admin tetap melihat semua data tanpa filter.

## Referensi
- Baca `ReportController` yang sudah ada untuk memahami 5 tipe laporan dan cara query datanya
- Setiap tipe mungkin punya method preview + export terpisah — semua perlu di-update

## File yang Dimodifikasi
- `app/Http/Controllers/ReportController.php`

## Verifikasi
- [x] Pimpinan hanya melihat data dari lab yang sudah approved
- [x] Admin tetap melihat semua data (tidak terfilter)
- [x] Jika tidak ada lab approved, Pimpinan melihat pesan informatif
- [x] Semua 5 tipe laporan terfilter dengan benar
- [x] Export PDF/Excel juga terfilter (bukan hanya preview)
