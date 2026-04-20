<div
    class="flex h-full w-full flex-col gap-4 p-1"
    x-data="{
        dragging: null,
        start(id) { this.dragging = id; },
        end() { this.dragging = null; },
        drop(status) {
            if (this.dragging) {
                $wire.updateStatus(this.dragging, status);
                this.dragging = null;
            }
        }
    }"
>
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="text-xl font-semibold">Kanban</h1>

        <select wire:model.live="projectId" class="rounded-md border-neutral-300 bg-white text-sm dark:border-neutral-700 dark:bg-neutral-900">
            <option value="">All projects</option>
            @foreach($projects as $project)
                <option value="{{ $project->id }}">{{ $project->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="sprintId" class="rounded-md border-neutral-300 bg-white text-sm dark:border-neutral-700 dark:bg-neutral-900">
            <option value="">All sprints</option>
            @foreach($sprints as $sprint)
                <option value="{{ $sprint->id }}">{{ $sprint->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="assigneeId" class="rounded-md border-neutral-300 bg-white text-sm dark:border-neutral-700 dark:bg-neutral-900">
            <option value="">Anyone</option>
            @foreach($assignees as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="grid flex-1 gap-3 md:grid-cols-4">
        @foreach(\App\Models\Task::STATUSES as $status)
            @php
                $label = ['todo' => 'To do', 'in_progress' => 'In progress', 'review' => 'Review', 'done' => 'Done'][$status];
                $tasks = $lanes[$status];
            @endphp
            <div
                class="flex flex-col rounded-xl border border-neutral-200 bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-900"
                x-on:dragover.prevent
                x-on:drop="drop('{{ $status }}')"
            >
                <div class="flex items-center justify-between border-b border-neutral-200 px-3 py-2 text-sm font-medium dark:border-neutral-700">
                    <span>{{ $label }}</span>
                    <span class="rounded-full bg-neutral-200 px-2 text-xs text-neutral-700 dark:bg-neutral-700 dark:text-neutral-200">{{ $tasks->count() }}</span>
                </div>

                <div class="flex flex-1 flex-col gap-2 p-2">
                    @foreach($tasks as $task)
                        <div
                            draggable="true"
                            x-on:dragstart="start({{ $task->id }})"
                            x-on:dragend="end()"
                            wire:key="task-{{ $task->id }}"
                            class="cursor-grab rounded-lg border border-neutral-200 bg-white p-3 text-sm shadow-sm active:cursor-grabbing dark:border-neutral-700 dark:bg-neutral-800"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <a href="{{ route('tasks.show', $task->key) }}" wire:navigate class="font-mono text-xs text-neutral-500 hover:underline">{{ $task->key }}</a>
                                <span class="rounded-full px-2 py-0.5 text-[10px] uppercase tracking-wide {{ match($task->priority) {
                                    'crit' => 'bg-red-100 text-red-700',
                                    'high' => 'bg-orange-100 text-orange-700',
                                    'med' => 'bg-blue-100 text-blue-700',
                                    default => 'bg-neutral-100 text-neutral-600',
                                } }}">{{ $task->priority }}</span>
                            </div>
                            <div class="mt-1 font-medium">{{ $task->title }}</div>
                            @if($task->assignees->isNotEmpty())
                                <div class="mt-2 flex -space-x-1">
                                    @foreach($task->assignees->take(4) as $user)
                                        <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" title="{{ $user->name }}" class="h-5 w-5 rounded-full border border-white dark:border-neutral-800" />
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
