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
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f4f4f5;
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-danger {
            color: red;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <h2>{{ $title }}</h2>
    <div class="subtitle">Sistem Informasi Manifest USN Kolaka | Dicetak pada: {{ $print_date }}</div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Software (Commercial)</th>
                <th class="text-center">Total Terpasang</th>
                <th class="text-center">Lisensi Dimiliki</th>
                <th class="text-center">Defisit (Kekurangan)</th>
                <th>Status Kepatuhan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($softwares as $index => $sw)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $sw->normalized_name }}</td>
                    <td class="text-center">{{ $sw->installed_count }}</td>
                    <td class="text-center">{{ $sw->owned_count }}</td>
                    <td class="text-center {{ $sw->deficit > 0 ? 'text-danger' : '' }}">
                        {{ $sw->deficit > 0 ? '-' . $sw->deficit : '0' }}
                    </td>
                    <td>{{ $sw->deficit > 0 ? 'Non-Compliant (Melanggar)' : 'Compliant (Aman)' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>