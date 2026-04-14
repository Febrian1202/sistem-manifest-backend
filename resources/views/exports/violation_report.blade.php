<!DOCTYPE html>
<html>

<head>
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        h2 {
            text-align: center;
            margin-bottom: 5px;
        }

        .subtitle {
            text-align: center;
            color: #555;
            margin-bottom: 20px;
            font-size: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px;
        }

        th {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .sw-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #b91c1c;
        }
    </style>
</head>

<body>
    <h2>{{ $title }}</h2>
    <div class="subtitle">Hanya menampilkan software yang jumlah instalasinya melebihi lisensi | Dicetak:
        {{ $print_date }}
    </div>

    @forelse($softwares as $sw)
        <div class="sw-title">⚠️ {{ $sw->normalized_name }} (Defisit: {{ $sw->deficit }} Lisensi)</div>
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="35%">Hostname Komputer</th>
                    <th width="30%">IP Address</th>
                    <th width="30%">Tanggal Terdeteksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sw->discoveries as $index => $discovery)
                    @if($discovery->computer)
                        <tr>
                            <td align="center">{{ $index + 1 }}</td>
                            <td>{{ $discovery->computer->hostname }}</td>
                            <td>{{ $discovery->computer->ip_address }}</td>
                            <td>{{ \Carbon\Carbon::parse($discovery->created_at)->format('d-m-Y H:i') }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    @empty
        <p align="center">Tidak ditemukan pelanggaran lisensi. Semua perangkat berstatus Compliant.</p>
    @endforelse
</body>

</html>