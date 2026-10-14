<x-layout.app title="Lisensi Fakultas">
    @if (! $faculty)
        <div>Akun Anda belum ditugaskan ke laboratorium atau fakultas.</div>
    @else
        <div>{{ $faculty->name }}</div>
        @foreach ($allocations as $allocation)
            <div>{{ $allocation->licenseInventory->catalog->normalized_name }}</div>
            <div>{{ $allocation->allocated_quota }}</div>
            <div>{{ $allocation->notes }}</div>
            <div>{{ $allocation->licenseInventory->masked_license_key }}</div>
        @endforeach
    @endif
</x-layout.app>
