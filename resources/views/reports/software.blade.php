<x-layout.app>
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Inventaris Software</h1>
                <p class="text-gray-600">Daftar perangkat lunak yang terdeteksi di seluruh jaringan.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('reports.software.export', ['format' => 'pdf', 'start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString()]) }}" 
                   target="_blank"
                   class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md shadow-sm">
                    Export PDF
                </a>
                <a href="{{ route('reports.software.export', ['format' => 'excel', 'start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString()]) }}" 
                   class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md shadow-sm">
                    Export Excel
                </a>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
            <form action="{{ route('reports.software') }}" method="GET" class="flex flex-wrap items-end gap-4">
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
                    <a href="{{ route('reports.software') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-md">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="mb-4 text-sm text-gray-600 italic">
            Menampilkan {{ $softwares->total() }} data untuk periode {{ $startDate->format('d/m/Y') }} s/d {{ $endDate->format('d/m/Y') }}
        </div>

        <div class="bg-white shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Software</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Versi</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Komputer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Lisensi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($softwares as $index => $sw)
                    @php
                        $hasLicense = $sw->catalog && $sw->catalog->licenses->count() > 0;
                    @endphp
                    <tr class="{{ !$hasLicense ? 'bg-red-50' : '' }}">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $softwares->firstItem() + $index }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $sw->normalized_name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">{{ $sw->version }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500 font-bold">{{ $sw->computer_count }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($hasLicense)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Berlisensi</span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Tidak Berlisensi</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $sw->category }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                {{ $softwares->links() }}
            </div>
        </div>
    </div>
</x-layout.app>
