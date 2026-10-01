<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Analisis Kebutuhan dan Alokasi Lisensi Software</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #333; line-height: 1.4; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #222; padding-bottom: 10px; }
        .institution { font-size: 15px; font-weight: bold; margin: 0; text-transform: uppercase; }
        .system-name { font-size: 12px; font-weight: bold; color: #1e3a8a; margin: 4px 0 2px; }
        .report-subtitle { font-size: 9px; color: #666; font-style: italic; margin: 0; }
        
        .metadata { margin-bottom: 15px; font-size: 9.5px; }
        .metadata table { width: 100%; border: none; }
        .metadata td { padding: 2px 0; }
        
        .section-title { font-size: 11px; font-weight: bold; border-left: 3px solid #1e3a8a; padding-left: 6px; margin: 16px 0 8px; color: #1e3a8a; text-transform: uppercase; }
        
        .stats-grid { width: 100%; margin-bottom: 15px; border-collapse: collapse; }
        .stats-grid td { width: 16.66%; padding: 8px 4px; border: 1px solid #ddd; text-align: center; background-color: #f9fafb; }
        .stat-label { font-size: 8px; text-transform: uppercase; color: #6b7280; margin-bottom: 3px; font-weight: 600; }
        .stat-value { font-size: 14px; font-weight: bold; color: #111827; }
        
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 9px; }
        table.data-table th { background-color: #f3f4f6; border: 1px solid #d1d5db; padding: 6px 4px; font-weight: bold; text-align: left; }
        table.data-table td { border: 1px solid #e5e7eb; padding: 5px 4px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        
        .badge { display: inline-block; padding: 2px 5px; font-size: 8px; font-weight: bold; border-radius: 3px; }
        .badge-deficit { background-color: #fee2e2; color: #991b1b; }
        .badge-surplus { background-color: #dbeafe; color: #1e40af; }
        .badge-optimal { background-color: #dcfce7; color: #166534; }
        
        .faculty-header { background-color: #e0e7ff; font-weight: bold; padding: 5px; font-size: 9.5px; border: 1px solid #c7d2fe; margin-top: 8px; }
        
        .scientific-note { font-style: italic; color: #4b5563; font-size: 8.5px; margin: 15px 0; padding: 6px 10px; background-color: #f9fafb; border-left: 2px solid #9ca3af; }
        
        .footer { position: fixed; bottom: 0; width: 100%; font-size: 7.5px; color: #9ca3af; text-align: center; border-top: 1px solid #eee; padding-top: 4px; }
        
        .signature-box { margin-top: 30px; float: right; width: 220px; text-align: center; font-size: 9.5px; page-break-inside: avoid; }
        .sig-space { height: 50px; }
        .clear { clear: both; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header">
        @if(file_exists(public_path('assets/logo-usn.png')))
            <img src="{{ public_path('assets/logo-usn.png') }}" style="height: 50px; width: auto; margin-bottom: 5px;" alt="Logo USN">
        @endif
        <h1 class="institution">Universitas Sembilanbelas November Kolaka</h1>
        <div class="system-name">Laporan Analisis Kebutuhan dan Alokasi Lisensi Software</div>
        <p class="report-subtitle">Dokumen Pendukung Pengambilan Keputusan Pengelolaan dan Pengadaan Lisensi</p>
    </div>

    <div class="metadata">
        <table>
            <tr>
                <td width="100">Dicetak Pada</td>
                <td width="10">:</td>
                <td>{{ $print_date }}</td>
                <td width="100">Otoritas Cetak</td>
                <td width="10">:</td>
                <td>{{ $printed_by }}</td>
            </tr>
            <tr>
                <td>Periode Evaluasi</td>
                <td>:</td>
                <td>{{ $period }}</td>
                <td>Cakupan Audit</td>
                <td>:</td>
                <td>{{ $isPimpinan ? 'Laboratorium Terverifikasi (Approved)' : 'Seluruh Unit Universitas' }}</td>
            </tr>
        </table>
    </div>

    {{-- Bagian I: Ringkasan Kapasitas Lisensi Universitas --}}
    <div class="section-title">Bagian I: Ringkasan Kapasitas Lisensi Universitas</div>
    <table class="stats-grid">
        <tr>
            <td>
                <div class="stat-label">Software Komersial</div>
                <div class="stat-value">{{ $summary['total_commercial_software'] }}</div>
            </td>
            <td>
                <div class="stat-label">Hak USN (Owned)</div>
                <div class="stat-value" style="color: #059669;">{{ $summary['total_owned'] }}</div>
            </td>
            <td>
                <div class="stat-label">Total Alokasi Seat</div>
                <div class="stat-value">{{ $summary['total_allocated'] }}</div>
            </td>
            <td>
                <div class="stat-label">Sisa Belum Dialokasi</div>
                <div class="stat-value" style="color: #2563eb;">{{ $summary['total_unallocated'] }}</div>
            </td>
            <td>
                <div class="stat-label">Total Terpasang</div>
                <div class="stat-value">{{ $summary['total_installed'] }}</div>
            </td>
            <td>
                <div class="stat-label">Defisit Bersih USN</div>
                <div class="stat-value" style="color: {{ $summary['total_deficit'] > 0 ? '#dc2626' : '#059669' }};">
                    {{ $summary['total_deficit'] }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Bagian II: Distribusi & Kepatuhan per Fakultas --}}
    <div class="section-title">Bagian II: Distribusi & Kepatuhan per Fakultas</div>
    @foreach ($facultyDistributions as $dist)
        <div class="faculty-header">
            {{ $dist['faculty']->name }} ({{ $dist['faculty']->code }}) &mdash;
            <span style="font-weight: normal; font-size: 8.5px;">
                Alokasi: <b>{{ $dist['total_allocated'] }}</b> | 
                Terpasang: <b>{{ $dist['total_installed'] }}</b> | 
                Defisit: <b style="color: {{ $dist['total_deficit'] > 0 ? '#dc2626' : '#333' }};">{{ $dist['total_deficit'] }}</b> | 
                Surplus: <b style="color: #2563eb;">{{ $dist['total_surplus'] }}</b>
            </span>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="20" class="text-center">No</th>
                    <th>Nama Software</th>
                    <th width="50" class="text-center">Alokasi</th>
                    <th width="50" class="text-center">Terpasang</th>
                    <th width="45" class="text-center">Defisit</th>
                    <th width="45" class="text-center">Surplus</th>
                    <th width="75" class="text-center">Status</th>
                    <th>Rekomendasi Alokasi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($dist['breakdown'] as $idx => $item)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td class="font-bold">{{ $item['software_name'] }}</td>
                        <td class="text-center">{{ $item['allocated'] }}</td>
                        <td class="text-center">{{ $item['installed'] }}</td>
                        <td class="text-center font-bold" style="color: {{ $item['deficit'] > 0 ? '#dc2626' : '#6b7280' }};">
                            {{ $item['deficit'] }}
                        </td>
                        <td class="text-center font-bold" style="color: {{ $item['surplus'] > 0 ? '#2563eb' : '#6b7280' }};">
                            {{ $item['surplus'] }}
                        </td>
                        <td class="text-center">
                            @if ($item['deficit'] > 0)
                                <span class="badge badge-deficit">Defisit</span>
                            @elseif ($item['surplus'] > 0)
                                <span class="badge badge-surplus">Surplus</span>
                            @else
                                <span class="badge badge-optimal">Optimal</span>
                            @endif
                        </td>
                        <td>{{ $item['recommendation'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center" style="color: #6b7280;">Tidak ada software komersial tercatat di fakultas ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endforeach

    {{-- Bagian III: Rekapitulasi Defisit & Kebutuhan Pengadaan --}}
    <div class="section-title" style="page-break-before: auto;">Bagian III: Rekapitulasi Defisit & Kebutuhan Pengadaan (Procurement Insights)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="20" class="text-center">No</th>
                <th>Nama Software</th>
                <th width="45" class="text-center">Hak USN</th>
                <th width="45" class="text-center">Alokasi</th>
                <th width="50" class="text-center">Terpasang</th>
                <th width="50" class="text-center">Defisit USN</th>
                <th width="110">Sebaran Defisit</th>
                <th>Catatan & Rekomendasi Pengadaan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($procurementInsights as $idx => $insight)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="font-bold">{{ $insight['software_name'] }}</td>
                    <td class="text-center font-bold" style="color: #059669;">{{ $insight['owned'] }}</td>
                    <td class="text-center">{{ $insight['allocated'] }}</td>
                    <td class="text-center">{{ $insight['installed'] }}</td>
                    <td class="text-center font-bold" style="color: {{ $insight['net_deficit'] > 0 ? '#dc2626' : '#6b7280' }};">
                        {{ $insight['net_deficit'] }}
                    </td>
                    <td>
                        @if (! empty($insight['faculty_deficits']))
                            @foreach ($insight['faculty_deficits'] as $fDef)
                                <span style="display: inline-block; background-color: #fee2e2; color: #991b1b; padding: 1px 4px; border-radius: 2px; font-size: 7.5px; margin: 1px;">
                                    {{ $fDef['faculty_code'] ?? $fDef['faculty_name'] }}: +{{ $fDef['deficit'] }}
                                </span>
                            @endforeach
                        @else
                            <span style="color: #9ca3af;">-</span>
                        @endif
                    </td>
                    <td>{{ $insight['recommendation'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="color: #6b7280;">Seluruh kebutuhan lisensi software komersial terpenuhi dalam batas aman.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="scientific-note">
        <strong>Pernyataan Netralitas Dokumen:</strong> Dokumen ini disusun secara otomatis oleh Sistem Manifest Lisensi Terdistribusi USN Kolaka berdasarkan data inventaris lisensi sah dan hasil pemindaian scanner agent di laboratorium sebagai bahan pertimbangan evaluasi dan pengambilan keputusan pengadaan lisensi baru.
    </div>

    <div class="signature-box">
        <p>Kolaka, {{ now()->translatedFormat('d F Y') }}<br>Mengetahui,</p>
        <div class="sig-space"></div>
        <p><b>( ______________________________ )</b><br>Pimpinan Institusi</p>
    </div>
    <div class="clear"></div>

    <div class="footer">
        Dokumen dicetak secara otomatis oleh Sistem Manifest Lisensi USN Kolaka pada {{ $print_date }}
    </div>
</body>
</html>
