<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Task distribution</x-slot>

        <div class="space-y-4">
            <div class="org-widget-panel">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="org-widget-kicker">Delivery health</div>
                        <div class="mt-2 text-3xl font-semibold text-[color:var(--gv-fg0)]">{{ $plannedPoints }}</div>
                        <div class="mt-1 text-sm text-[color:var(--gv-fg3)]">points currently planned across the visible scope</div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-mary-badge value="{{ $storyPointsTarget }} pt target" class="badge-soft" />
                        <x-mary-badge value="{{ max(0, $plannedPoints - $storyPointsTarget) }} over target" class="badge-soft" />
                    </div>
                </div>

                <div class="mt-4">
                    <div class="mb-1 flex items-center justify-between text-xs text-[color:var(--gv-fg4)]">
                        <span>planned vs default sprint target</span>
                        <span class="font-mono">{{ $plannedPoints }} / {{ $storyPointsTarget }}</span>
                    </div>
                    <progress class="progress h-2" max="{{ max($plannedPoints, $storyPointsTarget, 1) }}" value="{{ $plannedPoints }}"></progress>
                </div>
            </div>

            <div class="grid gap-4 xl:grid-cols-2">
                <div class="org-widget-panel">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <div class="org-widget-kicker">By status</div>
                            <div class="mt-1 text-sm text-[color:var(--gv-fg3)]">Where work is sitting right now</div>
                        </div>
                        <x-mary-badge value="{{ collect($statusRows)->sum('count') }} tasks" class="badge-soft" />
                    </div>

                    <div class="space-y-3">
                        @foreach($statusRows as $row)
                            <div class="rounded-xl border border-[color:var(--gv-border)] bg-[color:var(--gv-bg0-s)]/60 p-3">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="text-sm font-semibold capitalize text-[color:var(--gv-fg1)]">{{ $row['label'] }}</div>
                                    <x-mary-badge value="{{ $row['count'] }} · {{ $row['percent'] }}%" />
                                </div>
                                <progress class="progress mt-3 h-2" max="100" value="{{ $row['percent'] }}"></progress>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="org-widget-panel">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <div class="org-widget-kicker">By priority</div>
                            <div class="mt-1 text-sm text-[color:var(--gv-fg3)]">How urgent the scoped work is</div>
                        </div>
                        <x-mary-badge value="{{ collect($priorityRows)->sum('count') }} tasks" class="badge-soft" />
                    </div>

                    <div class="space-y-3">
                        @foreach($priorityRows as $row)
                            <div class="rounded-xl border border-[color:var(--gv-border)] bg-[color:var(--gv-bg0-s)]/60 p-3">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="text-sm font-semibold uppercase text-[color:var(--gv-fg1)]">{{ $row['label'] }}</div>
                                    <x-mary-badge value="{{ $row['count'] }} · {{ $row['percent'] }}%" />
                                </div>
                                <progress class="progress mt-3 h-2" max="100" value="{{ $row['percent'] }}"></progress>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
