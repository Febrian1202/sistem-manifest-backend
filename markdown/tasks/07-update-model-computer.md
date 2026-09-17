# Task 07 — Update Model: `Computer` (Tambah Relasi Lab)

## Sprint: 1 (Fondasi)
## Prioritas: Tinggi
## Dependensi: Task 02, Task 05
## Estimasi: 10 menit

---

## Deskripsi

Tambah relasi `belongsTo(Laboratory)` dan field `laboratory_id` ke fillable pada model `Computer`.

## Langkah-langkah

1. Buka `app/Models/Computer.php`
2. Tambah `'laboratory_id'` ke array `$fillable`
3. Tambah method relasi `laboratory()`
4. Tambah import `use` untuk model `Laboratory`

## Perubahan

```php
// Di $fillable, tambah:
'laboratory_id',

// Tambah method relasi:
public function laboratory(): BelongsTo
{
    return $this->belongsTo(Laboratory::class);
}
```

## Catatan Penting
- **JANGAN** mengubah logika yang sudah ada di model (terutama yang terkait Sanctum/HasApiTokens)
- Model `Computer` extends `Authenticatable` (bukan `Model` biasa) — jangan ubah ini
- Hanya tambah `laboratory_id` ke fillable dan tambah method `laboratory()`

## File yang Dimodifikasi
- `app/Models/Computer.php`

## Verifikasi
 - [x] `$computer->laboratory` mengembalikan instance `Laboratory` (atau null)
 - [x] `$computer->laboratory_id` bisa diisi via mass assignment
 - [x] Semua fungsionalitas lama Computer (Sanctum token, relasi softwareDiscoveries, complianceReports) tetap berjalan
 - [x] Jalankan `composer test` — semua test yang ada harus tetap pass
