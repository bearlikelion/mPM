<div
    class="flex h-full w-full flex-col gap-5"
    x-data="{
        dragging: null,
        justDragged: false,
        start(id) { this.dragging = id; this.justDragged = false; },
        end() { this.dragging = null; this.justDragged = true; setTimeout(() => this.justDragged = false, 150); },
        drop(status) {
            if (this.dragging) {
                $wire.updateStatus(this.dragging, status);
                this.dragging = null;
            }
        },
        open(key) {
            if (this.justDragged) return;
            window.Livewire.navigate('/tasks/' + key);
        }
    }"
>
    <section class="app-panel app-hero px-5 py-6 sm:px-7">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
            <div class="space-y-4">
                <div class="app-eyebrow">Delivery Flow</div>
                <div>
                    <h1 class="text-4xl font-semibold tracking-tight text-neutral-50 sm:text-5xl">Kanban</h1>
                    <p class="mt-3 max-w-3xl text-base leading-7 text-neutral-300">
                        Review active work by project, sprint, assignee, epic, or tag. Drag cards across lanes to reflect what is actually moving.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <span class="app-chip">{{ $projects->count() }} {{ \Illuminate\Support\Str::plural('project', $projects->count()) }}</span>
                <span class="app-chip">{{ $lanes->sum(fn ($lane) => $lane->count()) }} visible tasks</span>
            </div>
        </div>
    </section>

    <section class="app-panel px-4 py-4 sm:px-5">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <div class="app-eyebrow">Filters</div>
                <div class="mt-2 text-lg font-semibold text-neutral-50">Slice the board by project context</div>
            </div>
        </div>
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <select wire:model.live="projectId" class="app-select">
                <option value="">All projects</option>
                @foreach($projects as $project)
                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="sprintId" class="app-select">
                <option value="">All sprints</option>
                @foreach($sprints as $sprint)
                    <option value="{{ $sprint->id }}">{{ $sprint->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="assigneeId" class="app-select">
                <option value="">Anyone</option>
                @foreach($assignees as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="epicId" class="app-select">
                <option value="">All epics</option>
                @foreach($epics as $epic)
                    <option value="{{ $epic->id }}">{{ $epic->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="tagId" class="app-select">
                <option value="">All tags</option>
                @foreach($tags as $tag)
                    <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                @endforeach
            </select>
        </div>
    </section>

    <div class="grid flex-1 gap-4 2xl:grid-cols-4 xl:grid-cols-2">
        @foreach(\App\Models\Task::STATUSES as $status)
            @php
                $label = ['todo' => 'To do', 'in_progress' => 'In progress', 'review' => 'Review', 'done' => 'Done'][$status];
                $tasks = $lanes[$status];
            @endphp
            <section
                class="app-panel flex min-h-[32rem] flex-col overflow-hidden"
                x-on:dragover.prevent
                x-on:drop="drop('{{ $status }}')"
            >
                <header class="flex items-center justify-between border-b border-neutral-700/60 px-4 py-4">
                    <div>
                        <div class="app-eyebrow">{{ strtoupper(str_replace('_', ' ', $status)) }}</div>
                        <div class="mt-2 text-2xl font-semibold text-neutral-50">{{ $label }}</div>
                    </div>
                    <span class="app-chip">{{ $tasks->count() }}</span>
                </header>

                <div class="flex flex-1 flex-col gap-3 p-3">
                    @forelse($tasks as $task)
                        <article
                            draggable="true"
                            x-on:dragstart="start({{ $task->id }})"
                            x-on:dragend="end()"
                            wire:key="task-{{ $task->id }}"
                            x-on:click="open('{{ $task->key }}')"
                            @if($highlightId === $task->id)
                                x-init="$el.scrollIntoView({ behavior: 'smooth', block: 'center' }); $el.classList.add('ring-2','ring-amber-300','ring-offset-2','ring-offset-neutral-950'); setTimeout(() => $el.classList.remove('ring-2','ring-amber-300','ring-offset-2','ring-offset-neutral-950'), 2500)"
                            @endif
                            class="app-panel-muted cursor-pointer rounded-2xl p-4 transition hover:-translate-y-0.5 hover:border-amber-400/40 hover:bg-neutral-950/70"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <span class="app-chip">{{ $task->key }}</span>
                                <span @class([
                                    'rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em]',
                                    'priority-crit' => $task->priority === 'crit',
                                    'priority-high' => $task->priority === 'high',
                                    'priority-med' => $task->priority === 'med',
                                    'priority-low' => $task->priority === 'low',
                                ])>{{ $task->priority }}</span>
                            </div>

                            <div class="mt-4 text-xl font-semibold leading-tight text-neutral-50">{{ $task->title }}</div>

                            @if($task->description)
                                <div class="mt-3 line-clamp-3 text-sm leading-7 text-neutral-400">{{ $task->description }}</div>
                            @endif

                            <div class="mt-5 flex items-end justify-between gap-3">
                                @if($task->assignees->isNotEmpty())
                                    <div class="flex -space-x-2">
                                        @foreach($task->assignees->take(4) as $user)
                                            <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" title="{{ $user->name }}" class="h-8 w-8 rounded-full border-2 border-neutral-950 object-cover" />
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs italic text-neutral-500">Unassigned</span>
                                @endif

                                <div class="flex items-center gap-2">
                                    @if($task->project)
                                        <span class="text-xs text-neutral-500">{{ $task->project->name }}</span>
                                    @endif
                                    @if($task->story_points)
                                        <span class="rounded-full bg-neutral-800 px-2.5 py-1 font-mono text-xs text-neutral-200">{{ $task->story_points }}</span>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="flex flex-1 items-center justify-center rounded-2xl border border-dashed border-neutral-700/70 bg-neutral-950/35 px-4 py-10 text-center text-sm text-neutral-500">
                            No tasks in this lane.
                        </div>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
</div>
