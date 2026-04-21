<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Status Lisensi</title>
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
        
        .status-filled { color: #dc2626; font-weight: bold; }
        .status-near { color: #854d0e; font-weight: bold; }
        .status-available { color: #166534; font-weight: bold; }

        .footer { position: fixed; bottom: 0; width: 100%; font-size: 8px; color: #999; text-align: center; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-box">[LOGO USN KOLAKA]</div>
        <h1 class="institution">Universitas Sembilanbelas November Kolaka</h1>
        <p class="system-name">Sistem Manifest — Laporan Status Lisensi</p>
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
                <th width="40" class="text-center">Quota</th>
                <th width="40" class="text-center">Used</th>
                <th width="40" class="text-center">Sisa</th>
                <th width="50" class="text-center">%</th>
                <th>Status</th>
                <th>Expired</th>
            </tr>
        </thead>
        <tbody>
            @foreach($licenses as $index => $license)
            @php
                $status = 'Tersedia';
                $statusColor = 'status-available';
                if ($license->remaining <= 0) { $status = 'Penuh'; $statusColor = 'status-filled'; }
                elseif ($license->usage_pct >= 80) { $status = 'Hampir Habis'; $statusColor = 'status-near'; }
                if ($license->expiry_date && $license->expiry_date->lt(now())) { $status = 'Kedaluwarsa'; $statusColor = 'status-filled'; }
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td><strong>{{ $license->catalog->normalized_name ?? '-' }}</strong></td>
                <td class="text-center">{{ $license->quota_limit }}</td>
                <td class="text-center">{{ $license->used_count }}</td>
                <td class="text-center">{{ $license->remaining }}</td>
                <td class="text-center">{{ $license->usage_pct }}%</td>
                <td class="{{ $statusColor }}">{{ $status }}</td>
                <td>{{ $license->expiry_date ? $license->expiry_date->format('d/m/Y') : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dokumen ini dicetak secara otomatis oleh Sistem Manifest
    </div>
</body>
</html>
