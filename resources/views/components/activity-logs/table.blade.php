@props(['logs'])

<div class="relative overflow-x-auto rounded-lg border border-border bg-card shadow-sm w-full" x-data="{ expanded: null }">
    <table class="w-full text-left text-sm">
        <thead class="bg-muted/50 text-muted-foreground text-xs uppercase border-b border-border">
            <tr>
                <th scope="col" class="px-6 py-3 font-semibold">Waktu</th>
                <th scope="col" class="px-6 py-3 font-semibold">Pelaku</th>
                <th scope="col" class="px-6 py-3 font-semibold">Aksi</th>
                <th scope="col" class="px-6 py-3 font-semibold">Entitas Terkait</th>
                <th scope="col" class="px-6 py-3 font-semibold text-right">Detail</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-border">
            @forelse($logs as $log)
                @php
                    $eventName = $log->event ?? '';
                    $badgeColor = match($eventName) {
                        'created' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                        'updated' => 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20',
                        'deleted' => 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/20',
                        default => 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20',
                    };
                    $iconClass = match($eventName) {
                        'created' => 'fa-circle-plus text-emerald-500',
                        'updated' => 'fa-pen-to-square text-blue-500',
                        'deleted' => 'fa-trash-can text-red-500',
                        default => 'fa-circle-info text-slate-500',
                    };
                    
                    // Determine entity label
                    $subjectType = $log->subject_type ? basename(str_replace('\\', '/', $log->subject_type)) : 'Sistem';
                    $subjectName = '';
                    if ($log->subject) {
                        if ($log->subject_type === \App\Models\User::class) {
                            $subjectName = $log->subject->name;
                        } elseif ($log->subject_type === \App\Models\Computer::class) {
                            $subjectName = $log->subject->hostname;
                        } elseif ($log->subject_type === \App\Models\LicenseInventory::class) {
                            $subjectName = $log->subject->catalog->normalized_name ?? 'PO: ' . $log->subject->purchase_order_number;
                        } elseif ($log->subject_type === \App\Models\SoftwareCatalog::class) {
                            $subjectName = $log->subject->normalized_name;
                        } else {
                            $subjectName = 'ID: ' . $log->subject_id;
                        }
                    } else {
                        $subjectName = $log->subject_id ? 'ID: ' . $log->subject_id . ' (Dihapus/Tidak ditemukan)' : '-';
                    }

                    // Check if there are any details (attribute changes or custom properties)
                    $attributeChanges = $log->attribute_changes ?? null;
                    $attributes = $attributeChanges['attributes'] ?? null;
                    $old = $attributeChanges['old'] ?? null;

                    // Remove sensitive properties
                    if ($attributes) {
                        unset($attributes['password'], $attributes['license_key']);
                    }
                    if ($old) {
                        unset($old['password'], $old['license_key']);
                    }

                    $customProperties = $log->properties ? $log->properties->toArray() : [];
                    if ($customProperties) {
                        unset($customProperties['password'], $customProperties['license_key']);
                    }

                    $hasDetails = ($attributes || $old) || !empty($customProperties);
                @endphp
                <tr class="hover:bg-muted/30 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-foreground cursor-help border-b border-dotted border-muted-foreground/40" title="{{ $log->created_at->format('Y-m-d H:i:s') }}">
                            {{ $log->created_at->diffForHumans() }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-foreground">
                                {{ $log->causer ? $log->causer->name : 'Sistem' }}
                            </span>
                            @if($log->causer)
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold {{ $log->causer->hasRole('admin') ? 'bg-primary/10 text-primary border-primary/20' : 'bg-muted text-muted-foreground border-border' }}">
                                    {{ ucfirst($log->causer->roles->first()?->name ?? 'User') }}
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid {{ $iconClass }} w-4 text-center"></i>
                            <span class="text-foreground">
                                {!! preg_replace('/\*\*(.*?)\*\*/', '<strong class="font-semibold text-foreground">$1</strong>', e($log->description)) !!}
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-1.5">
                            @if($log->subject_type)
                                <span class="text-xs font-medium text-muted-foreground bg-muted border border-border px-1.5 py-0.5 rounded">
                                    {{ $subjectType }}
                                </span>
                                <span class="text-sm font-medium text-foreground">
                                    {{ $subjectName }}
                                </span>
                            @else
                                <span class="text-xs font-medium text-muted-foreground bg-muted border border-border px-1.5 py-0.5 rounded">
                                    Sistem
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        @if($hasDetails)
                            <button type="button" @click="expanded === {{ $log->id }} ? expanded = null : expanded = {{ $log->id }}"
                                class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground h-8 px-3">
                                <span x-show="expanded !== {{ $log->id }}"><i class="fa-solid fa-chevron-down mr-1"></i> Detail</span>
                                <span x-show="expanded === {{ $log->id }}" x-cloak><i class="fa-solid fa-chevron-up mr-1"></i> Tutup</span>
                            </button>
                        @else
                            <span class="text-muted-foreground text-xs italic">-</span>
                        @endif
                    </td>
                </tr>

                {{-- Expanded Row for Diff / Properties --}}
                @if($hasDetails)
                    <tr x-show="expanded === {{ $log->id }}" x-cloak class="bg-muted/20">
                        <td colspan="5" class="px-6 py-4 border-t border-border">
                            <div class="space-y-4">
                                @if($attributes || $old)
                                    <div>
                                        <h4 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2">Detail Perubahan Data</h4>
                                        <div class="overflow-x-auto rounded border border-border bg-background">
                                            <table class="w-full text-left text-xs">
                                                <thead class="bg-muted text-muted-foreground font-semibold">
                                                    <tr>
                                                        <th class="px-4 py-2">Properti</th>
                                                        @if($old) <th class="px-4 py-2 bg-red-500/5 text-red-600">Nilai Lama</th> @endif
                                                        @if($attributes) <th class="px-4 py-2 bg-emerald-500/5 text-emerald-600">Nilai Baru</th> @endif
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-border font-mono">
                                                    @php
                                                        $allKeys = array_keys(array_merge($old ?? [], $attributes ?? []));
                                                    @endphp
                                                    @foreach($allKeys as $key)
                                                        @php
                                                            $oldVal = $old[$key] ?? null;
                                                            $newVal = $attributes[$key] ?? null;
                                                        @endphp
                                                        <tr class="hover:bg-muted/40">
                                                            <td class="px-4 py-2 font-medium text-foreground">{{ $key }}</td>
                                                            @if($old)
                                                                <td class="px-4 py-2 bg-red-500/5 text-red-700 dark:text-red-400 break-all">
                                                                    {{ is_array($oldVal) ? json_encode($oldVal) : ($oldVal ?? 'NULL') }}
                                                                </td>
                                                            @endif
                                                            @if($attributes)
                                                                <td class="px-4 py-2 bg-emerald-500/5 text-emerald-700 dark:text-emerald-400 break-all">
                                                                    {{ is_array($newVal) ? json_encode($newVal) : ($newVal ?? 'NULL') }}
                                                                </td>
                                                            @endif
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif

                                @if(!empty($customProperties))
                                    <div>
                                        <h4 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2">Metadata / Informasi Tambahan</h4>
                                        <div class="rounded border border-border bg-background p-3">
                                            <dl class="grid grid-cols-1 gap-x-4 gap-y-2 sm:grid-cols-2 font-mono text-xs">
                                                @foreach($customProperties as $key => $value)
                                                    <div class="border-b border-border/50 pb-1.5 sm:col-span-1">
                                                        <dt class="text-muted-foreground font-semibold">{{ $key }}</dt>
                                                        <dd class="text-foreground mt-0.5 break-all">
                                                            {{ is_array($value) ? json_encode($value) : ($value ?? 'NULL') }}
                                                        </dd>
                                                    </div>
                                                @endforeach
                                            </dl>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-muted-foreground">
                        <i class="fa-solid fa-list-check text-4xl mb-3 block opacity-30"></i>
                        Belum ada aktivitas yang tercatat.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
