<x-filament-panels::page>
    @php($data = $this->getViewData())
    @php($org = $data['org'])
    @php($stats = $data['stats'])

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach([
            ['Members', $stats['members']],
            ['Projects', $stats['projects']],
            ['Tasks', $stats['tasks']],
            ['Open tasks', $stats['tasks_open']],
            ['Tasks · 30d', $stats['tasks_30d']],
            ['Comments · 30d', $stats['comments_30d']],
            ['Storage', $stats['storage']],
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
        <x-slot name="heading">Projects</x-slot>
        @if($data['projects']->isEmpty())
            <div class="text-sm text-[color:var(--gv-fg4)]">No projects yet.</div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[color:var(--gv-fg4)] text-xs uppercase tracking-wider">
                        <th class="py-2">Name</th>
                        <th>Key</th>
                        <th>Visibility</th>
                        <th>Members</th>
                        <th>Tasks</th>
                        <th>Open</th>
                        <th>Done</th>
                        <th>Last update</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['projects'] as $p)
                        <tr class="border-t border-[color:var(--gv-border)]">
                            <td class="py-2 text-[color:var(--gv-fg1)]">{{ $p->name }}</td>
                            <td class="font-mono text-xs text-[color:var(--gv-fg3)]">{{ $p->key }}</td>
                            <td class="text-[color:var(--gv-fg3)]">{{ $p->visibility }}</td>
                            <td class="font-mono text-xs">{{ $p->members_count }}</td>
                            <td class="font-mono text-xs">{{ $p->tasks_count }}</td>
                            <td class="font-mono text-xs">{{ $p->tasks_open_count }}</td>
                            <td class="font-mono text-xs">{{ $p->tasks_done_count }}</td>
                            <td class="text-xs text-[color:var(--gv-fg4)]">{{ $p->updated_at?->diffForHumans() ?? '-' }}</td>
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
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['members'] as $u)
                        <tr class="border-t border-[color:var(--gv-border)]">
                            <td class="py-2 text-[color:var(--gv-fg1)]">{{ $u->name }}</td>
                            <td class="text-[color:var(--gv-fg3)]">{{ $u->email }}</td>
                            <td class="font-mono text-xs">{{ $u->pivot->role }}</td>
                            <td class="text-xs text-[color:var(--gv-fg4)]">
                                {{ $u->pivot->joined_at ? \Carbon\Carbon::parse($u->pivot->joined_at)->diffForHumans() : '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Recent invites</x-slot>
        @if($data['invites']->isEmpty())
            <div class="text-sm text-[color:var(--gv-fg4)]">No invites sent.</div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[color:var(--gv-fg4)] text-xs uppercase tracking-wider">
                        <th class="py-2">Email</th>
                        <th>Role</th>
                        <th>Sent</th>
                        <th>Accepted</th>
                        <th>Expires</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['invites'] as $i)
                        <tr class="border-t border-[color:var(--gv-border)]">
                            <td class="py-2 text-[color:var(--gv-fg1)]">{{ $i->email }}</td>
                            <td class="font-mono text-xs">{{ $i->role }}</td>
                            <td class="text-xs text-[color:var(--gv-fg4)]">{{ $i->created_at?->diffForHumans() }}</td>
                            <td class="text-xs text-[color:var(--gv-fg4)]">{{ $i->accepted_at?->diffForHumans() ?? '-' }}</td>
                            <td class="text-xs text-[color:var(--gv-fg4)]">{{ $i->expires_at?->diffForHumans() ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>
</x-filament-panels::page>
