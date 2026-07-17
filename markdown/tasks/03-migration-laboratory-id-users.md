# Task 03 — Migration: Tambah `laboratory_id` di `users`

## Sprint: 1 (Fondasi)
## Prioritas: Tinggi
## Dependensi: Task 01
## Estimasi: 10-15 menit

---

## Deskripsi

Tambah kolom `laboratory_id` sebagai foreign key ke tabel `laboratories` pada tabel `users`. Kolom ini digunakan untuk mengasosiasikan user dengan role `kepala_lab` ke laboratorium yang menjadi tanggung jawabnya. Nullable karena role `admin` dan `pimpinan` tidak terikat ke lab manapun.

## Langkah-langkah

1. Jalankan `php artisan make:migration add_laboratory_id_to_users_table`
2. Isi migration:

```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->foreignId('laboratory_id')->nullable()->after('password')->constrained('laboratories')->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropConstrainedForeignId('laboratory_id');
    });
}
```

## Aturan Penggunaan `laboratory_id` Per Role

| Role | `laboratory_id` | Keterangan |
|------|-----------------|------------|
| `admin` | NULL | Akses ke semua lab |
| `kepala_lab` | WAJIB ISI | Hanya akses lab sendiri |
| `pimpinan` | NULL | Akses semua lab (yang approved) |

## File yang Dibuat
- `database/migrations/xxxx_xx_xx_xxxxxx_add_laboratory_id_to_users_table.php`

## Verifikasi
- [ ] Migration bisa dijalankan tanpa error
- [ ] Kolom `laboratory_id` muncul di tabel `users`
- [ ] Foreign key constraint aktif ke tabel `laboratories`
