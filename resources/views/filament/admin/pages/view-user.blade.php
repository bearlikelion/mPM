<x-filament-panels::page>
    @php($data = $this->getViewData())
    @php($user = $data['user'])
    @php($stats = $data['stats'])

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach([
            ['Orgs', $stats['orgs']],
            ['Assigned tasks', $stats['assigned_total']],
            ['Open assigned', $stats['assigned_open']],
            ['Done · 30d', $stats['done_30d']],
            ['Comments · 30d', $stats['comments_30d']],
            ['Tasks created', $stats['created_tasks']],
            ['Storage', $stats['storage']],
            ['Last active', $stats['last_active'] ? \Carbon\Carbon::parse($stats['last_active'])->diffForHumans() : '—'],
        ] as [$label, $value])
            <div class="p-4 rounded-sm border border-[color:var(--gv-border)] bg-[color:var(--gv-bg0)]">
                <div class="text-xs uppercase tracking-wider text-[color:var(--gv-fg4)]">{{ $label }}</div>
                <div class="mt-1 font-mono text-lg text-[color:var(--gv-fg0)]">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <x-filament::section>
        <x-slot name="heading">Assigned task status</x-slot>
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
        <x-slot name="heading">Comments · last 30 days</x-slot>
        <div class="flex items-end gap-[2px] h-24">
            @foreach($data['commentTrend'] as $n)
                @php($h = max(2, (int) round($n / $data['commentTrendMax'] * 100)))
                <div class="flex-1 bg-[color:var(--fi-accent,var(--gv-amber))] opacity-80 rounded-t-sm"
                     style="height: {{ $h }}%" title="{{ $n }}"></div>
            @endforeach
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Organizations</x-slot>
        @if($data['orgs']->isEmpty())
            <div class="text-sm text-[color:var(--gv-fg4)]">Not a member of any org.</div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[color:var(--gv-fg4)] text-xs uppercase tracking-wider">
                        <th class="py-2">Name</th>
                        <th>Role</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['orgs'] as $o)
                        <tr class="border-t border-[color:var(--gv-border)]">
                            <td class="py-2 text-[color:var(--gv-fg1)]">
                                <a href="{{ route('filament.admin.resources.organizations.view', ['record' => $o->id]) }}"
                                   class="hover:text-[color:var(--fi-accent,var(--gv-amber))]">
                                    {{ $o->name }}
                                </a>
                            </td>
                            <td class="font-mono text-xs">{{ $o->pivot->role }}</td>
                            <td class="text-xs text-[color:var(--gv-fg4)]">
                                {{ $o->pivot->joined_at ? \Carbon\Carbon::parse($o->pivot->joined_at)->diffForHumans() : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Recently assigned tasks</x-slot>
        @if($data['recentAssigned']->isEmpty())
            <div class="text-sm text-[color:var(--gv-fg4)]">No assigned tasks.</div>
        @else
            <ul class="divide-y divide-[color:var(--gv-border)]">
                @foreach($data['recentAssigned'] as $t)
                    <li class="py-2 flex items-center justify-between text-sm">
                        <div class="min-w-0">
                            <span class="font-mono text-xs text-[color:var(--gv-fg3)]">{{ $t->key }}</span>
                            <span class="ml-2 text-[color:var(--gv-fg1)]">{{ $t->title }}</span>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-[color:var(--gv-fg4)]">
                            <span class="font-mono">{{ $t->status }}</span>
                            <span>{{ $t->updated_at?->diffForHumans() }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Recent comments</x-slot>
        @if($data['recentComments']->isEmpty())
            <div class="text-sm text-[color:var(--gv-fg4)]">No comments yet.</div>
        @else
            <ul class="divide-y divide-[color:var(--gv-border)]">
                @foreach($data['recentComments'] as $c)
                    <li class="py-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-[color:var(--gv-fg3)]">
                                on <span class="font-mono text-xs">{{ $c->task?->key ?? '—' }}</span>
                                @if($c->task?->project) · <span class="text-[color:var(--gv-fg4)]">{{ $c->task->project->name }}</span> @endif
                            </span>
                            <span class="text-xs text-[color:var(--gv-fg4)]">{{ $c->created_at?->diffForHumans() }}</span>
                        </div>
                        <div class="mt-1 text-[color:var(--gv-fg1)] line-clamp-2">{{ \Illuminate\Support\Str::limit(strip_tags($c->body), 180) }}</div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-panels::page>
