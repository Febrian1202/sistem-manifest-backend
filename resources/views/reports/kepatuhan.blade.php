<x-layout.app>
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Laporan Kepatuhan</h1>
                <p class="text-gray-600">Detail status kepatuhan perangkat lunak per komputer.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('reports.kepatuhan.export', ['format' => 'pdf', 'start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString()]) }}" 
                   target="_blank"
                   class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md shadow-sm">
                    Export PDF
                </a>
                <a href="{{ route('reports.kepatuhan.export', ['format' => 'excel', 'start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString()]) }}" 
                   class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md shadow-sm">
                    Export Excel
                </a>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
            <form action="{{ route('reports.kepatuhan') }}" method="GET" class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                    <input type="date" name="start_date" value="{{ $startDate->toDateString() }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal Selesai</label>
                    <input type="date" name="end_date" value="{{ $endDate->toDateString() }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md">
                        Terapkan Filter
                    </button>
                    <a href="{{ route('reports.kepatuhan') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-md">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="mb-4 text-sm text-gray-600 italic">
            Menampilkan {{ $reports->total() }} data untuk periode {{ $startDate->format('d/m/Y') }} s/d {{ $endDate->format('d/m/Y') }}
        </div>

        <div class="bg-white shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Komputer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deteksi Terakhir</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($reports as $index => $report)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $reports->firstItem() + $index }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $report->computer->hostname ?? '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">{{ $report->computer->ip_address ?? '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($report->status === 'Safe')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Berlisensi</span>
                            @elseif($report->status === 'Warning')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Grace Period</span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Tidak Berlisensi</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $report->scanned_at ? $report->scanned_at->format('d/m/Y H:i') : '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">
                            <ul class="list-disc list-inside">
                                <li>Unlicensed: {{ $report->unlicensed_count }}</li>
                                <li>Blacklist: {{ $report->blacklisted_count }}</li>
                            </ul>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                {{ $reports->links() }}
            </div>
        </div>
    </div>
</x-layout.app>
