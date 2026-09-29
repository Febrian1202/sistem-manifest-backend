<x-layout.app title="Rekap Monitoring Berkala" :breadcrumbs="[['name' => 'Dashboard', 'url' => route('dashboard')], ['name' => 'Pusat Laporan', 'url' => route('reports')], ['name' => 'Rekap Monitoring', 'url' => null]]">
    <div class="p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Rekapitulasi Monitoring Berkala</h1>
                <p class="text-gray-600">Laporan ringkasan aktivitas dan keberhasilan sesi scan komputer pada periode tertentu.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
                <a href="{{ route('reports.monitoring.export', array_filter(['format' => 'pdf', 'start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString(), 'laboratory_id' => request('laboratory_id')])) }}"
                    target="_blank"
                    class="inline-flex justify-center items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md shadow-sm w-full sm:w-auto">
                    <i class="fa-solid fa-file-pdf mr-2"></i> Export PDF
                </a>
                <a href="{{ route('reports.monitoring.export', array_filter(['format' => 'excel', 'start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString(), 'laboratory_id' => request('laboratory_id')])) }}"
                    class="inline-flex justify-center items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md shadow-sm w-full sm:w-auto">
                    <i class="fa-solid fa-file-excel mr-2"></i> Export Excel
                </a>
            </div>
        </div>

        {{-- Filter Form --}}
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
            <form action="{{ route('reports.monitoring') }}" method="GET" class="flex flex-col md:flex-row items-stretch md:items-end gap-4">
                <div class="w-full md:flex-1">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                    <input type="date" name="start_date" value="{{ $startDate->toDateString() }}"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>
                <div class="w-full md:flex-1">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal Selesai</label>
                    <input type="date" name="end_date" value="{{ $endDate->toDateString() }}"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>
                @if(isset($laboratories) && $laboratories->isNotEmpty())
                <div class="w-full md:flex-1">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Laboratorium</label>
                    @if(auth()->user()->hasRole('kepala_lab'))
                        <input type="text" readonly disabled value="{{ auth()->user()->laboratory?->name ?? 'Belum Ditugaskan' }}"
                            class="block w-full rounded-md border-gray-200 bg-gray-50 text-gray-600 shadow-sm sm:text-sm">
                    @else
                        <select name="laboratory_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="All">Semua Laboratorium</option>
                            @foreach($laboratories as $lab)
                                <option value="{{ $lab->id }}" {{ request('laboratory_id') == $lab->id ? 'selected' : '' }}>
                                    {{ $lab->name }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>
                @endif
                <div class="flex justify-end items-center gap-2 w-full md:w-auto">
                    <button type="submit"
                        class="inline-flex justify-center items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md">
                        <i class="fa-solid fa-filter mr-1.5"></i> Terapkan Filter
                    </button>
                    <a href="{{ route('reports.monitoring') }}"
                        class="inline-flex justify-center items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-md">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- Ringkasan Metrics Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Komputer</div>
                <div class="mt-2 text-2xl font-bold text-gray-900">{{ $summary['total_computers'] ?? 0 }}</div>
            </div>
            <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Sesi Scan</div>
                <div class="mt-2 text-2xl font-bold text-blue-600">{{ $summary['total_scans'] ?? 0 }}</div>
            </div>
            <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Scan Berhasil</div>
                <div class="mt-2 text-2xl font-bold text-green-600">{{ $summary['successful_scans'] ?? 0 }}</div>
            </div>
            <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Scan Gagal</div>
                <div class="mt-2 text-2xl font-bold text-red-600">{{ $summary['failed_scans'] ?? 0 }}</div>
            </div>
            <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm col-span-2 md:col-span-1">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Success Rate</div>
                <div class="mt-2 text-2xl font-bold text-indigo-600">{{ $summary['success_rate'] ?? 0 }}%</div>
            </div>
        </div>

        {{-- Breakdown Per Laboratorium --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="font-bold text-gray-800 text-sm">Ringkasan Aktivitas Per Laboratorium</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Laboratorium</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600">Jumlah Komputer</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600">Total Scan</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600">Berhasil</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600">Gagal</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600">Success Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($labStats as $lab)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ $lab->name }} <span class="text-xs text-gray-500">({{ $lab->code }})</span>
                                </td>
                                <td class="px-4 py-3 text-center text-gray-600">{{ $lab->computers_count }}</td>
                                <td class="px-4 py-3 text-center font-semibold text-gray-900">{{ $lab->total_scans }}</td>
                                <td class="px-4 py-3 text-center text-green-600 font-semibold">{{ $lab->successful_scans }}</td>
                                <td class="px-4 py-3 text-center text-red-600 font-semibold">{{ $lab->failed_scans }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $lab->success_rate >= 80 ? 'bg-green-100 text-green-800' : ($lab->success_rate >= 50 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                        {{ $lab->success_rate }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-gray-500">Tidak ada laboratorium yang sesuai.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Detail Per Komputer --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="font-bold text-gray-800 text-sm">Status Monitoring Per Komputer</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Hostname</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Laboratorium</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">IP Address</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600">Total Scan</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600">Sukses</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600">Gagal</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Scan Terakhir</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($computers as $comp)
                            @php
                                $latestScan = $comp->scanSessions->first();
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-semibold text-gray-900">{{ $comp->hostname }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $comp->laboratory?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ $comp->ip_address ?? '-' }}</td>
                                <td class="px-4 py-3 text-center font-medium">{{ $comp->total_scans ?? 0 }}</td>
                                <td class="px-4 py-3 text-center text-green-600 font-medium">{{ $comp->successful_scans ?? 0 }}</td>
                                <td class="px-4 py-3 text-center text-red-600 font-medium">{{ $comp->failed_scans ?? 0 }}</td>
                                <td class="px-4 py-3 text-xs text-gray-600">
                                    {{ $latestScan && $latestScan->started_at ? $latestScan->started_at->format('d/m/Y H:i') : ($comp->last_seen_at ? $comp->last_seen_at->format('d/m/Y H:i') : '-') }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($latestScan)
                                        @if($latestScan->status === 'completed')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Berhasil</span>
                                        @elseif($latestScan->status === 'failed')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Gagal</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">{{ ucfirst($latestScan->status) }}</span>
                                        @endif
                                    @else
                                        <span class="text-xs text-gray-400">Belum Ada</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500">Tidak ada data komputer ditemukan pada rentang tanggal dan laboratorium ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if(method_exists($computers, 'links'))
                <div class="px-5 py-3 border-t border-gray-200">
                    {{ $computers->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layout.app>
