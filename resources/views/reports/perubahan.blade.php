<x-layout.app title="Rekap Perubahan Software" :breadcrumbs="[['name' => 'Dashboard', 'url' => route('dashboard')], ['name' => 'Pusat Laporan', 'url' => route('reports')], ['name' => 'Rekap Perubahan', 'url' => null]]">
    <div class="p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Rekapitulasi Perubahan Software</h1>
                <p class="text-gray-600">Laporan tracking histori software baru terpasang, dihapus, atau di-upgrade/downgrade pada periode tertentu.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
                <a href="{{ route('reports.perubahan.export', array_filter(['format' => 'pdf', 'start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString(), 'laboratory_id' => request('laboratory_id'), 'change_type' => request('change_type')])) }}"
                    target="_blank"
                    class="inline-flex justify-center items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md shadow-sm w-full sm:w-auto">
                    <i class="fa-solid fa-file-pdf mr-2"></i> Export PDF
                </a>
                <a href="{{ route('reports.perubahan.export', array_filter(['format' => 'excel', 'start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString(), 'laboratory_id' => request('laboratory_id'), 'change_type' => request('change_type')])) }}"
                    class="inline-flex justify-center items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md shadow-sm w-full sm:w-auto">
                    <i class="fa-solid fa-file-excel mr-2"></i> Export Excel
                </a>
            </div>
        </div>

        {{-- Filter Form --}}
        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
            <form action="{{ route('reports.perubahan') }}" method="GET" class="flex flex-col md:flex-row items-stretch md:items-end gap-4">
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
                <div class="w-full md:flex-1">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Jenis Perubahan</label>
                    <select name="change_type" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="All" {{ request('change_type') == 'All' || !request('change_type') ? 'selected' : '' }}>Semua Jenis Perubahan</option>
                        <option value="added" {{ request('change_type') == 'added' ? 'selected' : '' }}>Software Baru (+)</option>
                        <option value="removed" {{ request('change_type') == 'removed' ? 'selected' : '' }}>Software Dihapus (-)</option>
                        <option value="version_changed" {{ request('change_type') == 'version_changed' ? 'selected' : '' }}>Perubahan Versi</option>
                        <option value="returned" {{ request('change_type') == 'returned' ? 'selected' : '' }}>Muncul Kembali</option>
                    </select>
                </div>
                <div class="flex justify-end items-center gap-2 w-full md:w-auto">
                    <button type="submit"
                        class="inline-flex justify-center items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md">
                        <i class="fa-solid fa-filter mr-1.5"></i> Terapkan
                    </button>
                    <a href="{{ route('reports.perubahan') }}"
                        class="inline-flex justify-center items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-md">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- Metrics Summary --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Perubahan</div>
                <div class="mt-2 text-2xl font-bold text-gray-900">{{ $summary['total'] ?? 0 }}</div>
            </div>
            <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Software Baru</div>
                <div class="mt-2 text-2xl font-bold text-green-600">+{{ $summary['added'] ?? 0 }}</div>
            </div>
            <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Software Dihapus</div>
                <div class="mt-2 text-2xl font-bold text-red-600">-{{ $summary['removed'] ?? 0 }}</div>
            </div>
            <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Versi Berubah</div>
                <div class="mt-2 text-2xl font-bold text-amber-600">{{ $summary['version_changed'] ?? 0 }}</div>
            </div>
            <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm col-span-2 md:col-span-1">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Muncul Kembali</div>
                <div class="mt-2 text-2xl font-bold text-blue-600">{{ $summary['returned'] ?? 0 }}</div>
            </div>
        </div>

        {{-- Tabel Perubahan --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="font-bold text-gray-800 text-sm">Daftar Detail Riwayat Perubahan</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Waktu Terdeteksi</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Hostname</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Laboratorium</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Nama Software</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600">Jenis Perubahan</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Detail Versi</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Vendor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($paginatedChanges as $change)
                            @php
                                $scannedAt = ! empty($change['scanned_at'])
                                    ? ($change['scanned_at'] instanceof \Carbon\Carbon ? $change['scanned_at']->format('d/m/Y H:i') : date('d/m/Y H:i', strtotime($change['scanned_at'])))
                                    : '-';
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">{{ $scannedAt }}</td>
                                <td class="px-4 py-3 font-semibold text-gray-900">{{ $change['computer_hostname'] ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $change['laboratory_name'] ?? '-' }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $change['raw_name'] ?? '-' }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if(($change['type'] ?? '') === 'added')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800">
                                            <i class="fa-solid fa-plus mr-1 text-[10px]"></i> Software Baru
                                        </span>
                                    @elseif(($change['type'] ?? '') === 'removed')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-800">
                                            <i class="fa-solid fa-minus mr-1 text-[10px]"></i> Dihapus
                                        </span>
                                    @elseif(($change['type'] ?? '') === 'version_changed')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800">
                                            <i class="fa-solid fa-arrow-right-arrow-left mr-1 text-[10px]"></i> Versi Berubah
                                        </span>
                                    @elseif(($change['type'] ?? '') === 'returned')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800">
                                            <i class="fa-solid fa-rotate-left mr-1 text-[10px]"></i> Muncul Kembali
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-600">{{ ucfirst($change['type'] ?? '-') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs font-mono text-gray-600">
                                    @if(($change['type'] ?? '') === 'version_changed')
                                        <span class="line-through text-red-500">{{ $change['old_version'] ?? '-' }}</span>
                                        <i class="fa-solid fa-arrow-right mx-1 text-[10px] text-gray-400"></i>
                                        <span class="font-bold text-green-600">{{ $change['new_version'] ?? '-' }}</span>
                                    @else
                                        {{ $change['version'] ?? '-' }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500">{{ $change['vendor'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                    Tidak ada riwayat perubahan software ditemukan untuk periode dan filter ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if(method_exists($paginatedChanges, 'links'))
                <div class="px-5 py-3 border-t border-gray-200">
                    {{ $paginatedChanges->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layout.app>
