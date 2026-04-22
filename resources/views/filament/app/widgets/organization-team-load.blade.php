<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Team workload</x-slot>

        @if(count($members) === 0)
            <div class="text-sm text-[color:var(--gv-fg4)]">No members in scope.</div>
        @else
            <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                @foreach($members as $member)
                    @php
                        $total = $member['open_tasks_count'] + $member['completed_tasks_count'];
                        $share = $total > 0 ? (int) round(($member['completed_tasks_count'] / $total) * 100) : 0;
                        $initials = collect(explode(' ', $member['name']))
                            ->filter()
                            ->take(2)
                            ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
                            ->implode('');
                    @endphp
                    <article class="org-widget-panel">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-[color:var(--gv-border)] bg-[color:var(--gv-bg1)] text-sm font-semibold text-[color:var(--gv-fg0)]">
                                    {{ $initials ?: '??' }}
                                </div>
                                <div class="min-w-0">
                                    <div class="truncate text-base font-semibold text-[color:var(--gv-fg0)]">{{ $member['name'] }}</div>
                                    <div class="truncate text-sm text-[color:var(--gv-fg4)]">{{ $member['email'] }}</div>
                                </div>
                            </div>
                            <x-mary-badge value="{{ str_replace('_', ' ', $member['role']) }}" class="badge-soft capitalize" />
                        </div>

                        <div class="mt-4 grid grid-cols-3 gap-3">
                            <div class="rounded-xl border border-[color:var(--gv-border)] bg-[color:var(--gv-bg0-s)]/60 p-3">
                                <div class="org-widget-kicker">Open</div>
                                <div class="mt-2 text-xl font-semibold text-[color:var(--gv-fg0)]">{{ $member['open_tasks_count'] }}</div>
                            </div>
                            <div class="rounded-xl border border-[color:var(--gv-border)] bg-[color:var(--gv-bg0-s)]/60 p-3">
                                <div class="org-widget-kicker">Done {{ $windowDays }}d</div>
                                <div class="mt-2 text-xl font-semibold text-[color:var(--gv-fg0)]">{{ $member['completed_tasks_count'] }}</div>
                            </div>
                            <div class="rounded-xl border border-[color:var(--gv-border)] bg-[color:var(--gv-bg0-s)]/60 p-3">
                                <div class="org-widget-kicker">Comments</div>
                                <div class="mt-2 text-xl font-semibold text-[color:var(--gv-fg0)]">{{ $member['recent_comments_count'] }}</div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="mb-2 flex items-center justify-between gap-3 text-xs text-[color:var(--gv-fg4)]">
                                <span>completion share</span>
                                <span class="font-mono">{{ $share }}% · {{ $total }} tasks touched</span>
                            </div>
                            <progress class="progress h-2" max="100" value="{{ $share }}"></progress>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
