# Task 15 — Update `AgentDownloadController`: Dropdown Lab + Config.json

## Sprint: 3 (Agent Scanner + Lab)
## Prioritas: Tinggi
## Dependensi: Sprint 1 selesai
## Estimasi: 1-2 jam

---

## Deskripsi

Update halaman download agent scanner agar Admin harus memilih laboratorium tujuan sebelum download. File `config.json` dalam ZIP bundle akan menyertakan `laboratoryId` dan `laboratoryName`.

## Langkah-langkah

### 1. Update Controller

Buka `app/Http/Controllers/AgentDownloadController.php`.

**Modifikasi `showDownloadPage()` (atau method yang menampilkan halaman download):**
```php
use App\Models\Laboratory;

public function showDownloadPage()
{
    $laboratories = Laboratory::orderBy('name')->get();
    return view('agent-download', compact('laboratories'));
    // sesuaikan nama view dengan yang sudah ada
}
```

**Modifikasi method download (yang generate ZIP):**
```php
public function download(Request $request)
{
    $request->validate([
        'laboratory_id' => 'required|exists:laboratories,id',
    ]);

    $lab = Laboratory::findOrFail($request->laboratory_id);

    // Saat generate config.json, tambahkan:
    $config = [
        'baseUrl' => config('app.url'),
        'registrationKey' => env('AGENT_REGISTRATION_KEY'),
        'laboratoryId' => $lab->id,         // BARU
        'laboratoryName' => $lab->name,     // BARU (referensi untuk admin)
    ];

    // ... lanjutkan proses ZIP seperti biasa
}
```

### 2. Update View

Tambahkan dropdown pilih laboratorium **sebelum** tombol download:

```html
<form action="{{ route('agent.download') }}" method="POST">
    @csrf
    <label>Pilih Laboratorium Tujuan</label>
    <select name="laboratory_id" required>
        <option value="">-- Pilih Laboratorium --</option>
        @foreach($laboratories as $lab)
            <option value="{{ $lab->id }}">
                {{ $lab->name }} ({{ $lab->code }})
            </option>
        @endforeach
    </select>

    <button type="submit">Download Scanner</button>
</form>
```

### 3. Ubah Narasi di Halaman

Ganti referensi "Agen Scanner" menjadi "Tools Pemindai" di halaman ini (lihat Task 29 untuk detail narasi).

## Referensi
- Baca `AgentDownloadController` yang sudah ada untuk memahami cara generate ZIP bundle
- Baca view download yang sudah ada untuk memahami layout
- File yang biasanya ada dalam ZIP: `scanner.ps1`, `setup_tasks.ps1`, `config.json`, `instruksi.txt`

## File yang Dimodifikasi
- `app/Http/Controllers/AgentDownloadController.php`
- View halaman download agent (cari file dengan string "Download" atau "Unduh" terkait agent/scanner)

## Verifikasi
- [ ] Halaman download menampilkan dropdown laboratorium
- [ ] Tidak bisa download tanpa memilih lab (validasi required)
- [ ] File `config.json` dalam ZIP berisi `laboratoryId` dan `laboratoryName`
- [ ] Format ZIP lainnya (scanner.ps1, instruksi.txt) tidak terganggu
