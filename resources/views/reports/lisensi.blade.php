<x-layout.app title="Laporan Kepatuhan" :breadcrumbs="[['name' => 'Dashboard', 'url' => route('dashboard')], ['name' => 'Pusat Laporan', 'url' => route('reports')], ['name' => 'Status Lisensi', 'url' => null]]">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Status Lisensi</h1>
                <p class="text-gray-600">Pemantauan penggunaan kuota dan ekspirasi lisensi.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('reports.lisensi.export', ['format' => 'pdf', 'start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString()]) }}"
                    target="_blank"
                    class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md shadow-sm">
                    Export PDF
                </a>
                <a href="{{ route('reports.lisensi.export', ['format' => 'excel', 'start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString()]) }}"
                    class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md shadow-sm">
                    Export Excel
                </a>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
            <form action="{{ route('reports.lisensi') }}" method="GET" class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                    <input type="date" name="start_date" value="{{ $startDate->toDateString() }}"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal Selesai</label>
                    <input type="date" name="end_date" value="{{ $endDate->toDateString() }}"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md">
                        Terapkan Filter
                    </button>
                    <a href="{{ route('reports.lisensi') }}"
                        class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-md">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="mb-4 text-sm text-gray-600 italic">
            Menampilkan {{ $licenses->total() }} data untuk periode {{ $startDate->format('d/m/Y') }} s/d
            {{ $endDate->format('d/m/Y') }}
        </div>

        <div class="bg-white shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Software</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Quota</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Terpakai</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Sisa</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">%
                            Penggunaan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Expired</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($licenses as $index => $license)
                        @php
                            $status = 'Tersedia';
                            $statusClass = 'bg-green-100 text-green-800';
                            if ($license->remaining <= 0) {
                                $status = 'Penuh';
                                $statusClass = 'bg-red-100 text-red-800';
                            } elseif ($license->usage_pct >= 80) {
                                $status = 'Hampir Habis';
                                $statusClass = 'bg-yellow-100 text-yellow-800';
                            }
                            if ($license->expiry_date && $license->expiry_date->lt(now())) {
                                $status = 'Kedaluwarsa';
                                $statusClass = 'bg-red-100 text-red-800';
                            }
                        @endphp
                        <tr class="{{ ($status === 'Penuh' || $status === 'Kedaluwarsa') ? 'bg-red-50' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $licenses->firstItem() + $index }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $license->catalog->normalized_name ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">
                                {{ $license->quota_limit }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500 font-bold">
                                {{ $license->used_count }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">
                                {{ $license->remaining }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                <div class="flex items-center justify-center">
                                    <span class="text-xs font-semibold mr-2">{{ $license->usage_pct }}%</span>
                                    <div class="w-16 bg-gray-200 rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full {{ $license->usage_pct >= 90 ? 'bg-red-600' : ($license->usage_pct >= 75 ? 'bg-yellow-400' : 'bg-green-500') }}"
                                            style="width: {{ min(100, $license->usage_pct) }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClass }}">
                                    {{ $status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $license->expiry_date ? $license->expiry_date->format('d/m/Y') : '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                {{ $licenses->links() }}
            </div>
        </div>
    </div>
</x-layout.app>