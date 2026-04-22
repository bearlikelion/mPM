<div
    class="flex h-full w-full flex-col gap-4"
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
            $wire.set('taskKey', key);
        }
    }"
>
    <x-page-header title="Kanban" subtitle="Drag cards across lanes to reflect what's moving.">
        <x-slot:actions>
            <span class="app-chip">{{ $projects->count() }} {{ \Illuminate\Support\Str::plural('project', $projects->count()) }}</span>
            <span class="app-chip">{{ $lanes->sum(fn ($lane) => $lane->count()) }} tasks</span>
        </x-slot:actions>
    </x-page-header>

    <div class="app-filter-panel grid gap-2 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        <x-mary-choices-offline wire:model.live="projectId" :options="$projectOptions" single searchable clearable placeholder="All projects" />
        <x-mary-choices-offline wire:model.live="sprintId" :options="$sprintOptions" single searchable clearable placeholder="All sprints" />
        <x-mary-choices-offline wire:model.live="assigneeId" :options="$assigneeOptions" single searchable clearable placeholder="Anyone" option-sub-label="email" option-avatar="avatar" />
        <x-mary-choices-offline wire:model.live="epicId" :options="$epicOptions" single searchable clearable placeholder="All epics" />
        <x-mary-choices-offline wire:model.live="tagId" :options="$tagOptions" single searchable clearable placeholder="All tags" />
        <x-mary-select wire:model.live="statusFilter" placeholder="All statuses" placeholder-value="" icon-right="o-chevron-up-down">
            @foreach(\App\Models\Task::STATUSES as $status)
                <option value="{{ $status }}">{{ str($status)->replace('_', ' ')->title() }}</option>
            @endforeach
        </x-mary-select>
    </div>

    <div class="grid flex-1 gap-3 2xl:grid-cols-4 xl:grid-cols-2">
        @foreach(\App\Models\Task::STATUSES as $status)
            @php
                $label = ['todo' => 'To do', 'in_progress' => 'In progress', 'review' => 'Review', 'done' => 'Done'][$status];
                $tasks = $lanes[$status];
            @endphp
            <section
                class="gv-card flex min-h-[28rem] flex-col overflow-hidden"
                x-on:dragover.prevent
                x-on:drop="drop('{{ $status }}')"
            >
                <header class="flex items-center justify-between border-b border-[color:var(--gv-border)] px-3 py-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.16em] text-[color:var(--gv-amber)]">{{ strtoupper(str_replace('_', ' ', $status)) }}</span>
                        <span class="text-sm font-medium text-[color:var(--gv-fg1)]">{{ $label }}</span>
                    </div>
                    <span class="app-chip">{{ $tasks->count() }}</span>
                </header>

                <div class="flex flex-1 flex-col gap-2 p-2">
                    @forelse($tasks as $task)
                        @php($projectColor = $projectColors[$task->project_id] ?? null)
                        <article
                            draggable="true"
                            x-on:dragstart="start({{ $task->id }})"
                            x-on:dragend="end()"
                            wire:key="task-{{ $task->id }}"
                            x-on:click="open('{{ $task->key }}')"
                            @if($highlightId === $task->id)
                                x-init="$el.scrollIntoView({ behavior: 'smooth', block: 'center' }); $el.classList.add('ring-1','ring-amber-400'); setTimeout(() => $el.classList.remove('ring-1','ring-amber-400'), 2500)"
                            @endif
                            @if($projectColor)
                                style="border-left: 2px solid {{ $projectColor['stripe'] }};"
                            @endif
                            class="gv-card-muted gv-hover cursor-pointer p-3"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-mono text-[0.68rem] font-semibold tracking-wide text-[color:var(--gv-fg4)]">{{ $task->key }}</span>
                                <span @class([
                                    'rounded-sm px-1.5 py-0.5 text-xs font-semibold uppercase tracking-[0.14em]',
                                    'priority-crit' => $task->priority === 'crit',
                                    'priority-high' => $task->priority === 'high',
                                    'priority-med' => $task->priority === 'med',
                                    'priority-low' => $task->priority === 'low',
                                ])>{{ $task->priority }}</span>
                            </div>

                            <div class="mt-2 text-[0.95rem] font-medium leading-snug text-[color:var(--gv-fg0)]">{{ $task->title }}</div>

                            @if($task->description)
                                <div class="mt-1 line-clamp-2 text-xs leading-relaxed text-[color:var(--gv-fg4)]">{{ $task->description }}</div>
                            @endif

                            @if($task->blockedTasks->isNotEmpty())
                                @php($firstBlockedTask = $task->blockedTasks->first())
                                @php($remainingBlockedTasks = $task->blockedTasks->count() - 1)
                                <div class="mt-2 flex items-start gap-2 rounded-md border border-red-500/25 bg-red-500/10 px-2 py-1.5 text-xs text-red-100">
                                    <span class="inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-red-500/20 font-mono text-[0.65rem] font-bold text-red-200">!</span>
                                    <span>
                                        This task is blocking {{ $firstBlockedTask->title }}
                                        @if($remainingBlockedTasks > 0)
                                            <span class="text-red-200/80">+{{ $remainingBlockedTasks }} more</span>
                                        @endif
                                    </span>
                                </div>
                            @elseif($task->blockers->isNotEmpty())
                                @php($firstBlocker = $task->blockers->first())
                                @php($remainingBlockers = $task->blockers->count() - 1)
                                <div class="mt-2 flex items-start gap-2 rounded-md border border-amber-400/25 bg-amber-400/10 px-2 py-1.5 text-xs text-amber-100">
                                    <span class="inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-md bg-amber-400/20 font-mono text-[0.65rem] font-bold text-amber-200">!</span>
                                    <span>
                                        Blocked by
                                        <a
                                            href="{{ route('tasks.show', $firstBlocker->key) }}"
                                            wire:navigate
                                            x-on:click.stop
                                            class="underline underline-offset-2 transition hover:text-amber-50"
                                        >
                                            {{ $firstBlocker->title }}
                                        </a>
                                        @if($remainingBlockers > 0)
                                            <span class="text-amber-200/80">+{{ $remainingBlockers }} more</span>
                                        @endif
                                    </span>
                                </div>
                            @endif

                            <div class="mt-3 flex items-center justify-between gap-2">
                                @if($task->assignees->isNotEmpty())
                                    <div class="flex -space-x-1.5">
                                        @foreach($task->assignees->take(4) as $user)
                                            <a href="{{ route('users.show', $user) }}" wire:navigate x-on:click.stop title="{{ $user->name }}">
                                                <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="h-6 w-6 rounded-full border border-[color:var(--gv-bg0-h)] object-cover" />
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-[color:var(--gv-fg4)]">unassigned</span>
                                @endif

                                <div class="flex items-center gap-1.5">
                                    @if($task->project)
                                        @if($projectColor)
                                            <span class="rounded-sm px-1.5 py-0.5 font-mono text-[0.6rem] font-semibold" style="color: {{ $projectColor['chip_fg'] }};">{{ $task->project->name }}</span>
                                        @else
                                            <span class="text-xs text-[color:var(--gv-fg4)]">{{ $task->project->name }}</span>
                                        @endif
                                    @endif
                                    @if($task->story_points)
                                        <span class="rounded-sm border border-[color:var(--gv-border)] bg-[color:var(--gv-bg1)] px-1.5 py-0.5 text-xs text-[color:var(--gv-fg2)]">{{ $task->story_points }}</span>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="flex flex-1 items-center justify-center border border-dashed border-[color:var(--gv-border)] p-6 text-center text-sm text-[color:var(--gv-fg4)]">
                            empty
                        </div>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>

    @if($taskKey)
        <div class="fixed inset-0 z-40 bg-black/70" x-on:click="$wire.set('taskKey', null)"></div>
        <aside class="app-drawer fixed inset-y-0 right-0 z-50 w-full max-w-3xl">
            <div class="flex h-full flex-col" x-on:click.stop>
                <div class="flex items-center justify-between border-b border-[color:var(--gv-border)] px-4 py-3">
                    <div class="flex items-center gap-2">
                        <span class="text-[color:var(--gv-amber)]">»</span>
                        <span class="font-mono text-sm font-semibold text-[color:var(--gv-fg0)]">{{ $taskKey }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('tasks.show', $taskKey) }}" wire:navigate class="app-link text-sm">open full view</a>
                        <button type="button" x-on:click="$wire.set('taskKey', null)" class="btn btn-ghost btn-sm">close</button>
                    </div>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto px-4 py-4">
                    <livewire:task-detail :task-key="$taskKey" :embedded="true" :key="'drawer-task-'.$taskKey" />
                </div>
            </div>
        </aside>
    @endif
</div>
