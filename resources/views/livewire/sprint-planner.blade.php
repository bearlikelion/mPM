<div class="flex h-full w-full flex-col gap-4">
    <x-page-header title="Sprints" subtitle="Plan focused work windows and ship them.">
        <x-slot:actions>
            <span class="app-chip">{{ $sprints->count() }} sprint{{ $sprints->count() === 1 ? '' : 's' }}</span>
        </x-slot:actions>
    </x-page-header>

    <div class="max-w-sm">
        <x-mary-choices-offline
            wire:model.live="projectId"
            :options="$projects"
            single
            searchable
            clearable
            placeholder="Select project"
        />
    </div>

    @if($projectId)
        <form wire:submit="createSprint" class="gv-card grid gap-3 p-3 md:grid-cols-[2fr_1fr_1fr_auto] md:items-end">
            <x-mary-input wire:model="name" label="Name" placeholder="Sprint 7" />
            <x-mary-input wire:model="startsAt" type="date" label="Starts" />
            <x-mary-input wire:model="endsAt" type="date" label="Ends" />
            <x-mary-button type="submit" label="Create" spinner="createSprint" class="btn-primary btn-sm" />
        </form>
    @endif

    <section class="gv-card overflow-hidden">
        <div class="flex items-center justify-between border-b border-[color:var(--gv-border)] px-3 py-2">
            <span class="text-sm font-semibold uppercase tracking-wide text-[color:var(--gv-amber)]">» sprint schedule</span>
            <span class="app-chip">{{ $sprints->count() }} total</span>
        </div>

        @if($sprints->isEmpty())
            <div class="px-3 py-8 text-center text-sm text-[color:var(--gv-fg4)]">no sprints yet</div>
        @else
            <ul class="divide-y divide-[color:var(--gv-border)]">
                @foreach($sprints as $sprint)
                    <li wire:key="sprint-{{ $sprint->id }}" class="flex flex-col gap-2 px-3 py-3 md:flex-row md:items-center md:justify-between">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="font-mono text-[0.9rem] font-semibold text-[color:var(--gv-fg0)]">{{ $sprint->name }}</span>
                            <span @class([
                                'rounded-sm px-1.5 py-0.5 font-mono text-[0.6rem] font-semibold uppercase tracking-[0.14em]',
                                'status-active' => $sprint->isActive(),
                                'status-ended' => $sprint->ended_at,
                                'status-planned' => ! $sprint->isActive() && ! $sprint->ended_at,
                            ])>
                                {{ $sprint->isActive() ? 'active' : ($sprint->ended_at ? 'ended' : 'planned') }}
                            </span>
                            <span class="font-mono text-[0.68rem] text-[color:var(--gv-fg4)]">{{ $sprint->starts_at?->format('M j') }} → {{ $sprint->ends_at?->format('M j, Y') }}</span>
                            <span class="font-mono text-[0.68rem] text-[color:var(--gv-fg4)]">· {{ $sprint->tasks_count }} {{ \Illuminate\Support\Str::plural('task', $sprint->tasks_count) }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            @if(! $sprint->started_at)
                                <x-mary-button wire:click="startSprint({{ $sprint->id }})" label="Start" spinner="startSprint({{ $sprint->id }})" class="btn-sm btn-primary" />
                            @elseif(! $sprint->ended_at)
                                <x-mary-button wire:click="endSprint({{ $sprint->id }})" label="End" spinner="endSprint({{ $sprint->id }})" class="btn-sm btn-error" />
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
