<div class="flex h-full w-full flex-col gap-5">
    <section class="app-panel app-hero px-5 py-6 sm:px-7">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
            <div class="space-y-4">
                <div class="app-eyebrow">Planning Queue</div>
                <div>
                    <h1 class="text-4xl font-semibold tracking-tight text-neutral-50 sm:text-5xl">Backlog</h1>
                    <p class="mt-3 max-w-3xl text-base leading-7 text-neutral-300">
                        Keep unscheduled work visible, prioritize what matters next, and move tasks into a sprint when the team is ready.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <span class="app-chip">{{ $projects->count() }} projects</span>
                <span class="app-chip">{{ $backlog->count() }} unscheduled tasks</span>
            </div>
        </div>
    </section>

    <section class="app-panel app-filter-panel px-4 py-4 sm:px-5">
        <div class="mb-4">
            <div class="app-eyebrow">Scope</div>
            <div class="mt-2 text-lg font-semibold text-neutral-50">Choose a project backlog</div>
        </div>

        <div class="max-w-md">
            <x-mary-choices-offline
                wire:model.live="projectId"
                :options="$projects"
                single
                searchable
                clearable
                placeholder="Select project"
            />
        </div>
    </section>

    <section class="app-panel overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-neutral-700/60 px-5 py-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="app-eyebrow">Unscheduled Work</div>
                <div class="mt-2 text-2xl font-semibold text-neutral-50">Tasks waiting for a sprint</div>
            </div>
            <span class="app-chip">{{ $backlog->count() }} items</span>
        </div>

        @if($backlog->isEmpty())
            <div class="px-5 py-14 text-center text-sm text-neutral-500">Backlog is clear.</div>
        @else
            <ul class="divide-y divide-neutral-700/60">
                @foreach($backlog as $task)
                    <li wire:key="backlog-{{ $task->id }}" class="flex flex-col gap-4 px-5 py-4 xl:flex-row xl:items-center xl:justify-between">
                        <div class="min-w-0 space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="app-chip">{{ $task->key }}</span>
                                @if($task->epic)
                                    <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-blue-400">{{ $task->epic->name }}</span>
                                @endif
                                <span @class([
                                    'rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em]',
                                    'priority-crit' => $task->priority === 'crit',
                                    'priority-high' => $task->priority === 'high',
                                    'priority-med' => $task->priority === 'med',
                                    'priority-low' => $task->priority === 'low',
                                ])>{{ $task->priority }}</span>
                                @if($task->story_points)
                                    <span class="rounded-full bg-neutral-800 px-2.5 py-1 font-mono text-xs text-neutral-200">{{ $task->story_points }}</span>
                                @endif
                            </div>

                            <div class="text-xl font-semibold leading-tight text-neutral-50">{{ $task->title }}</div>

                            @if($task->description)
                                <div class="line-clamp-2 max-w-4xl text-sm leading-7 text-neutral-400">{{ $task->description }}</div>
                            @endif
                        </div>

                        <div class="w-full xl:w-64">
                            <select
                                class="app-select w-full text-sm"
                                x-on:change="$wire.assignToSprint({{ $task->id }}, $event.target.value || null)"
                            >
                                <option value="">Move to sprint…</option>
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
