<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Top organizations</x-slot>

        <div class="grid grid-cols-1 gap-5">
            <div>
                <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-[color:var(--gv-fg4)]">By activity · 30d</div>
                @if(count($byActivity) === 0)
                    <div class="text-sm text-[color:var(--gv-fg4)]">no activity yet</div>
                @else
                    <ul class="space-y-1">
                        @foreach($byActivity as $row)
                            <li class="flex items-center justify-between text-sm">
                                <a href="{{ route('filament.admin.resources.organizations.view', ['record' => $row['id']]) }}"
                                   class="text-[color:var(--gv-fg1)] hover:text-[color:var(--fi-accent,var(--gv-amber))]">
                                    {{ $row['name'] }}
                                </a>
                                <span class="font-mono text-xs text-[color:var(--gv-fg3)]">{{ $row['tasks_created'] }} tasks</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div>
                <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-[color:var(--gv-fg4)]">By storage</div>
                @if(count($byStorage) === 0)
                    <div class="text-sm text-[color:var(--gv-fg4)]">no uploads yet</div>
                @else
                    <ul class="space-y-1">
                        @foreach($byStorage as $row)
                            <li class="flex items-center justify-between text-sm">
                                <a href="{{ route('filament.admin.resources.organizations.view', ['record' => $row['id']]) }}"
                                   class="text-[color:var(--gv-fg1)] hover:text-[color:var(--fi-accent,var(--gv-amber))]">
                                    {{ $row['name'] }}
                                </a>
                                <span class="font-mono text-xs text-[color:var(--gv-fg3)]">{{ $row['human'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
