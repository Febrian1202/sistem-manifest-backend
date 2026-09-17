# Task 20 — Controller + Views: `LabInventoryController` (Inventaris Lab PJ Lab)

## Sprint: 4 (Fitur PJ Lab + Kirim Laporan)
## Prioritas: Tinggi
## Dependensi: Sprint 1 selesai
## Estimasi: 2-3 jam

---

## Deskripsi

Buat controller dan views untuk fitur inventaris lab yang hanya bisa diakses oleh PJ Lab. Semua data di-scope ke laboratorium milik user yang login.

## Langkah-langkah

### 1. Buat Controller

```bash
php artisan make:controller LabInventoryController
```

### 2. Implementasi Method

**`index()` — Daftar komputer di lab sendiri (GET `/lab/inventory`)**

```php
public function index()
{
    $user = auth()->user();
    $lab = $user->laboratory;

    if (!$lab) {
        abort(403, 'Anda belum ditugaskan ke laboratorium manapun.');
    }

    $computers = $lab->computers()
        ->withCount('softwareDiscoveries')
        ->with('latestComplianceReport')  // atau relasi serupa
        ->paginate(20);

    // Hitung ringkasan
    $stats = [
        'total_computers' => $lab->computers()->count(),
        'scanned_this_month' => $lab->computers()
            ->whereMonth('last_seen_at', now()->month)
            ->whereYear('last_seen_at', now()->year)
            ->count(),
        // Hitung compliance rate lab
    ];

    return view('lab-inventory.index', compact('computers', 'lab', 'stats'));
}
```

**`show($computer)` — Detail komputer + software (GET `/lab/inventory/{computer}`)**

```php
public function show(Computer $computer)
{
    $user = auth()->user();

    // SCOPE CHECK: pastikan komputer ini milik lab user
    if ($computer->laboratory_id !== $user->laboratory_id) {
        abort(403);
    }

    $computer->load([
        'softwareDiscoveries.softwareCatalog',
        'complianceReports.softwareCatalog',
        'laboratory',
    ]);

    return view('lab-inventory.show', compact('computer'));
}
```

### 3. Buat Views

**`resources/views/lab-inventory/index.blade.php`**

Tampilkan:
- Header: nama lab, kode lab, info gedung/lantai
- Ringkasan: total komputer, sudah scan bulan ini, tingkat kepatuhan lab
- Tabel komputer: hostname, OS, jumlah software, tingkat kepatuhan, terakhir scan
- Search/filter (opsional)
- **Tidak ada** tombol edit/hapus/scan (PJ Lab read-only)

**`resources/views/lab-inventory/show.blade.php`**

Tampilkan:
- Info komputer: hostname, OS, hardware specs, last seen
- Daftar software terinstal + status kepatuhan masing-masing
- Nama laboratorium
- **Tidak ada** tombol edit/hapus (PJ Lab read-only)

### 4. Daftarkan Route

Di `routes/web.php`, dalam group middleware `role:kepala_lab`:
```php
Route::get('/lab/inventory', [LabInventoryController::class, 'index'])->name('lab.inventory.index');
Route::get('/lab/inventory/{computer}', [LabInventoryController::class, 'show'])->name('lab.inventory.show');
```

## Keamanan — PENTING
- **Semua query HARUS di-scope** ke `auth()->user()->laboratory_id`
- PJ Lab **tidak boleh bisa** melihat komputer di lab lain (cek di `show()`)
- Tidak ada method `store`, `update`, `destroy` — PJ Lab sepenuhnya read-only

## Referensi
- Lihat `ComputerDataController` yang sudah ada untuk referensi cara menampilkan daftar komputer dan detail
- Gunakan layout dan styling yang konsisten

## File yang Dibuat
- `app/Http/Controllers/LabInventoryController.php`
- `resources/views/lab-inventory/index.blade.php`
- `resources/views/lab-inventory/show.blade.php`

## File yang Dimodifikasi
- `routes/web.php` — tambah routes

## Verifikasi
- [x] PJ Lab melihat hanya komputer di lab sendiri
- [x] PJ Lab tidak bisa akses komputer di lab lain (403)
- [x] Ringkasan statistik lab ditampilkan dengan benar
- [x] Detail komputer + software terinstal tampil
- [x] Tidak ada tombol edit/hapus di halaman ini
- [x] Admin dan Pimpinan tidak bisa akses route ini (403)
