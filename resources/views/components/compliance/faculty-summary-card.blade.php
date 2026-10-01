@props(['stats', 'faculty'])

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <x-stat-card 
        title="Alokasi Kursi" 
        value="{{ $stats['total_allocated'] }}" 
        subtitle="Alokasi resmi untuk {{ $faculty->name }}"
        icon="fa-solid fa-ticket" 
        class="border-l-4 border-l-primary" 
    />
    <x-stat-card 
        title="Total Terpasang" 
        value="{{ $stats['total_installed'] }}" 
        subtitle="Terdeteksi di lab {{ $faculty->code }}"
        icon="fa-solid fa-desktop" 
    />
    <x-stat-card 
        title="Defisit Fakultas" 
        value="{{ $stats['total_deficit'] }}" 
        subtitle="Instalasi melebihi alokasi"
        icon="fa-solid fa-triangle-exclamation" 
        variant="{{ $stats['total_deficit'] > 0 ? 'critical' : 'default' }}" 
        class="border-l-4 border-l-destructive" 
    />
    <x-stat-card 
        title="Surplus Alokasi" 
        value="{{ $stats['total_surplus'] }}" 
        subtitle="Alokasi kursi belum terpakai"
        icon="fa-solid fa-layer-group" 
        class="border-l-4 border-l-info" 
    />
</div>
