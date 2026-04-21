<x-layout.app title="Ringkasan Eksekutif" :breadcrumbs="[['name' => 'Dashboard', 'url' => route('dashboard')], ['name' => 'Pusat Laporan', 'url' => route('reports')], ['name' => 'Ringkasan Eksekutif', 'url' => null]]">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Ringkasan Eksekutif</h1>
                <p class="text-gray-600">Laporan ringkasan status kepatuhan dan aset IT.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('reports.eksekutif.export', ['format' => 'pdf', 'start_date' => $startDate, 'end_date' => $endDate]) }}"
                    target="_blank"
                    class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    Export PDF
                </a>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
            <form action="{{ route('reports.eksekutif') }}" method="GET" class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                    <input type="date" name="start_date" value="{{ $startDate }}"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal Selesai</label>
                    <input type="date" name="end_date" value="{{ $endDate }}"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md">
                        Terapkan Filter
                    </button>
                    <a href="{{ route('reports.eksekutif') }}"
                        class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-md">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Komputer Aktif</p>
                <h3 class="text-3xl font-bold text-gray-900 mt-1">{{ $totalComputers }}</h3>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Software Terdeteksi</p>
                <h3 class="text-3xl font-bold text-gray-900 mt-1">{{ $totalInstallations }}</h3>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Tingkat Kepatuhan</p>
                <h3 class="text-3xl font-bold text-green-600 mt-1">{{ $complianceRate }}%</h3>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Peringatan Kritis</p>
                <h3 class="text-3xl font-bold text-red-600 mt-1">{{ $criticalAlerts }}</h3>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Breakdown Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="font-bold text-gray-800">Status Kepatuhan OS</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Jumlah Komputer</th>
                            <th
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Persentase</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($breakdown as $item)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $item['status'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">
                                    {{ $item['count'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">
                                    {{ $item['pct'] }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Top Unlicensed -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="font-bold text-gray-800">Top 5 Software Tanpa Lisensi</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nama Software</th>
                            <th
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Jumlah Terinstall</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($topUnlicensed as $sw)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $sw->raw_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">{{ $sw->total }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-6 py-8 text-center text-gray-500 italic">Tidak ada data
                                    pelanggaran ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-8 p-4 bg-blue-50 border border-blue-100 rounded-lg">
            <p class="text-sm text-blue-800">
                <strong>Catatan:</strong> Laporan ini merupakan ringkasan kondisi kepatuhan lisensi perangkat lunak di
                lingkungan institusi pada periode {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d
                {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}.
            </p>
        </div>
    </div>
</x-layout.app>