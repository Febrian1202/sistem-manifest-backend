# Task 28 — Update Computer Views: Tampilkan Nama Lab

## Sprint: 5 (Integrasi Pimpinan & Polish)
## Prioritas: Rendah
## Dependensi: Sprint 1 selesai
## Estimasi: 30-45 menit

---

## Deskripsi

Update view daftar komputer dan detail komputer agar menampilkan nama Laboratorium (dari relasi) selain atau sebagai pengganti string `location`.

## Langkah-langkah

### 1. Update Controller

Di `ComputerDataController` (atau controller yang menampilkan daftar komputer), eager load relasi `laboratory`:

```php
$computers = Computer::with('laboratory')
    ->paginate(20);
    // tambahkan ke query builder yang sudah ada
```

### 2. Update View Daftar Komputer

Cari view yang menampilkan daftar komputer (biasanya ada kolom "Lokasi").

Tambah/ganti kolom:

```blade
<td>
    @if($computer->laboratory)
        {{ $computer->laboratory->name }}
        <span class="text-sm text-gray-500">({{ $computer->laboratory->code }})</span>
    @elseif($computer->location)
        {{ $computer->location }}
    @else
        -
    @endif
</td>
```

### 3. Update View Detail Komputer

Pada halaman detail komputer (show), tambahkan info laboratorium:

```blade
<dt>Laboratorium</dt>
<dd>
    @if($computer->laboratory)
        {{ $computer->laboratory->name }} ({{ $computer->laboratory->code }})
    @else
        Belum ditentukan
    @endif
</dd>
```

### 4. (Opsional) Tambah Filter by Lab

Jika halaman daftar komputer sudah punya filter (dropdown lokasi), pertimbangkan menambah filter by laboratorium:

```html
<select name="laboratory_id">
    <option value="">Semua Laboratorium</option>
    @foreach($laboratories as $lab)
        <option value="{{ $lab->id }}">{{ $lab->name }}</option>
    @endforeach
</select>
```

## Referensi
- Cari view komputer di `resources/views/` — biasanya ada file yang berisi tabel hostname, OS, lokasi, dll
- Pertahankan kolom `location` yang lama sebagai fallback untuk komputer yang belum punya `laboratory_id`

## File yang Dimodifikasi
- `app/Http/Controllers/ComputerDataController.php` — tambah eager load `laboratory`
- View daftar komputer (index)
- View detail komputer (show)

## Verifikasi
- [ ] Daftar komputer menampilkan nama lab (bukan hanya string location)
- [ ] Komputer tanpa lab menampilkan fallback (location string atau "-")
- [ ] Detail komputer menampilkan info lab
- [ ] View tetap tampil benar untuk komputer tanpa `laboratory_id` (backward compatible)
