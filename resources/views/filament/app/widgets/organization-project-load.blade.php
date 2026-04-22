<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Projects</x-slot>

        @if(count($projects) === 0)
            <div class="text-sm text-[color:var(--gv-fg4)]">No projects in scope.</div>
        @else
            <ul class="grid gap-4 md:grid-cols-2">
                @foreach($projects as $project)
                    @php
                        $sprintProgressMax = max($project['story_points_target'], $project['active_sprint_points'], 1);
                    @endphp
                    <li class="org-widget-panel">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="text-base font-semibold text-[color:var(--gv-fg0)]">{{ $project['name'] }}</div>
                                <div class="mt-1 flex flex-wrap gap-2">
                                    <x-mary-badge value="{{ $project['key'] }}" class="badge-soft font-mono" />
                                    <x-mary-badge value="{{ $project['members'] }} members" />
                                </div>
                            </div>
                            <x-mary-badge value="{{ $project['open_tasks'] }} open" class="badge-soft" />
                        </div>

                        <div class="mt-4 grid grid-cols-3 gap-3">
                            <div class="rounded-xl border border-[color:var(--gv-border)] bg-[color:var(--gv-bg0-s)]/60 p-3">
                                <div class="org-widget-kicker">Total</div>
                                <div class="mt-2 text-xl font-semibold text-[color:var(--gv-fg0)]">{{ $project['tasks'] }}</div>
                            </div>
                            <div class="rounded-xl border border-[color:var(--gv-border)] bg-[color:var(--gv-bg0-s)]/60 p-3">
                                <div class="org-widget-kicker">Done {{ $windowDays }}d</div>
                                <div class="mt-2 text-xl font-semibold text-[color:var(--gv-fg0)]">{{ $project['completed_tasks'] }}</div>
                            </div>
                            <div class="rounded-xl border border-[color:var(--gv-border)] bg-[color:var(--gv-bg0-s)]/60 p-3">
                                <div class="org-widget-kicker">Open</div>
                                <div class="mt-2 text-xl font-semibold text-[color:var(--gv-fg0)]">{{ $project['open_tasks'] }}</div>
                            </div>
                        </div>

                        <div class="mt-4 border-t border-[color:var(--gv-border)] pt-4">
                            @if($project['active_sprint'])
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="org-widget-kicker">Active sprint</div>
                                        <div class="mt-1 text-sm font-semibold text-[color:var(--gv-fg1)]">{{ $project['active_sprint'] }}</div>
                                    </div>
                                    <x-mary-badge value="{{ $project['active_sprint_points'] }} / {{ $project['story_points_target'] }} pts" class="badge-soft" />
                                </div>
                                <progress class="progress mt-3 h-2" max="{{ $sprintProgressMax }}" value="{{ $project['active_sprint_points'] }}"></progress>
                            @else
                                <div class="rounded-xl border border-dashed border-[color:var(--gv-border)] px-4 py-3 text-sm text-[color:var(--gv-fg4)]">No active sprint</div>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
