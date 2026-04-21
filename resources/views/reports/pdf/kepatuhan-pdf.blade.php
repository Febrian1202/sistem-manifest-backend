<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Kepatuhan Lisensi</title>
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
        
        .badge { padding: 3px 8px; border-radius: 10px; font-size: 9px; font-weight: bold; }
        .badge-safe { background-color: #DCFCE7; color: #166534; }
        .badge-warning { background-color: #FEF9C3; color: #854d0e; }
        .badge-critical { background-color: #FEE2E2; color: #991b1b; }

        .signature { margin-top: 50px; float: right; width: 200px; text-align: center; }
        .sig-space { height: 60px; }
        
        .footer { position: fixed; bottom: 0; width: 100%; font-size: 8px; color: #999; text-align: center; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-box">[LOGO USN KOLAKA]</div>
        <h1 class="institution">Universitas Sembilanbelas November Kolaka</h1>
        <p class="system-name">Sistem Manifest — Laporan Kepatuhan Lisensi</p>
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
                <th>Nama Komputer</th>
                <th>IP Address</th>
                <th>Nama Software</th>
                <th width="80">Status</th>
                <th>Tanggal Deteksi</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reports as $index => $computer)
            @php
                $report = $computer->latestComplianceReport;
                $statusClass = 'badge-safe'; // Default
                $statusText = 'Belum Diperiksa';
                
                if ($report) {
                    $statusClass = $report->status === 'Safe' ? 'badge-safe' : ($report->status === 'Warning' ? 'badge-warning' : 'badge-critical');
                    $statusText = $report->status === 'Safe' ? 'Berlisensi' : ($report->status === 'Warning' ? 'Grace Period' : 'Tidak Berlisensi');
                }
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td><strong>{{ $computer->hostname ?? '-' }}</strong></td>
                <td>{{ $computer->ip_address ?? '-' }}</td>
                <td style="font-size: 8px;">
                    @php
                        $swNames = $report && is_array($report->violation_details) 
                            ? collect($report->violation_details)->pluck('software_name')->filter()->implode(', ')
                            : '-';
                    @endphp
                    {{ \Illuminate\Support\Str::limit($swNames, 40) }}
                </td>
                <td class="text-center">
                    <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                </td>
                <td>{{ $report && $report->scanned_at ? $report->scanned_at->format('d/m/Y H:i') : '-' }}</td>
                <td style="font-size: 8px;">
                    @if($report)
                        Unlicensed: {{ $report->unlicensed_count }} | Blacklist: {{ $report->blacklisted_count }}
                    @else
                        -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

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
