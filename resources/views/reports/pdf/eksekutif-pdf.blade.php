<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ringkasan Eksekutif</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #444; padding-bottom: 10px; }
        .institution { font-size: 16px; font-bold; margin: 0; }
        .system-name { font-size: 12px; color: #666; margin: 5px 0; }
        .logo-box { display: inline-block; padding: 8px; border: 2px solid #333; margin-bottom: 10px; font-weight: bold; }
        
        .metadata { margin-bottom: 20px; }
        .metadata table { width: 100%; border: none; }
        .metadata td { padding: 2px 0; }

        .stats-grid { width: 100%; margin-bottom: 30px; border-collapse: collapse; }
        .stats-grid td { width: 25%; padding: 15px; border: 1px solid #ddd; text-align: center; }
        .stat-label { font-size: 9px; text-transform: uppercase; color: #777; margin-bottom: 5px; }
        .stat-value { font-size: 18px; font-weight: bold; }
        
        .section-title { font-size: 12px; font-weight: bold; border-left: 3px solid #3b82f6; padding-left: 8px; margin: 20px 0 10px; }
        
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.data-table th { background-color: #f3f4f6; border: 1px solid #ddd; padding: 8px; text-align: left; font-weight: bold; }
        table.data-table td { border: 1px solid #ddd; padding: 8px; }
        .text-center { text-align: center; }
        
        .footer { position: fixed; bottom: 0; width: 100%; font-size: 8px; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 5px; }
        
        .signature { margin-top: 50px; float: right; width: 200px; text-align: center; }
        .sig-space { height: 60px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-box">[LOGO USN KOLAKA]</div>
        <h1 class="institution">Universitas Sembilanbelas November Kolaka</h1>
        <p class="system-name">Sistem Manifest — Laporan Ringkasan Eksekutif</p>
    </div>

    <div class="metadata">
        <table>
            <tr>
                <td width="80">Periode</td>
                <td width="10">:</td>
                <td>{{ $startDateStr }} s/d {{ $endDateStr }}</td>
            </tr>
            <tr>
                <td>Dicetak pada</td>
                <td>:</td>
                <td>{{ $print_date }}</td>
            </tr>
            <tr>
                <td>Dicetak oleh</td>
                <td>:</td>
                <td>{{ $printed_by }}</td>
            </tr>
        </table>
    </div>

    <table class="stats-grid">
        <tr>
            <td>
                <div class="stat-label">Total Komputer Aktif</div>
                <div class="stat-value">{{ $totalComputers }}</div>
            </td>
            <td>
                <div class="stat-label">Software Terdeteksi</div>
                <div class="stat-value">{{ $totalInstallations }}</div>
            </td>
            <td>
                <div class="stat-label">Tingkat Kepatuhan</div>
                <div class="stat-value" style="color: #059669;">{{ $complianceRate }}%</div>
            </td>
            <td>
                <div class="stat-label">Peringatan Kritis</div>
                <div class="stat-value" style="color: #dc2626;">{{ $criticalAlerts }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Status Kepatuhan Lisensi</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Status</th>
                <th class="text-center">Jumlah Komputer</th>
                <th class="text-center">Persentase</th>
            </tr>
        </thead>
        <tbody>
            @foreach($breakdown as $item)
            <tr>
                <td>{{ $item['status'] }}</td>
                <td class="text-center">{{ $item['count'] }}</td>
                <td class="text-center">{{ $item['pct'] }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">Top 5 Software Tidak Berlisensi</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Nama Software</th>
                <th class="text-center">Jumlah Komputer Terinstall</th>
            </tr>
        </thead>
        <tbody>
            @foreach($topUnlicensed as $sw)
            <tr>
                <td>{{ $sw->raw_name }}</td>
                <td class="text-center">{{ $sw->total }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top: 30px; font-style: italic; color: #555;">
        Laporan ini merupakan ringkasan kondisi kepatuhan lisensi perangkat lunak di lingkungan institusi pada periode yang tertera.
    </p>

    <div class="signature">
        <p>Mengetahui,</p>
        <div class="sig-space"></div>
        <p>( ________________________ )</p>
        <p>Pimpinan</p>
    </div>

    <div class="footer">
        Dokumen ini dicetak secara otomatis oleh Sistem Manifest
    </div>
</body>
</html>
