# Task 16 — Update `AgentRegisterController`: Terima `laboratory_id`

## Sprint: 3 (Agent Scanner + Lab)
## Prioritas: Tinggi
## Dependensi: Sprint 1 selesai
## Estimasi: 30-45 menit

---

## Deskripsi

Update API endpoint registrasi agent (`POST /api/agent/register`) agar menerima dan menyimpan `laboratory_id` dari payload. Komputer yang teregistrasi akan langsung terasosiasi ke laboratorium yang benar.

## Langkah-langkah

### 1. Update Controller

Buka `app/Http/Controllers/Api/AgentRegisterController.php`.

**Tambah validasi `laboratory_id`:**
```php
$validated = $request->validate([
    // ... validasi yang sudah ada (hostname, mac_address, dll)
    'laboratory_id' => 'required|exists:laboratories,id',  // BARU
]);
```

**Tambah `laboratory_id` saat `updateOrCreate` Computer:**
```php
$computer = Computer::updateOrCreate(
    ['mac_address' => $validated['mac_address']],
    [
        'hostname' => $validated['hostname'],
        'laboratory_id' => $validated['laboratory_id'],  // BARU
        // ... field lainnya yang sudah ada
    ]
);
```

### 2. Pertimbangan Backward Compatibility

Jika masih ada agent lama yang belum di-update (tanpa `laboratory_id` di config.json), pertimbangkan untuk menggunakan `nullable` validation sementara:

```php
'laboratory_id' => 'nullable|exists:laboratories,id',
```

Ini memberikan waktu untuk re-deploy agent scanner ke semua komputer. Setelah semua agent di-update, ubah kembali ke `required`.

## Referensi
- Baca `AgentRegisterController` yang sudah ada untuk memahami alur registrasi
- Perhatikan pengecekan `X-Agent-Key` header yang sudah ada — jangan ubah
- Perhatikan logic revoke old tokens — jangan ubah

## File yang Dimodifikasi
- `app/Http/Controllers/Api/AgentRegisterController.php`

## Verifikasi
- [x] `POST /api/agent/register` dengan `laboratory_id` → komputer tersimpan dengan lab yang benar
- [x] `POST /api/agent/register` tanpa `laboratory_id` → error validasi (atau null jika backward compatible)
- [x] Komputer yang sudah ada di-update `laboratory_id` saat re-register
- [x] Token Sanctum tetap di-generate dengan benar
- [x] Header `X-Agent-Key` masih dicek (fungsionalitas lama tidak terganggu)
