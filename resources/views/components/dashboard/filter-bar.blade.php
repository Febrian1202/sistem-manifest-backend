@props([
    'period' => '7d',
    'selectedLabId' => null,
    'laboratories' => collect(),
    'showLabFilter' => true,
    'action' => route('dashboard'),
])

<div class="bg-card border border-border p-4 rounded-xl shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
    <div class="flex items-center gap-2 text-sm font-semibold text-foreground">
        <div class="p-2 bg-primary/10 text-primary rounded-lg flex items-center justify-center">
            <i class="fa-solid fa-sliders"></i>
        </div>
        <div>
            <div class="font-bold text-foreground">Filter Monitoring</div>
            <div class="text-xs text-muted-foreground font-normal">Pilih periode waktu dan laboratorium</div>
        </div>
    </div>

    <form method="GET" action="{{ $action }}" class="flex flex-wrap items-center gap-3 w-full md:w-auto">
        {{-- Periode Preset --}}
        <div class="flex items-center gap-1.5 bg-muted/50 p-1 rounded-lg border border-border/60">
            <a href="{{ request()->fullUrlWithQuery(['period' => '7d']) }}"
               class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors {{ $period === '7d' ? 'bg-background text-foreground shadow-xs border border-border/80 font-bold' : 'text-muted-foreground hover:text-foreground' }}">
                7 Hari
            </a>
            <a href="{{ request()->fullUrlWithQuery(['period' => '30d']) }}"
               class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors {{ $period === '30d' ? 'bg-background text-foreground shadow-xs border border-border/80 font-bold' : 'text-muted-foreground hover:text-foreground' }}">
                30 Hari
            </a>
            <a href="{{ request()->fullUrlWithQuery(['period' => 'this_month']) }}"
               class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors {{ $period === 'this_month' ? 'bg-background text-foreground shadow-xs border border-border/80 font-bold' : 'text-muted-foreground hover:text-foreground' }}">
                Bulan Ini
            </a>
            <a href="{{ request()->fullUrlWithQuery(['period' => '3m']) }}"
               class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors {{ $period === '3m' ? 'bg-background text-foreground shadow-xs border border-border/80 font-bold' : 'text-muted-foreground hover:text-foreground' }}">
                3 Bulan
            </a>
        </div>

        <input type="hidden" name="period" value="{{ $period }}">

        {{-- Laboratorium Dropdown (jika diizinkan) --}}
        @if ($showLabFilter && $laboratories->isNotEmpty())
            <div class="flex items-center gap-2">
                <select name="laboratory_id"
                        onchange="this.form.submit()"
                        class="px-3 py-1.5 text-xs rounded-lg border border-border bg-background text-foreground focus:ring-2 focus:ring-primary focus:outline-none">
                    <option value="">Semua Laboratorium</option>
                    @foreach ($laboratories as $lab)
                        <option value="{{ $lab->id }}" {{ (string)$selectedLabId === (string)$lab->id ? 'selected' : '' }}>
                            {{ $lab->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        @if ($selectedLabId)
            <a href="{{ request()->fullUrlWithQuery(['laboratory_id' => null]) }}"
               class="text-xs text-muted-foreground hover:text-destructive flex items-center gap-1"
               title="Reset filter lab">
                <i class="fa-solid fa-xmark"></i> Reset Lab
            </a>
        @endif
    </form>
</div>
