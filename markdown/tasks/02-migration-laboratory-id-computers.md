# Task 02 — Migration: Tambah `laboratory_id` di `computers`

## Sprint: 1 (Fondasi)
## Prioritas: Tinggi
## Dependensi: Task 01
## Estimasi: 10-15 menit

---

## Deskripsi

Tambah kolom `laboratory_id` sebagai foreign key ke tabel `laboratories` pada tabel `computers`. Kolom ini nullable karena komputer yang sudah ada belum memiliki asosiasi lab. Field `location` (string) yang sudah ada **tetap dipertahankan** untuk backward compatibility.

## Langkah-langkah

1. Jalankan `php artisan make:migration add_laboratory_id_to_computers_table`
2. Isi migration:

```php
public function up(): void
{
    Schema::table('computers', function (Blueprint $table) {
        $table->foreignId('laboratory_id')->nullable()->after('location')->constrained('laboratories')->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('computers', function (Blueprint $table) {
        $table->dropConstrainedForeignId('laboratory_id');
    });
}
```

## Catatan Penting
- Kolom `location` (string) **jangan dihapus** — tetap ada untuk backward compatibility
- `nullOnDelete()` artinya: jika lab dihapus, komputer tetap ada tapi `laboratory_id` jadi NULL
- Posisikan kolom `after('location')` agar rapi

## File yang Dibuat
- `database/migrations/xxxx_xx_xx_xxxxxx_add_laboratory_id_to_computers_table.php`

## Verifikasi
 - [x] Migration bisa dijalankan tanpa error
 - [x] Kolom `laboratory_id` muncul di tabel `computers`
 - [x] Foreign key constraint aktif ke tabel `laboratories`
 - [x] Kolom `location` yang lama masih ada
