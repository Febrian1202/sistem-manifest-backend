<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Preview Laporan Kepatuhan — {{ $lab->name }}</title>
    <style>
        @page {
            margin: 30px 40px;
        }
        body {
            font-family: sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.3;
        }
        .watermark {
            position: fixed;
            top: 35%;
            left: 5%;
            right: 5%;
            text-align: center;
            font-size: 42px;
            font-weight: bold;
            color: rgba(220, 38, 38, 0.12);
            transform: rotate(-30deg);
            z-index: -1000;
            text-transform: uppercase;
            letter-spacing: 4px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .institution {
            font-size: 14px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        .system-name {
            font-size: 11px;
            color: #444;
            margin: 3px 0;
        }
        .report-title {
            font-size: 12px;
            font-weight: bold;
            color: #111;
            margin-top: 6px;
            text-transform: uppercase;
        }
        
        .metadata-box {
            margin-bottom: 15px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 8px 12px;
        }
        .metadata-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .metadata-box td {
            padding: 3px 0;
            font-size: 9px;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            margin: 12px 0 6px 0;
            color: #1e293b;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.data-table th {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 6px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
        }
        table.data-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            font-size: 8.5px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
        .badge {
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
            display: inline-block;
        }
        .badge-safe { background-color: #dcfce7; color: #166534; }
        .badge-warning { background-color: #fef9c3; color: #854d0e; }
        .badge-critical { background-color: #fee2e2; color: #991b1b; }

        .stat-grid {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }
        .stat-grid td {
            width: 25%;
            padding: 6px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            text-align: center;
        }
        .stat-label { font-size: 8px; color: #64748b; text-transform: uppercase; }
        .stat-value { font-size: 13px; font-weight: bold; color: #0f172a; margin-top: 2px; }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px dashed #cbd5e1;
            padding-top: 4px;
        }
    </style>
</head>
<body>

    {{-- Watermark Status --}}
    <div class="watermark">
        DRAF PREVIEW — {{ strtoupper($reportApproval->status) }}
    </div>

    <div class="header">
        <h1 class="institution">Universitas Sembilanbelas November Kolaka</h1>
        <p class="system-name">Sistem Manifest Kepatuhan Aset & Lisensi Software (UniLicense)</p>
        <p class="report-title">Draf Laporan Kepatuhan Perangkat Lunak Laboratorium</p>
    </div>

    <div class="metadata-box">
        <table>
            <tr>
                <td width="22%"><strong>Nama Laboratorium</strong></td>
                <td width="38%">: {{ $lab->name }} ({{ $lab->code }})</td>
                <td width="18%"><strong>Periode Evaluasi</strong></td>
                <td width="22%">: {{ \Carbon\Carbon::createFromFormat('Y-m', $period)->translatedFormat('F Y') }}</td>
            </tr>
            <tr>
                <td><strong>Lokasi Gedung / Lantai</strong></td>
                <td>: {{ $lab->building ?? 'Gedung -' }} Lt. {{ $lab->floor ?? '-' }}</td>
                <td><strong>Status Laporan</strong></td>
                <td>: <span style="text-transform: uppercase; font-weight: bold; color: {{ $reportApproval->status === 'approved' ? '#166534' : ($reportApproval->status === 'rejected' ? '#991b1b' : '#d97706') }};">{{ $reportApproval->status }}</span></td>
            </tr>
            <tr>
                <td><strong>Dicetak Oleh</strong></td>
                <td>: {{ $printedBy }}</td>
                <td><strong>Waktu Cetak</strong></td>
                <td>: {{ $printedAt }} WITA</td>
            </tr>
        </table>
    </div>

    {{-- Ringkasan Statistik --}}
    <table class="stat-grid">
        <tr>
            <td>
                <div class="stat-label">Total Komputer</div>
                <div class="stat-value">{{ $computers->count() }}</div>
            </td>
            <td>
                <div class="stat-label">OS Berlisensi</div>
                <div class="stat-value">{{ $computers->where('os_license_status', 'Licensed')->count() }}</div>
            </td>
            <td>
                <div class="stat-label">Temuan Pelanggaran</div>
                <div class="stat-value" style="color: {{ $complianceData->where('status', '!=', 'Berlisensi')->count() > 0 ? '#dc2626' : '#16a34a' }};">
                    {{ $complianceData->where('status', '!=', 'Berlisensi')->count() }}
                </div>
            </td>
            <td>
                <div class="stat-label">Tingkat Kepatuhan OS</div>
                <div class="stat-value">
                    {{ $computers->count() > 0 ? round(($computers->where('os_license_status', 'Licensed')->count() / $computers->count()) * 100, 1) : 0 }}%
                </div>
            </td>
        </tr>
    </table>

    {{-- Daftar Temuan Audit --}}
    <div class="section-title">A. Daftar Temuan Pelanggaran & Software Tanpa Lisensi Valid</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="20" class="text-center">No</th>
                <th width="120">Komputer</th>
                <th>Software Terdeteksi</th>
                <th width="60">Versi</th>
                <th width="90" class="text-center">Status Temuan</th>
                <th>Keterangan / Tindak Lanjut</th>
            </tr>
        </thead>
        <tbody>
            @php
                $violations = $complianceData->where('status', '!=', 'Berlisensi');
            @endphp
            @forelse ($violations as $idx => $item)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>
                        <strong>{{ $item->computer->hostname ?? '-' }}</strong><br>
                        <span style="color: #64748b; font-size: 7.5px;">{{ $item->computer->ip_address ?? '-' }}</span>
                    </td>
                    <td>{{ $item->software_name }}</td>
                    <td>{{ $item->software_version ?? '-' }}</td>
                    <td class="text-center">
                        <span class="badge badge-critical">{{ $item->status }}</span>
                    </td>
                    <td>{{ $item->keterangan ?: 'Perlu lisensi atau uninstal software.' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 10px; color: #166534;">
                        Semua perangkat lunak terdeteksi dinyatakan patuh dan berlisensi resmi.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Daftar Komputer --}}
    <div class="section-title">B. Inventaris Komputer di Laboratorium</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="20" class="text-center">No</th>
                <th width="120">Hostname</th>
                <th>Sistem Operasi</th>
                <th width="80" class="text-center">Status Lisensi OS</th>
                <th width="70" class="text-center">Total Software</th>
                <th width="90">Terakhir Scan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($computers as $idx => $comp)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>
                        <strong>{{ $comp->hostname }}</strong><br>
                        <span style="color: #64748b; font-size: 7.5px;">{{ $comp->ip_address ?? '-' }}</span>
                    </td>
                    <td>{{ $comp->os_name ?? '-' }} ({{ $comp->os_architecture ?? '-' }})</td>
                    <td class="text-center">
                        <span class="badge {{ $comp->os_license_status === 'Licensed' ? 'badge-safe' : 'badge-critical' }}">
                            {{ $comp->os_license_status ?? 'Unknown' }}
                        </span>
                    </td>
                    <td class="text-center">{{ $comp->softwares->count() }}</td>
                    <td>{{ $comp->last_seen_at ? $comp->last_seen_at->format('d/m/Y H:i') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 10px;">
                        Tidak ada unit komputer yang terdaftar di laboratorium ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Catatan Review --}}
    @if ($reportApproval->notes)
        <div class="section-title">C. Catatan Evaluasi Penanggung Jawab Laboratorium</div>
        <div style="background-color: #f8fafc; border: 1px solid #cbd5e1; padding: 8px; font-size: 9px; margin-bottom: 20px;">
            <strong>Catatan:</strong> {{ $reportApproval->notes }}
        </div>
    @endif

    <div class="footer">
        Dokumen ini merupakan draf preview sistem dan digenerate otomatis oleh Sistem Manifest USN Kolaka pada {{ $printedAt }}.
    </div>

</body>
</html>
