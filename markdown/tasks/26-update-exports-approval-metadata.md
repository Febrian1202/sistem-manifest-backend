# Task 26 — Update Export Classes: Tambah Metadata Approval di PDF/Excel

## Sprint: 5 (Integrasi Pimpinan & Polish)
## Prioritas: Sedang
## Dependensi: Task 25
## Estimasi: 1-2 jam

---

## Deskripsi

Tambahkan metadata persetujuan PJ Lab pada dokumen laporan PDF dan Excel yang diexport. Metadata ini memberikan informasi bahwa laporan sudah melewati proses verifikasi.

## Langkah-langkah

### 1. Identifikasi File Export

Lihat di `app/Exports/` untuk menemukan class export yang ada. Juga lihat view PDF di `resources/views/` yang digunakan untuk generate dokumen.

### 2. Tambah Metadata di PDF

Pada setiap view PDF laporan, tambahkan section metadata approval:

```html
<!-- Di bagian header/footer PDF -->
<div class="approval-info">
    <h4>Status Verifikasi</h4>
    <table>
        <tr>
            <td>Laboratorium</td>
            <td>Status</td>
            <td>Diverifikasi Oleh</td>
            <td>Tanggal</td>
        </tr>
        @foreach($approvalData as $approval)
        <tr>
            <td>{{ $approval->laboratory->name }}</td>
            <td>{{ $approval->status === 'approved' ? 'Disetujui' : '-' }}</td>
            <td>{{ $approval->reviewer?->name ?? '-' }}</td>
            <td>{{ $approval->reviewed_at?->format('d/m/Y H:i') ?? '-' }}</td>
        </tr>
        @endforeach
    </table>
</div>
```

### 3. Tambah Metadata di Excel

Pada class export Excel, tambahkan sheet tambahan atau baris header:

```php
// Tambah info approval di header/footer sheet
// Atau tambah sheet "Informasi Verifikasi" terpisah
```

### 4. Pass Data Approval ke View/Export

Di `ReportController`, saat generate laporan, query data approval:

```php
$approvalData = ReportApproval::where('status', 'approved')
    ->where('report_type', 'kepatuhan')
    ->where('period', $period)
    ->with(['laboratory', 'reviewer'])
    ->get();

// Pass ke view PDF atau export class
```

## Referensi
- Lihat `app/Exports/` dan view PDF yang sudah ada
- Proyek menggunakan `maatwebsite/excel` dan `barryvdh/laravel-dompdf`
- Pertahankan styling yang sudah ada, hanya tambah section baru

## File yang Dimodifikasi
- View-view PDF di `resources/views/` (laporan eksekutif, komputer, software, kepatuhan, lisensi)
- Export classes di `app/Exports/` (jika perlu tambah metadata di Excel)
- `app/Http/Controllers/ReportController.php` — pass data approval ke view/export

## Verifikasi
- [ ] PDF menampilkan info "Diverifikasi oleh [nama PJ Lab] pada [tanggal]"
- [ ] Excel menyertakan metadata approval
- [ ] Metadata hanya muncul untuk lab yang sudah approved
- [ ] Format dokumen yang sudah ada tidak rusak
