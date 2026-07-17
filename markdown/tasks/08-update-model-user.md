# Task 08 — Update Model: `User` (Tambah Relasi Lab)

## Sprint: 1 (Fondasi)
## Prioritas: Tinggi
## Dependensi: Task 03, Task 05
## Estimasi: 10 menit

---

## Deskripsi

Tambah relasi `belongsTo(Laboratory)` dan field `laboratory_id` ke fillable pada model `User`.

## Langkah-langkah

1. Buka `app/Models/User.php`
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
- **JANGAN** mengubah logika yang sudah ada di model
- `laboratory_id` bersifat nullable — hanya diisi untuk role `kepala_lab`
- Validasi bahwa `laboratory_id` wajib untuk `kepala_lab` dilakukan di controller/request, bukan di model

## File yang Dimodifikasi
- `app/Models/User.php`

## Verifikasi
- [ ] `$user->laboratory` mengembalikan instance `Laboratory` (atau null)
- [ ] `$user->laboratory_id` bisa diisi via mass assignment
- [ ] Semua fungsionalitas lama User (Spatie roles/permissions, LogsActivity) tetap berjalan
- [ ] Jalankan `composer test` — semua test yang ada harus tetap pass
