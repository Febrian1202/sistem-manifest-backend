# Task 01 — Migration: Tabel `laboratories`

## Sprint: 1 (Fondasi)
## Prioritas: Tinggi
## Dependensi: Tidak ada
## Estimasi: 15-30 menit

---

## Deskripsi

Buat migration baru untuk tabel `laboratories`. Tabel ini merepresentasikan laboratorium sebagai entitas terstruktur, menggantikan field `location` (string bebas) di tabel `computers`.

## Langkah-langkah

1. Jalankan `php artisan make:migration create_laboratories_table`
2. Isi migration sesuai skema di bawah
3. Jalankan `php artisan migrate` untuk memverifikasi migration berhasil

## Skema Tabel

```php
Schema::create('laboratories', function (Blueprint $table) {
    $table->id();
    $table->string('name');              // "Lab Komputer 1", "Lab Jaringan", dll
    $table->string('code')->unique();    // "LAB-01", "LAB-JRG", dll
    $table->string('building')->nullable();  // Gedung
    $table->string('floor')->nullable();     // Lantai
    $table->text('description')->nullable();
    $table->timestamps();
});
```

## Kolom Detail

| Kolom | Tipe | Nullable | Unique | Deskripsi |
|-------|------|----------|--------|-----------|
| `id` | bigint unsigned | Tidak | Ya (PK) | Primary key |
| `name` | string | Tidak | Tidak | Nama lab lengkap |
| `code` | string | Tidak | Ya | Kode unik lab |
| `building` | string | Ya | Tidak | Nama gedung |
| `floor` | string | Ya | Tidak | Lantai |
| `description` | text | Ya | Tidak | Deskripsi tambahan |
| `created_at` | timestamp | Ya | Tidak | Timestamp Laravel |
| `updated_at` | timestamp | Ya | Tidak | Timestamp Laravel |

## File yang Dibuat
- `database/migrations/xxxx_xx_xx_xxxxxx_create_laboratories_table.php`

## Verifikasi
- [ ] Migration bisa dijalankan tanpa error (`php artisan migrate`)
- [ ] Migration bisa di-rollback tanpa error (`php artisan migrate:rollback`)
- [ ] Tabel `laboratories` terbentuk dengan semua kolom yang benar
