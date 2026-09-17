# Task 05 — Model: `Laboratory`

## Sprint: 1 (Fondasi)
## Prioritas: Tinggi
## Dependensi: Task 01
## Estimasi: 15-20 menit

---

## Deskripsi

Buat model Eloquent `Laboratory` dengan fillable, relasi, dan factory.

## Langkah-langkah

1. Jalankan `php artisan make:model Laboratory -f` (dengan factory)
2. Implementasi model sesuai spesifikasi

## Implementasi Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Laboratory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'building',
        'floor',
        'description',
    ];

    public function computers(): HasMany
    {
        return $this->hasMany(Computer::class);
    }

    public function penanggungJawab(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function reportApprovals(): HasMany
    {
        return $this->hasMany(ReportApproval::class);
    }
}
```

## Factory

Buat factory `LaboratoryFactory` di `database/factories/LaboratoryFactory.php`:

```php
public function definition(): array
{
    return [
        'name' => 'Lab ' . fake()->word(),
        'code' => 'LAB-' . strtoupper(fake()->unique()->lexify('???')),
        'building' => fake()->optional()->word(),
        'floor' => fake()->optional()->randomElement(['1', '2', '3']),
        'description' => fake()->optional()->sentence(),
    ];
}
```

## File yang Dibuat
- `app/Models/Laboratory.php`
- `database/factories/LaboratoryFactory.php`

## Verifikasi
 - [x] Model bisa di-import tanpa error
 - [x] `Laboratory::create([...])` berhasil menyimpan data
 - [x] Relasi `computers()`, `penanggungJawab()`, `reportApprovals()` bisa diakses
 - [x] Factory bisa menghasilkan instance: `Laboratory::factory()->create()`
