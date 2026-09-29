<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Perubahan Software</title>
    <style>
        @page { size: A4 landscape; margin: 1cm; }
        body { font-family: sans-serif; font-size: 10px; color: #333; line-height: 1.3; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #444; padding-bottom: 8px; }
        .institution { font-size: 14px; font-weight: bold; margin: 0; }
        .system-name { font-size: 11px; color: #666; margin: 2px 0; }

        .metadata { margin-bottom: 15px; }
        .metadata table { width: 100%; border: none; }
        .metadata td { padding: 2px 0; font-size: 9px; }

        .stats-grid { width: 100%; margin-bottom: 15px; border-collapse: collapse; }
        .stats-grid td { width: 20%; padding: 8px; border: 1px solid #ddd; text-align: center; background-color: #fafafa; }
        .stat-label { font-size: 8px; text-transform: uppercase; color: #666; margin-bottom: 3px; font-weight: bold; }
        .stat-value { font-size: 14px; font-weight: bold; }

        .section-title { font-size: 11px; font-weight: bold; border-left: 3px solid #7c3aed; padding-left: 6px; margin: 15px 0 8px; }

        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table.data-table th { background-color: #f3f4f6; border: 1px solid #ddd; padding: 6px 8px; text-align: left; font-weight: bold; font-size: 9px; }
        table.data-table td { border: 1px solid #ddd; padding: 5px 8px; font-size: 9px; }
        .text-center { text-align: center; }

        .badge-added { color: #15803d; font-weight: bold; }
        .badge-removed { color: #b91c1c; font-weight: bold; }
        .badge-changed { color: #d97706; font-weight: bold; }
        .badge-returned { color: #2563eb; font-weight: bold; }

        .footer { position: fixed; bottom: 0; width: 100%; font-size: 8px; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 4px; }
    </style>
</head>
<body>
    <div class="header">
        @if(file_exists(public_path('assets/logo-usn.png')))
            <img src="{{ public_path('assets/logo-usn.png') }}" style="height: 45px; width: auto; margin-bottom: 5px;" alt="Logo USN">
        @endif
        <h1 class="institution">Universitas Sembilanbelas November Kolaka</h1>
        <p class="system-name">Sistem Manifest — Laporan Rekapitulasi Perubahan Software</p>
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

    {{-- Ringkasan Metrics Cards --}}
    <table class="stats-grid">
        <tr>
            <td>
                <div class="stat-label">Total Perubahan</div>
                <div class="stat-value" style="color: #4b5563;">{{ $summary['total'] ?? 0 }}</div>
            </td>
            <td>
                <div class="stat-label">Software Baru</div>
                <div class="stat-value badge-added">{{ $summary['added'] ?? 0 }}</div>
            </td>
            <td>
                <div class="stat-label">Software Dihapus</div>
                <div class="stat-value badge-removed">{{ $summary['removed'] ?? 0 }}</div>
            </td>
            <td>
                <div class="stat-label">Versi Berubah</div>
                <div class="stat-value badge-changed">{{ $summary['version_changed'] ?? 0 }}</div>
            </td>
            <td>
                <div class="stat-label">Muncul Kembali</div>
                <div class="stat-value badge-returned">{{ $summary['returned'] ?? 0 }}</div>
            </td>
        </tr>
    </table>

    {{-- Tabel Perubahan Software --}}
    <div class="section-title">Daftar Riwayat Perubahan Terdeteksi</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="30" class="text-center">No</th>
                <th width="110">Waktu Terdeteksi</th>
                <th>Hostname</th>
                <th>Laboratorium</th>
                <th>Nama Software</th>
                <th width="100" class="text-center">Jenis Perubahan</th>
                <th>Detail Versi</th>
                <th>Vendor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($changes as $idx => $change)
                @php
                    $badgeClass = match($change['type'] ?? '') {
                        'added' => 'badge-added',
                        'removed' => 'badge-removed',
                        'version_changed' => 'badge-changed',
                        'returned' => 'badge-returned',
                        default => '',
                    };
                    $typeLabel = match($change['type'] ?? '') {
                        'added' => 'Software Baru',
                        'removed' => 'Dihapus',
                        'version_changed' => 'Perubahan Versi',
                        'returned' => 'Muncul Kembali',
                        default => ucfirst($change['type'] ?? '-'),
                    };
                    $versionInfo = match($change['type'] ?? '') {
                        'version_changed' => ($change['old_version'] ?? '-') . ' -> ' . ($change['new_version'] ?? '-'),
                        'added', 'returned' => $change['version'] ?? '-',
                        'removed' => $change['version'] ?? '-',
                        default => $change['version'] ?? '-',
                    };
                    $scannedAtStr = ! empty($change['scanned_at'])
                        ? ($change['scanned_at'] instanceof \Carbon\Carbon ? $change['scanned_at']->format('d/m/Y H:i') : date('d/m/Y H:i', strtotime($change['scanned_at'])))
                        : '-';
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>{{ $scannedAtStr }}</td>
                    <td><strong>{{ $change['computer_hostname'] ?? '-' }}</strong></td>
                    <td>{{ $change['laboratory_name'] ?? '-' }}</td>
                    <td>{{ $change['raw_name'] ?? '-' }}</td>
                    <td class="text-center {{ $badgeClass }}">{{ $typeLabel }}</td>
                    <td style="font-family: monospace;">{{ $versionInfo }}</td>
                    <td>{{ $change['vendor'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Tidak ada riwayat perubahan software pada periode dan laboratorium ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak secara otomatis melalui Sistem Manifest Lisensi Software — Universitas Sembilanbelas November Kolaka
    </div>
</body>
</html>
