@props(['activities' => []])

<div class="bg-card border border-border rounded-lg shadow-sm">

    {{-- Header --}}
    <div class="p-5 border-b border-border">
        <h3 class="text-sm font-semibold text-foreground">Recent Scan Activity</h3>
        <p class="text-xs text-muted-foreground mt-1">Hasil scan terbaru dari semua komputer</p>
    </div>

    {{-- Table Container --}}
    <div class="relative w-full overflow-auto">
        <table class="w-full caption-bottom text-sm">

            {{-- Table Head --}}
            <thead class="[&_tr]:border-b [&_tr]:border-border">
                <tr class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                    <th
                        class="h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0">
                        Computer
                    </th>
                    <th
                        class="h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0">
                        Time
                    </th>
                    <th
                        class="h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0">
                        Software Found
                    </th>
                    <th
                        class="h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0">
                        Status
                    </th>
                </tr>
            </thead>

            {{-- Table Body --}}
            <tbody class="[&_tr:last-child]:border-0">
                @forelse ($activities as $activity)
                    {{-- Logic Styling Status --}}
                    @php
                        switch ($activity['status']) {
                            case 'success':
                                $badgeClass = 'bg-success/10 text-success border-success/20';
                                $icon = 'fa-circle-check';
                                break;
                            case 'warning':
                                $badgeClass = 'bg-warning/10 text-warning border-warning/20';
                                $icon = 'fa-triangle-exclamation';
                                break;
                            default:
                                // error
                                $badgeClass = 'bg-destructive/10 text-destructive border-destructive/20';
                                $icon = 'fa-circle-xmark';
                                break;
                        }
                    @endphp

                    <tr class="border-b border-border transition-colors hover:bg-muted/50">
                        <td class="p-4 align-middle font-medium text-foreground">{{ $activity['computer'] }}</td>
                        <td class="p-4 align-middle text-muted-foreground">{{ $activity['time'] }}</td>
                        <td class="p-4 align-middle text-muted-foreground">{{ $activity['software'] }} apps</td>
                        <td class="p-4 align-middle">
                            <span
                                class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $badgeClass }}">
                                <i class="fa-solid {{ $icon }} mr-1.5 text-[10px]"></i>
                                {{ $activity['statusText'] }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-4 text-center text-muted-foreground">Belum ada data scan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
