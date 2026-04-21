<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Inventaris Software</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #333; line-height: 1.3; }
        .header { text-align: center; margin-bottom: 20px; }
        .institution { font-size: 15px; font-weight: bold; margin: 0; }
        .system-name { font-size: 11px; color: #666; margin: 4px 0; }
        .logo-box { display: inline-block; padding: 6px; border: 1px solid #333; margin-bottom: 8px; font-weight: bold; }
        
        .metadata { margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .metadata table { width: 100%; border: none; }

        table.data-table { width: 100%; border-collapse: collapse; }
        table.data-table th { background-color: #f3f4f6; border: 1px solid #ddd; padding: 7px; text-align: left; font-weight: bold; }
        table.data-table td { border: 1px solid #ddd; padding: 7px; }
        .text-center { text-align: center; }
        .text-danger { color: #dc2626; font-weight: bold; }
        
        .footer { position: fixed; bottom: 0; width: 100%; font-size: 8px; color: #999; text-align: center; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-box">[LOGO USN KOLAKA]</div>
        <h1 class="institution">Universitas Sembilanbelas November Kolaka</h1>
        <p class="system-name">Sistem Manifest — Laporan Inventaris Software</p>
    </div>

    <div class="metadata">
        <table>
            <tr>
                <td width="80">Periode</td>
                <td>: {{ $startDateStr }} s/d {{ $endDateStr }}</td>
                <td align="right">Dicetak oleh: {{ $printed_by }}</td>
            </tr>
            <tr>
                <td>Dicetak pada</td>
                <td>: {{ $print_date }}</td>
                <td align="right">Halaman <span class="page-number"></span></td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th width="30">No</th>
                <th>Nama Software</th>
                <th>Versi</th>
                <th width="60" class="text-center">Komputer</th>
                <th>Status Lisensi</th>
                <th>Kategori</th>
            </tr>
        </thead>
        <tbody>
            @foreach($softwares as $index => $sw)
            @php
                $hasLicense = $sw->catalog && $sw->catalog->licenses->count() > 0;
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td><strong>{{ $sw->normalized_name }}</strong></td>
                <td>{{ $sw->version }}</td>
                <td class="text-center">{{ $sw->computer_count }}</td>
                <td class="{{ !$hasLicense ? 'text-danger' : '' }}">
                    {{ $hasLicense ? 'Berlisensi' : 'Tidak Berlisensi' }}
                </td>
                <td>{{ $sw->category }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dokumen ini dicetak secara otomatis oleh Sistem Manifest
    </div>
</body>
</html>
