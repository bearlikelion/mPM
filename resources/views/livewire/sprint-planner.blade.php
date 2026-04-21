<div class="flex h-full w-full flex-col gap-5">
    <section class="app-panel app-hero px-5 py-6 sm:px-7">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
            <div class="space-y-4">
                <div class="app-eyebrow">Cadence</div>
                <div>
                    <h1 class="text-4xl font-semibold tracking-tight text-neutral-50 sm:text-5xl">Sprints</h1>
                    <p class="mt-3 max-w-3xl text-base leading-7 text-neutral-300">
                        Plan focused work windows, start them when the team is ready, and end them with a clean read on what shipped and what rolls back.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <span class="app-chip">{{ $sprints->count() }} sprint{{ $sprints->count() === 1 ? '' : 's' }}</span>
                @if($projectId)
                    <span class="app-chip">Project selected</span>
                @endif
            </div>
        </div>
    </section>

    <section class="app-panel px-4 py-4 sm:px-5">
        <div class="mb-4">
            <div class="app-eyebrow">Scope</div>
            <div class="mt-2 text-lg font-semibold text-neutral-50">Choose a project</div>
        </div>

        <div class="max-w-md">
            <select wire:model.live="projectId" class="app-select w-full">
                <option value="">Select project</option>
                @foreach($projects as $project)
                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                @endforeach
            </select>
        </div>
    </section>

    @if($projectId)
        <form wire:submit="createSprint" class="app-panel grid gap-4 px-4 py-5 md:grid-cols-4 md:px-5">
            <div class="md:col-span-2">
                <flux:input wire:model="name" label="Name" placeholder="Sprint 7" />
            </div>
            <flux:input wire:model="startsAt" type="date" label="Starts" />
            <flux:input wire:model="endsAt" type="date" label="Ends" />
            <div class="md:col-span-4 flex justify-end">
                <flux:button type="submit" variant="primary" size="sm">Create sprint</flux:button>
            </div>
        </form>
    @endif

    <section class="app-panel overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-neutral-700/60 px-5 py-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="app-eyebrow">Timeline</div>
                <div class="mt-2 text-2xl font-semibold text-neutral-50">Sprint schedule</div>
            </div>
            <span class="app-chip">{{ $sprints->count() }} total</span>
        </div>

        @if($sprints->isEmpty())
            <div class="px-5 py-14 text-center text-sm text-neutral-500">No sprints yet.</div>
        @else
            <ul class="divide-y divide-neutral-700/60">
                @foreach($sprints as $sprint)
                    <li wire:key="sprint-{{ $sprint->id }}" class="flex flex-col gap-4 px-5 py-4 xl:flex-row xl:items-center xl:justify-between">
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xl font-semibold text-neutral-50">{{ $sprint->name }}</span>
                                <span @class([
                                    'rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em]',
                                    'status-active' => $sprint->isActive(),
                                    'status-ended' => $sprint->ended_at,
                                    'status-planned' => ! $sprint->isActive() && ! $sprint->ended_at,
                                ])>
                                    {{ $sprint->isActive() ? 'Active' : ($sprint->ended_at ? 'Ended' : 'Planned') }}
                                </span>
                            </div>
                            <div class="text-sm text-neutral-400">
                                {{ $sprint->starts_at?->format('M j') }} - {{ $sprint->ends_at?->format('M j, Y') }}
                            </div>
                            <div class="text-sm text-neutral-500">{{ $sprint->tasks_count }} {{ \Illuminate\Support\Str::plural('task', $sprint->tasks_count) }}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if(! $sprint->started_at)
                                <flux:button wire:click="startSprint({{ $sprint->id }})" size="sm">Start</flux:button>
                            @elseif(! $sprint->ended_at)
                                <flux:button wire:click="endSprint({{ $sprint->id }})" size="sm" variant="danger">End</flux:button>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
