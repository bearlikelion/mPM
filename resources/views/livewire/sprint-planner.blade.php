<div class="flex h-full w-full flex-col gap-4 p-1">
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="text-xl font-semibold">Sprints</h1>

        <select wire:model.live="projectId" class="rounded-md border-neutral-300 bg-white text-sm dark:border-neutral-700 dark:bg-neutral-900">
            <option value="">Select project</option>
            @foreach($projects as $project)
                <option value="{{ $project->id }}">{{ $project->name }}</option>
            @endforeach
        </select>
    </div>

    @if($projectId)
        <form wire:submit="createSprint" class="grid gap-3 rounded-xl border border-neutral-200 p-4 md:grid-cols-4 dark:border-neutral-700">
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

    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700">
        <div class="border-b border-neutral-200 px-4 py-3 text-sm font-medium dark:border-neutral-700">
            Sprints ({{ $sprints->count() }})
        </div>

        @if($sprints->isEmpty())
            <div class="px-4 py-8 text-center text-sm text-neutral-500">No sprints yet.</div>
        @else
            <ul class="divide-y divide-neutral-200 dark:divide-neutral-700">
                @foreach($sprints as $sprint)
                    <li wire:key="sprint-{{ $sprint->id }}" class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-sm">
                        <div class="flex items-center gap-3">
                            <span class="font-medium">{{ $sprint->name }}</span>
                            <span class="text-xs text-neutral-500">
                                {{ $sprint->starts_at?->format('M j') }} – {{ $sprint->ends_at?->format('M j') }}
                            </span>
                            <span class="rounded bg-neutral-100 px-2 text-xs dark:bg-neutral-800">{{ $sprint->tasks_count }} tasks</span>
                            @if($sprint->isActive())
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] uppercase tracking-wide text-emerald-700">Active</span>
                            @elseif($sprint->ended_at)
                                <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-[10px] uppercase tracking-wide text-neutral-600">Ended</span>
                            @else
                                <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] uppercase tracking-wide text-blue-700">Planned</span>
                            @endif
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
    </div>
</div>
