<x-layout.app>
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Laporan Kepatuhan</h1>
                <p class="text-gray-600">Detail status kepatuhan perangkat lunak per software.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('reports.kepatuhan.export', ['format' => 'pdf', 'start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString()]) }}" 
                   target="_blank"
                   class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md shadow-sm transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    Export PDF
                </a>
                <a href="{{ route('reports.kepatuhan.export', ['format' => 'excel', 'start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString()]) }}" 
                   class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md shadow-sm transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
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
            <div class="mt-4 pt-4 border-t border-gray-100 flex justify-end">
                <form action="{{ route('reports.kepatuhan.scan') }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md shadow-sm transition-all duration-200 hover:scale-[1.02]">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        Pindai Ulang Kepatuhan (Antrean Job)
                    </button>
                </form>
            </div>
        </div>

        <div class="mb-4 flex items-center justify-between">
            <div class="text-sm text-gray-600 italic">
                Menampilkan {{ $reports->total() }} temuan untuk periode {{ $startDate->format('d/m/Y') }} s/d {{ $endDate->format('d/m/Y') }}
            </div>
            <div class="flex gap-4">
                <div class="flex items-center text-xs">
                    <span class="w-3 h-3 rounded-full bg-green-500 mr-1"></span> Berlisensi
                </div>
                <div class="flex items-center text-xs">
                    <span class="w-3 h-3 rounded-full bg-yellow-500 mr-1"></span> Grace Period
                </div>
                <div class="flex items-center text-xs">
                    <span class="w-3 h-3 rounded-full bg-red-500 mr-1"></span> Tidak Berlisensi
                </div>
            </div>
        </div>

        <div class="bg-white shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Komputer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Software</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Versi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Terdeteksi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($reports as $index => $report)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $reports->firstItem() + $index }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $report->computer->hostname ?? '-' }}</div>
                            <div class="text-xs text-gray-500 font-mono">{{ $report->computer->ip_address ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-semibold">{{ $report->software_name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $report->software_version ?? '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($report->status === 'Berlisensi')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Berlisensi</span>
                            @elseif($report->status === 'Grace Period')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Grace Period</span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Tidak Berlisensi</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $report->scanned_at ? $report->scanned_at->format('d/m/Y H:i') : '-' }}
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-500">
                            {{ $report->keterangan }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-gray-500 italic">
                            Data kepatuhan belum ditemukan untuk periode ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                {{ $reports->links() }}
            </div>
        </div>
    </div>
</x-layout.app>
