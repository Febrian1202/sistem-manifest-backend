# Task 06 — Model: `ReportApproval`

## Sprint: 1 (Fondasi)
## Prioritas: Tinggi
## Dependensi: Task 04
## Estimasi: 15-20 menit

---

## Deskripsi

Buat model Eloquent `ReportApproval` dengan fillable, casts, relasi, trait LogsActivity, dan factory.

## Langkah-langkah

1. Jalankan `php artisan make:model ReportApproval -f` (dengan factory)
2. Implementasi model sesuai spesifikasi

## Implementasi Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ReportApproval extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'laboratory_id',
        'reviewed_by',
        'report_type',
        'period',
        'status',
        'notes',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Laboratory::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'notes', 'reviewed_at'])
            ->logOnlyDirty()
            ->useLogName('report_approval');
    }
}
```

## Factory

```php
public function definition(): array
{
    return [
        'laboratory_id' => Laboratory::factory(),
        'reviewed_by' => User::factory(),
        'report_type' => 'kepatuhan',
        'period' => now()->format('Y-m'),
        'status' => 'pending',
        'notes' => null,
        'reviewed_at' => null,
    ];
}

public function approved(): static
{
    return $this->state(fn () => [
        'status' => 'approved',
        'reviewed_at' => now(),
        'notes' => 'Data sudah lengkap dan valid.',
    ]);
}

public function rejected(): static
{
    return $this->state(fn () => [
        'status' => 'rejected',
        'reviewed_at' => now(),
        'notes' => 'Data belum lengkap, perlu scan ulang.',
    ]);
}
```

## Referensi Trait LogsActivity
- Lihat cara model lain di proyek ini menggunakan `LogsActivity` (contoh: `Computer.php`, `LicenseInventory.php`, `SoftwareCatalog.php`)
- Sesuaikan implementasi `getActivitylogOptions()` agar konsisten dengan pola yang sudah ada

## File yang Dibuat
- `app/Models/ReportApproval.php`
- `database/factories/ReportApprovalFactory.php`

## Verifikasi
 - [x] Model bisa di-import tanpa error
 - [x] Relasi `laboratory()` dan `reviewer()` berfungsi
 - [x] Cast `reviewed_at` mengembalikan Carbon instance
 - [x] Activity log tercatat saat status berubah
 - [x] Factory states (`approved()`, `rejected()`) berfungsi
