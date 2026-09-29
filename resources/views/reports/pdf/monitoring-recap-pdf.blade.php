<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Monitoring Berkala</title>
    <style>
        @page { size: A4 portrait; margin: 1cm; }
        body { font-family: sans-serif; font-size: 10px; color: #333; line-height: 1.3; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #444; padding-bottom: 8px; }
        .institution { font-size: 14px; font-weight: bold; margin: 0; }
        .system-name { font-size: 11px; color: #666; margin: 2px 0; }

        .metadata { margin-bottom: 15px; }
        .metadata table { width: 100%; border: none; }
        .metadata td { padding: 2px 0; font-size: 9px; }

        .stats-grid { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .stats-grid td { width: 20%; padding: 10px; border: 1px solid #ddd; text-align: center; background-color: #fafafa; }
        .stat-label { font-size: 8px; text-transform: uppercase; color: #666; margin-bottom: 4px; font-weight: bold; }
        .stat-value { font-size: 15px; font-weight: bold; color: #1e3a8a; }

        .section-title { font-size: 11px; font-weight: bold; border-left: 3px solid #2563eb; padding-left: 6px; margin: 15px 0 8px; }

        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table.data-table th { background-color: #f3f4f6; border: 1px solid #ddd; padding: 6px 8px; text-align: left; font-weight: bold; font-size: 9px; }
        table.data-table td { border: 1px solid #ddd; padding: 5px 8px; font-size: 9px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .badge-success { color: #15803d; font-weight: bold; }
        .badge-danger { color: #b91c1c; font-weight: bold; }

        .footer { position: fixed; bottom: 0; width: 100%; font-size: 8px; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 4px; }
    </style>
</head>
<body>
    <div class="header">
        @if(file_exists(public_path('assets/logo-usn.png')))
            <img src="{{ public_path('assets/logo-usn.png') }}" style="height: 50px; width: auto; margin-bottom: 6px;" alt="Logo USN">
        @endif
        <h1 class="institution">Universitas Sembilanbelas November Kolaka</h1>
        <p class="system-name">Sistem Manifest — Laporan Rekapitulasi Monitoring Berkala</p>
    </div>

    <div class="metadata">
        <table>
            <tr>
                <td width="90">Periode</td>
                <td width="10">:</td>
                <td>{{ $startDateStr }} s/d {{ $endDateStr }}</td>
                <td align="right">Dicetak pada: {{ $print_date }}</td>
            </tr>
            <tr>
                <td>Laboratorium</td>
                <td>:</td>
                <td>{{ $selectedLabName ?? 'Semua Laboratorium' }}</td>
                <td align="right">Dicetak oleh: {{ $printed_by }}</td>
            </tr>
        </table>
    </div>

    {{-- Ringkasan Statistik --}}
    <table class="stats-grid">
        <tr>
            <td>
                <div class="stat-label">Total Komputer</div>
                <div class="stat-value">{{ $summary['total_computers'] ?? 0 }}</div>
            </td>
            <td>
                <div class="stat-label">Total Sesi Scan</div>
                <div class="stat-value">{{ $summary['total_scans'] ?? 0 }}</div>
            </td>
            <td>
                <div class="stat-label">Scan Berhasil</div>
                <div class="stat-value" style="color: #15803d;">{{ $summary['successful_scans'] ?? 0 }}</div>
            </td>
            <td>
                <div class="stat-label">Scan Gagal</div>
                <div class="stat-value" style="color: #b91c1c;">{{ $summary['failed_scans'] ?? 0 }}</div>
            </td>
            <td>
                <div class="stat-label">Success Rate</div>
                <div class="stat-value">{{ $summary['success_rate'] ?? 0 }}%</div>
            </td>
        </tr>
    </table>

    {{-- Ringkasan Per Laboratorium --}}
    <div class="section-title">Ringkasan Aktivitas Per Laboratorium</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="30" class="text-center">No</th>
                <th>Laboratorium</th>
                <th width="80" class="text-center">Jml Komputer</th>
                <th width="70" class="text-center">Total Scan</th>
                <th width="70" class="text-center">Berhasil</th>
                <th width="60" class="text-center">Gagal</th>
                <th width="80" class="text-center">Success Rate</th>
            </tr>
        </thead>
        <tbody>
            @forelse($labStats as $idx => $lab)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td><strong>{{ $lab->name }}</strong> ({{ $lab->code }})</td>
                    <td class="text-center">{{ $lab->computers_count }}</td>
                    <td class="text-center">{{ $lab->total_scans }}</td>
                    <td class="text-center badge-success">{{ $lab->successful_scans }}</td>
                    <td class="text-center badge-danger">{{ $lab->failed_scans }}</td>
                    <td class="text-center font-bold">{{ $lab->success_rate }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Tidak ada data laboratorium pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Detail Status Per Komputer --}}
    <div class="section-title">Status Monitoring Per Komputer</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="25" class="text-center">No</th>
                <th>Hostname</th>
                <th>Laboratorium</th>
                <th>IP Address</th>
                <th width="50" class="text-center">Total</th>
                <th width="50" class="text-center">Sukses</th>
                <th width="50" class="text-center">Gagal</th>
                <th width="90" class="text-center">Scan Terakhir</th>
                <th width="65" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($computers as $idx => $comp)
                @php
                    $latestScan = $comp->scanSessions->first();
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td><strong>{{ $comp->hostname }}</strong></td>
                    <td>{{ $comp->laboratory?->name ?? '-' }}</td>
                    <td>{{ $comp->ip_address ?? '-' }}</td>
                    <td class="text-center">{{ $comp->total_scans ?? 0 }}</td>
                    <td class="text-center badge-success">{{ $comp->successful_scans ?? 0 }}</td>
                    <td class="text-center badge-danger">{{ $comp->failed_scans ?? 0 }}</td>
                    <td class="text-center">
                        {{ $latestScan && $latestScan->started_at ? $latestScan->started_at->format('d/m/Y H:i') : ($comp->last_seen_at ? $comp->last_seen_at->format('d/m/Y H:i') : '-') }}
                    </td>
                    <td class="text-center">
                        @if($latestScan)
                            @if($latestScan->status === 'completed')
                                <span class="badge-success">Berhasil</span>
                            @elseif($latestScan->status === 'failed')
                                <span class="badge-danger">Gagal</span>
                            @else
                                <span>{{ ucfirst($latestScan->status) }}</span>
                            @endif
                        @else
                            <span style="color: #9ca3af;">Belum Ada</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data komputer pada filter ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak secara otomatis melalui Sistem Manifest Lisensi Software — Universitas Sembilanbelas November Kolaka
    </div>
</body>
</html>
