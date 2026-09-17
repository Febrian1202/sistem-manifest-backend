# Task 04 — Migration: Tabel `report_approvals`

## Sprint: 1 (Fondasi)
## Prioritas: Tinggi
## Dependensi: Task 01, Task 03
## Estimasi: 15-20 menit

---

## Deskripsi

Buat migration baru untuk tabel `report_approvals`. Tabel ini menyimpan riwayat persetujuan/penolakan laporan oleh PJ Lab (Kepala Lab). Satu lab bisa punya beberapa record di periode yang sama (jika Admin mengirim ulang setelah approve/reject).

## Langkah-langkah

1. Jalankan `php artisan make:migration create_report_approvals_table`
2. Isi migration sesuai skema di bawah

## Skema Tabel

```php
Schema::create('report_approvals', function (Blueprint $table) {
    $table->id();
    $table->foreignId('laboratory_id')->constrained('laboratories')->cascadeOnDelete();
    $table->foreignId('reviewed_by')->nullable()->constrained('users')->cascadeOnDelete();
    $table->string('report_type');         // 'kepatuhan'
    $table->string('period');              // '2026-07' (tahun-bulan)
    $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
    $table->text('notes')->nullable();     // Catatan review PJ Lab
    $table->timestamp('reviewed_at')->nullable();
    $table->timestamps();

    $table->index(['laboratory_id', 'report_type', 'period']);
});
```

## Kolom Detail

| Kolom | Tipe | Nullable | Deskripsi |
|-------|------|----------|-----------|
| `id` | bigint unsigned | Tidak | Primary key |
| `laboratory_id` | FK → laboratories | Tidak | Lab yang dilaporkan |
| `reviewed_by` | FK → users | Ya | PJ Lab yang mereview (nullable jika lab belum punya PJ Lab) |
| `report_type` | string | Tidak | Tipe laporan (saat ini hanya 'kepatuhan') |
| `period` | string | Tidak | Periode laporan format 'YYYY-MM' |
| `status` | enum | Tidak | 'pending', 'approved', 'rejected' |
| `notes` | text | Ya | Catatan review (wajib saat reject, opsional saat approve) |
| `reviewed_at` | timestamp | Ya | Waktu review dilakukan |

## Catatan Penting
- **Tidak menggunakan `unique`** pada (laboratory_id, report_type, period) karena satu lab bisa punya beberapa record di periode yang sama
- Menggunakan **`index`** saja untuk performa query
- `reviewed_by` di-set **nullable** untuk mengantisipasi lab yang belum punya PJ Lab saat Admin mengirim laporan
- `cascadeOnDelete` pada `laboratory_id` — jika lab dihapus, semua approval-nya ikut terhapus

## File yang Dibuat
- `database/migrations/xxxx_xx_xx_xxxxxx_create_report_approvals_table.php`

## Verifikasi
 - [x] Migration bisa dijalankan tanpa error
 - [x] Migration bisa di-rollback tanpa error
 - [x] Tabel `report_approvals` terbentuk dengan semua kolom
 - [x] Index pada (laboratory_id, report_type, period) aktif
 - [x] FK constraint ke `laboratories` dan `users` aktif
