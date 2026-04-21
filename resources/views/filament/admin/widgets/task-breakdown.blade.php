<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Tasks breakdown</x-slot>

        <div class="space-y-5">
            <div>
                <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-[color:var(--gv-fg4)]">By status</div>
                <div class="space-y-1.5">
                    @foreach($statuses as $row)
                        <div class="flex items-center gap-3 text-sm">
                            <span class="w-24 text-[color:var(--gv-fg2)]">{{ $row['label'] }}</span>
                            <div class="flex-1 h-2 rounded-sm bg-[color:var(--gv-bg1)] overflow-hidden">
                                <div class="h-full bg-[color:var(--fi-accent,var(--gv-amber))]" style="width: {{ $row['percent'] }}%"></div>
                            </div>
                            <span class="w-16 text-right font-mono text-xs text-[color:var(--gv-fg1)]">{{ $row['count'] }} · {{ $row['percent'] }}%</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-[color:var(--gv-fg4)]">By priority</div>
                <div class="space-y-1.5">
                    @foreach($priorities as $row)
                        <div class="flex items-center gap-3 text-sm">
                            <span class="w-24 text-[color:var(--gv-fg2)]">{{ $row['label'] }}</span>
                            <div class="flex-1 h-2 rounded-sm bg-[color:var(--gv-bg1)] overflow-hidden">
                                <div class="h-full bg-[color:var(--fi-accent,var(--gv-amber))]" style="width: {{ $row['percent'] }}%"></div>
                            </div>
                            <span class="w-16 text-right font-mono text-xs text-[color:var(--gv-fg1)]">{{ $row['count'] }} · {{ $row['percent'] }}%</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
