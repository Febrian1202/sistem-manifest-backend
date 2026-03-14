<button type="button" @click="open = !open"
    {{ $attributes->merge(['class' => 'flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50']) }}>
    <span x-text="selectedLabel" class="truncate"></span>
    <i class="fa-solid fa-chevron-down h-4 w-4 opacity-50 transition-transform duration-200"
        :class="open ? 'rotate-180' : ''"></i>
</button>
