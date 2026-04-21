<x-filament-panels::page>
    @php($data = $this->getViewData())
    @php($project = $data['project'])
    @php($stats = $data['stats'])

    <div class="text-xs uppercase tracking-wider text-[color:var(--gv-fg4)]">
        <a href="{{ route('filament.admin.resources.organizations.view', ['record' => $project->organization_id]) }}"
           class="hover:text-[color:var(--fi-accent,var(--gv-amber))]">
            {{ $project->organization?->name }}
        </a>
        &nbsp;/&nbsp; <span class="font-mono text-[color:var(--fi-accent,var(--gv-amber))]">{{ $project->key }}</span>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach([
            ['Tasks', $stats['tasks']],
            ['Open', $stats['tasks_open']],
            ['Done', $stats['tasks_done']],
            ['Tasks · 30d', $stats['tasks_30d']],
            ['Comments · 30d', $stats['comments_30d']],
            ['Epics', $stats['epics'].' ('.$stats['epics_done'].' done)'],
            ['Members', $stats['members']],
            ['Active sprint', $stats['active_sprint']],
            ['Completion', $stats['completion_rate'].'%'],
        ] as [$label, $value])
            <div class="p-4 rounded-sm border border-[color:var(--gv-border)] bg-[color:var(--gv-bg0)]">
                <div class="text-xs uppercase tracking-wider text-[color:var(--gv-fg4)]">{{ $label }}</div>
                <div class="mt-1 font-mono text-lg text-[color:var(--gv-fg0)]">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <x-filament::section>
        <x-slot name="heading">Task status distribution</x-slot>
        <div class="space-y-1.5">
            @foreach($data['statusBars'] as $row)
                <div class="flex items-center gap-3 text-sm">
                    <span class="w-28 text-[color:var(--gv-fg2)]">{{ $row['label'] }}</span>
                    <div class="flex-1 h-2 rounded-sm bg-[color:var(--gv-bg1)] overflow-hidden">
                        <div class="h-full bg-[color:var(--fi-accent,var(--gv-amber))]" style="width: {{ $row['percent'] }}%"></div>
                    </div>
                    <span class="w-20 text-right font-mono text-xs text-[color:var(--gv-fg1)]">{{ $row['count'] }} · {{ $row['percent'] }}%</span>
                </div>
            @endforeach
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Priority distribution</x-slot>
        <div class="space-y-1.5">
            @foreach($data['priorityBars'] as $row)
                <div class="flex items-center gap-3 text-sm">
                    <span class="w-28 text-[color:var(--gv-fg2)]">{{ $row['label'] }}</span>
                    <div class="flex-1 h-2 rounded-sm bg-[color:var(--gv-bg1)] overflow-hidden">
                        <div class="h-full bg-[color:var(--fi-accent,var(--gv-amber))]" style="width: {{ $row['percent'] }}%"></div>
                    </div>
                    <span class="w-20 text-right font-mono text-xs text-[color:var(--gv-fg1)]">{{ $row['count'] }} · {{ $row['percent'] }}%</span>
                </div>
            @endforeach
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Tasks created · last 30 days</x-slot>
        <div class="flex items-end gap-[2px] h-24">
            @foreach($data['tasksTrend'] as $n)
                @php($h = max(2, (int) round($n / $data['tasksTrendMax'] * 100)))
                <div class="flex-1 bg-[color:var(--fi-accent,var(--gv-amber))] opacity-80 rounded-t-sm"
                     style="height: {{ $h }}%" title="{{ $n }}"></div>
            @endforeach
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Epics</x-slot>
        @if($data['epics']->isEmpty())
            <div class="text-sm text-[color:var(--gv-fg4)]">No epics.</div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[color:var(--gv-fg4)] text-xs uppercase tracking-wider">
                        <th class="py-2">Name</th>
                        <th>Tasks</th>
                        <th>Done</th>
                        <th>Due</th>
                        <th>Completed</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['epics'] as $e)
                        <tr class="border-t border-[color:var(--gv-border)]">
                            <td class="py-2 text-[color:var(--gv-fg1)]">{{ $e->name }}</td>
                            <td class="font-mono text-xs">{{ $e->tasks_count }}</td>
                            <td class="font-mono text-xs">{{ $e->tasks_done_count }}</td>
                            <td class="text-xs text-[color:var(--gv-fg4)]">{{ $e->due_date?->format('M j, Y') ?? '—' }}</td>
                            <td class="text-xs text-[color:var(--gv-fg4)]">{{ $e->completed_at?->diffForHumans() ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Sprints</x-slot>
        @if($data['sprints']->isEmpty())
            <div class="text-sm text-[color:var(--gv-fg4)]">No sprints yet.</div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[color:var(--gv-fg4)] text-xs uppercase tracking-wider">
                        <th class="py-2">Name</th>
                        <th>Tasks</th>
                        <th>Starts</th>
                        <th>Ends</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['sprints'] as $s)
                        <tr class="border-t border-[color:var(--gv-border)]">
                            <td class="py-2 text-[color:var(--gv-fg1)]">{{ $s->name }}</td>
                            <td class="font-mono text-xs">{{ $s->tasks_count }}</td>
                            <td class="text-xs text-[color:var(--gv-fg4)]">{{ $s->starts_at?->format('M j') ?? '—' }}</td>
                            <td class="text-xs text-[color:var(--gv-fg4)]">{{ $s->ends_at?->format('M j') ?? '—' }}</td>
                            <td class="font-mono text-xs">
                                @if($s->started_at && ! $s->ended_at)
                                    <span class="text-[color:var(--fi-accent,var(--gv-amber))]">active</span>
                                @elseif($s->ended_at)
                                    ended
                                @else
                                    planned
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Members</x-slot>
        @if($data['members']->isEmpty())
            <div class="text-sm text-[color:var(--gv-fg4)]">No members.</div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[color:var(--gv-fg4)] text-xs uppercase tracking-wider">
                        <th class="py-2">Name</th>
                        <th>Email</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['members'] as $u)
                        <tr class="border-t border-[color:var(--gv-border)]">
                            <td class="py-2 text-[color:var(--gv-fg1)]">
                                <a href="{{ route('filament.admin.resources.users.view', ['record' => $u->id]) }}"
                                   class="hover:text-[color:var(--fi-accent,var(--gv-amber))]">{{ $u->name }}</a>
                            </td>
                            <td class="text-[color:var(--gv-fg3)]">{{ $u->email }}</td>
                            <td class="font-mono text-xs">{{ $u->pivot->role ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>
</x-filament-panels::page>
