<div class="flex h-full w-full flex-col gap-4 p-1">
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="text-xl font-semibold">Backlog</h1>

        <select wire:model.live="projectId" class="rounded-md border-neutral-300 bg-white text-sm dark:border-neutral-700 dark:bg-neutral-900">
            <option value="">Select project</option>
            @foreach($projects as $project)
                <option value="{{ $project->id }}">{{ $project->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700">
        <div class="border-b border-neutral-200 px-4 py-3 text-sm font-medium dark:border-neutral-700">
            Unassigned tasks ({{ $backlog->count() }})
        </div>

        @if($backlog->isEmpty())
            <div class="px-4 py-8 text-center text-sm text-neutral-500">
                Backlog is clear.
            </div>
        @else
            <ul class="divide-y divide-neutral-200 dark:divide-neutral-700">
                @foreach($backlog as $task)
                    <li wire:key="backlog-{{ $task->id }}" class="flex flex-wrap items-center justify-between gap-2 px-4 py-2 text-sm">
                        <div class="flex min-w-0 items-center gap-2">
                            <span class="font-mono text-xs text-neutral-500">{{ $task->key }}</span>
                            <span class="truncate">{{ $task->title }}</span>
                            @if($task->epic)
                                <span class="rounded-full bg-purple-100 px-2 py-0.5 text-[10px] uppercase tracking-wide text-purple-700">{{ $task->epic->name }}</span>
                            @endif
                            <span class="rounded-full px-2 py-0.5 text-[10px] uppercase tracking-wide {{ match($task->priority) {
                                'crit' => 'bg-red-100 text-red-700',
                                'high' => 'bg-orange-100 text-orange-700',
                                'med' => 'bg-blue-100 text-blue-700',
                                default => 'bg-neutral-100 text-neutral-600',
                            } }}">{{ $task->priority }}</span>
                            @if($task->story_points)
                                <span class="rounded bg-neutral-200 px-1.5 text-[10px] font-mono dark:bg-neutral-700">{{ $task->story_points }}</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <select
                                class="rounded-md border-neutral-300 bg-white text-xs dark:border-neutral-700 dark:bg-neutral-900"
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
    </div>
</div>
