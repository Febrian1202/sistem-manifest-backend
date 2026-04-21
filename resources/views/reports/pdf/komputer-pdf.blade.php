<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Inventaris Komputer</title>
    <style>
        @page { size: A4 landscape; margin: 1cm; }
        body { font-family: sans-serif; font-size: 10px; color: #333; line-height: 1.2; }
        .header { text-align: center; margin-bottom: 15px; }
        .institution { font-size: 14px; font-weight: bold; margin: 0; }
        .system-name { font-size: 11px; color: #666; margin: 2px 0; }
        .logo-box { display: inline-block; padding: 5px; border: 1px solid #333; margin-bottom: 5px; font-weight: bold; font-size: 9px; }
        
        .metadata { margin-bottom: 15px; }
        .metadata table { width: 100%; border: none; }
        .metadata td { padding: 1px 0; }

        table.data-table { width: 100%; border-collapse: collapse; }
        table.data-table th { background-color: #f3f4f6; border: 1px solid #ddd; padding: 6px; text-align: left; font-weight: bold; font-size: 9px; }
        table.data-table td { border: 1px solid #ddd; padding: 6px; }
        .text-center { text-align: center; }
        .bg-inactive { background-color: #FEE2E2; }
        
        .footer { position: fixed; bottom: -20px; width: 100%; font-size: 8px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-box">[LOGO USN KOLAKA]</div>
        <h1 class="institution">Universitas Sembilanbelas November Kolaka</h1>
        <p class="system-name">Sistem Manifest — Laporan Inventaris Komputer</p>
    </div>

    <div class="metadata">
        <table>
            <tr>
                <td width="80">Periode</td>
                <td width="10">:</td>
                <td>{{ $startDateStr }} s/d {{ $endDateStr }}</td>
                <td align="right">Halaman <span class="page-number"></span></td>
            </tr>
            <tr>
                <td>Dicetak pada</td>
                <td>:</td>
                <td>{{ $print_date }}</td>
                <td align="right">Dicetak oleh: {{ $printed_by }}</td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th width="30">No</th>
                <th>Hostname</th>
                <th>IP Address</th>
                <th>MAC Address</th>
                <th>CPU</th>
                <th width="40">RAM</th>
                <th>OS</th>
                <th width="50">Status</th>
                <th>Last Seen</th>
                <th width="40">Soft.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($computers as $index => $computer)
            @php
                $isInactive = $computer->last_seen_at && $computer->last_seen_at->lt(now()->subDays(7));
            @endphp
            <tr class="{{ $isInactive ? 'bg-inactive' : '' }}">
                <td class="text-center">{{ $index + 1 }}</td>
                <td><strong>{{ $computer->hostname }}</strong></td>
                <td>{{ $computer->ip_address }}</td>
                <td>{{ $computer->mac_address }}</td>
                <td>{{ \Illuminate\Support\Str::limit($computer->processor, 30) }}</td>
                <td class="text-center">{{ $computer->ram_gb }} GB</td>
                <td>{{ $computer->os_name }}</td>
                <td class="text-center">
                    {{ $isInactive ? 'Tidak Aktif' : 'Aktif' }}
                </td>
                <td>{{ $computer->last_seen_at ? $computer->last_seen_at->format('d/m/Y H:i') : '-' }}</td>
                <td class="text-center">{{ $computer->softwares_count }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dokumen ini dicetak secara otomatis oleh Sistem Manifest
    </div>
</body>
</html>
