<div class="flex h-full w-full flex-col gap-4">
    <x-page-header title="Backlog" subtitle="Prioritize unscheduled work and move it into a sprint.">
        <x-slot:actions>
            <span class="app-chip">{{ $projects->count() }} projects</span>
            @if($activeSprint)
                <span class="app-chip text-amber-400">{{ $unassignedTasks->count() }} unassigned</span>
            @endif
            <span class="app-chip">{{ $backlog->count() }} unscheduled</span>
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

    @if($activeSprint)
        <section class="gv-card overflow-hidden">
            <div class="flex items-center justify-between border-b border-[color:var(--gv-border)] px-3 py-2">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold uppercase tracking-wide text-amber-400">» unassigned tasks</span>
                    <span class="rounded-sm border border-amber-700/40 bg-amber-950/30 px-1.5 py-0.5 font-mono text-[0.6rem] font-semibold uppercase tracking-[0.12em] text-amber-400">{{ $activeSprint->name }} active</span>
                </div>
                <span class="app-chip">{{ $unassignedTasks->count() }} items</span>
            </div>

            @if($unassignedTasks->isEmpty())
                <div class="px-3 py-8 text-center text-sm text-[color:var(--gv-fg4)]">no unassigned tasks</div>
            @else
                <ul class="divide-y divide-[color:var(--gv-border)]">
                    @foreach($unassignedTasks as $task)
                        <li wire:key="unassigned-{{ $task->id }}" class="flex flex-col gap-3 px-3 py-3 xl:flex-row xl:items-center xl:justify-between">
                            <div class="min-w-0 space-y-1.5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono text-[0.68rem] text-[color:var(--gv-fg4)]">{{ $task->key }}</span>
                                    @if($task->epic)
                                        <span class="rounded-sm border border-[color:var(--gv-border)] px-1.5 py-0.5 font-mono text-[0.6rem] font-semibold uppercase tracking-[0.12em] text-[color:var(--gv-blue)]">{{ $task->epic->name }}</span>
                                    @endif
                                    <span @class([
                                        'rounded-sm px-1.5 py-0.5 font-mono text-[0.6rem] font-semibold uppercase tracking-[0.12em]',
                                        'priority-crit' => $task->priority === 'crit',
                                        'priority-high' => $task->priority === 'high',
                                        'priority-med' => $task->priority === 'med',
                                        'priority-low' => $task->priority === 'low',
                                    ])>{{ $task->priority }}</span>
                                    @if($task->story_points)
                                        <span class="rounded-sm border border-[color:var(--gv-border)] bg-[color:var(--gv-bg1)] px-1.5 py-0.5 text-xs text-[color:var(--gv-fg2)]">{{ $task->story_points }}</span>
                                    @endif
                                </div>
                                <div class="text-sm font-semibold leading-snug text-[color:var(--gv-fg0)]">{{ $task->title }}</div>
                                @if($task->description)
                                    <div class="line-clamp-2 max-w-4xl text-xs text-[color:var(--gv-fg4)]">{{ $task->description }}</div>
                                @endif
                            </div>

                            <div class="w-full xl:w-56">
                                <select
                                    class="select select-sm w-full font-mono text-xs"
                                    x-on:change="$wire.assignToSprint({{ $task->id }}, $event.target.value || null)"
                                >
                                    <option value="">move to sprint…</option>
                                    @foreach($sprints as $sprint)
                                        <option value="{{ $sprint->id }}">{{ $sprint->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif

    <section class="gv-card overflow-hidden">
        <div class="flex items-center justify-between border-b border-[color:var(--gv-border)] px-3 py-2">
            <span class="text-sm font-semibold uppercase tracking-wide text-[color:var(--gv-amber)]">» tasks waiting for a sprint</span>
            <span class="app-chip">{{ $backlog->count() }} items</span>
        </div>

        @if($backlog->isEmpty())
            <div class="px-3 py-8 text-center text-sm text-[color:var(--gv-fg4)]">backlog is clear</div>
        @else
            <ul class="divide-y divide-[color:var(--gv-border)]">
                @foreach($backlog as $task)
                    <li wire:key="backlog-{{ $task->id }}" class="flex flex-col gap-3 px-3 py-3 xl:flex-row xl:items-center xl:justify-between">
                        <div class="min-w-0 space-y-1.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-[0.68rem] text-[color:var(--gv-fg4)]">{{ $task->key }}</span>
                                @if($task->epic)
                                    <span class="rounded-sm border border-[color:var(--gv-border)] px-1.5 py-0.5 font-mono text-[0.6rem] font-semibold uppercase tracking-[0.12em] text-[color:var(--gv-blue)]">{{ $task->epic->name }}</span>
                                @endif
                                <span @class([
                                    'rounded-sm px-1.5 py-0.5 font-mono text-[0.6rem] font-semibold uppercase tracking-[0.12em]',
                                    'priority-crit' => $task->priority === 'crit',
                                    'priority-high' => $task->priority === 'high',
                                    'priority-med' => $task->priority === 'med',
                                    'priority-low' => $task->priority === 'low',
                                ])>{{ $task->priority }}</span>
                                @if($task->story_points)
                                    <span class="rounded-sm border border-[color:var(--gv-border)] bg-[color:var(--gv-bg1)] px-1.5 py-0.5 text-xs text-[color:var(--gv-fg2)]">{{ $task->story_points }}</span>
                                @endif
                            </div>
                            <div class="text-sm font-semibold leading-snug text-[color:var(--gv-fg0)]">{{ $task->title }}</div>
                            @if($task->description)
                                <div class="line-clamp-2 max-w-4xl text-xs text-[color:var(--gv-fg4)]">{{ $task->description }}</div>
                            @endif
                        </div>

                        <div class="w-full xl:w-56">
                            <select
                                class="select select-sm w-full font-mono text-xs"
                                x-on:change="$wire.assignToSprint({{ $task->id }}, $event.target.value || null)"
                            >
                                <option value="">move to sprint…</option>
                                @foreach($sprints as $sprint)
                                    <option value="{{ $sprint->id }}">{{ $sprint->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
