<x-layouts.app>
    @php
        $totalTasks = $epic->tasks_count;
        $completedTasks = $epic->completed_tasks_count;
        $progress = $totalTasks > 0 ? (int) round(($completedTasks / $totalTasks) * 100) : 0;
    @endphp

    <div class="flex flex-col gap-4">
        <x-page-header :title="$epic->name" :subtitle="$epic->description ? \Illuminate\Support\Str::limit(trim(strip_tags($epic->description)), 200) : 'Delivery arc for '.$epic->project->name.'.'">
            <x-slot:actions>
                <a href="{{ route('epics') }}" wire:navigate class="app-link text-sm">all epics</a>
                <a href="{{ route('kanban', ['project' => $epic->project_id, 'epic' => $epic->id]) }}" wire:navigate class="btn btn-sm">open in kanban</a>
            </x-slot:actions>
        </x-page-header>

        <section class="gv-card flex flex-col gap-4 p-4 md:flex-row md:items-center">
            <div class="flex items-center gap-3">
                <img src="{{ $epic->avatarUrl() }}" alt="" class="h-12 w-12 rounded-sm border border-[color:var(--gv-border)] bg-[color:var(--gv-bg1)] object-cover" />
                <div class="min-w-0">
                    <a href="{{ route('kanban', ['project' => $epic->project_id]) }}" wire:navigate class="block truncate text-sm font-semibold text-[color:var(--gv-fg0)] hover:text-[color:var(--gv-amber)]">{{ $epic->project->name }}</a>
                    <div class="text-xs text-[color:var(--gv-fg4)]">
                        @if($epic->due_date)
                            due {{ $epic->due_date->format('M j, Y') }}
                        @else
                            no due date
                        @endif
                        @if($epic->completed_at)
                            · <span class="text-[color:var(--gv-aqua)]">completed {{ $epic->completed_at->format('M j, Y') }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="md:ml-auto md:w-80">
                <div class="flex items-center justify-between text-xs text-[color:var(--gv-fg4)]">
                    <span>{{ $completedTasks }}/{{ $totalTasks }} tasks</span>
                    <span>{{ $progress }}%</span>
                </div>
                <div class="progress-track mt-1"><div class="progress-bar" style="width: {{ $progress }}%"></div></div>
            </div>
        </section>

        <section class="flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-[color:var(--gv-amber)]">» sprint history</h2>
                <span class="app-chip">{{ $sprints->count() }} {{ \Illuminate\Support\Str::plural('sprint', $sprints->count()) }}</span>
            </div>

            @if($sprints->isEmpty() && $unscheduledTasks->isEmpty())
                <div class="gv-card px-4 py-10 text-center text-sm text-[color:var(--gv-fg4)]">no sprints have included tasks from this epic yet</div>
            @else
                <ol class="relative ml-2 border-l border-[color:var(--gv-border)] pl-6">
                    @foreach($sprints as $sprint)
                        @php
                            $startDate = $sprint->started_at ?? $sprint->starts_at;
                            $endDate = $sprint->ended_at ?? $sprint->ends_at;
                            $isActive = $sprint->started_at && ! $sprint->ended_at;
                            $isPlanned = ! $sprint->started_at;
                            $duration = null;
                            if ($startDate && $endDate) {
                                $days = (int) floor($startDate->diffInDays($endDate));
                                $duration = $days === 0 ? 'same day' : $days.' '.\Illuminate\Support\Str::plural('day', $days);
                            } elseif ($startDate && $isActive) {
                                $days = (int) floor($startDate->diffInDays(now()));
                                $duration = $days === 0 ? 'today' : $days.' '.\Illuminate\Support\Str::plural('day', $days).' in';
                            }
                            $sprintProgress = $sprint->epic_tasks_count > 0
                                ? (int) round(($sprint->epic_completed_count / $sprint->epic_tasks_count) * 100)
                                : 0;
                        @endphp
                        <li wire:key="sprint-{{ $sprint->id }}" class="relative mb-4">
                            <span @class([
                                'absolute -left-[33px] top-2 h-3 w-3 rounded-full border-2 border-[color:var(--gv-bg0-h)]',
                                'bg-[color:var(--gv-aqua)]' => $isActive,
                                'bg-[color:var(--gv-amber)]' => $sprint->ended_at,
                                'bg-[color:var(--gv-fg4)]' => $isPlanned,
                            ])></span>

                            <div class="gv-card overflow-hidden">
                                <header class="flex flex-wrap items-center justify-between gap-2 border-b border-[color:var(--gv-border)] px-3 py-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('kanban', ['project' => $sprint->project_id, 'sprint' => $sprint->id]) }}" wire:navigate class="font-mono text-sm font-semibold text-[color:var(--gv-fg0)] transition hover:text-[color:var(--gv-amber)] hover:underline">
                                            {{ $sprint->name }}
                                        </a>
                                        <span @class([
                                            'rounded-sm px-1.5 py-0.5 font-mono text-[0.6rem] font-semibold uppercase tracking-[0.14em]',
                                            'status-active' => $isActive,
                                            'status-ended' => $sprint->ended_at,
                                            'status-planned' => $isPlanned,
                                        ])>
                                            {{ $isActive ? 'active' : ($sprint->ended_at ? 'ended' : 'planned') }}
                                        </span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-3 text-xs text-[color:var(--gv-fg4)]">
                                        @if($startDate)
                                            <span class="font-mono">{{ $startDate->format('M j') }}{{ $endDate ? ' → '.$endDate->format('M j, Y') : '' }}</span>
                                        @endif
                                        @if($duration)
                                            <span class="font-mono">{{ $duration }}</span>
                                        @endif
                                        <span>{{ $sprint->epic_completed_count }}/{{ $sprint->epic_tasks_count }} done · {{ $sprintProgress }}%</span>
                                    </div>
                                </header>

                                <ul class="divide-y divide-[color:var(--gv-border)]">
                                    @foreach($sprint->tasks as $task)
                                        <li class="flex items-center gap-2 px-3 py-2 text-sm">
                                            <span @class([
                                                'rounded-sm px-1.5 py-0.5 font-mono text-[0.6rem] font-semibold uppercase tracking-[0.12em]',
                                                'status-active' => $task->status === 'done',
                                                'status-planned' => $task->status === 'in_progress' || $task->status === 'review',
                                                'status-ended' => $task->status === 'todo',
                                            ])>{{ str_replace('_', ' ', $task->status) }}</span>
                                            <a href="{{ route('tasks.show', $task->key) }}" wire:navigate class="font-mono text-xs text-[color:var(--gv-fg4)] hover:text-[color:var(--gv-amber)]">{{ $task->key }}</a>
                                            <a href="{{ route('tasks.show', $task->key) }}" wire:navigate class="min-w-0 flex-1 truncate text-[color:var(--gv-fg1)] hover:text-[color:var(--gv-amber)]">{{ $task->title }}</a>
                                            @if($task->assignees->isNotEmpty())
                                                <div class="flex -space-x-1.5">
                                                    @foreach($task->assignees->take(3) as $user)
                                                        <a href="{{ route('users.show', $user) }}" wire:navigate title="{{ $user->name }}">
                                                            <img src="{{ $user->avatarUrl() }}" alt="" class="h-5 w-5 rounded-full border border-[color:var(--gv-bg0-h)] object-cover" />
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </li>
                    @endforeach

                    @if($unscheduledTasks->isNotEmpty())
                        <li class="relative">
                            <span class="absolute -left-[33px] top-2 h-3 w-3 rounded-full border-2 border-[color:var(--gv-bg0-h)] bg-[color:var(--gv-fg4)]"></span>

                            <div class="gv-card overflow-hidden">
                                <header class="flex items-center justify-between border-b border-[color:var(--gv-border)] px-3 py-2">
                                    <span class="text-sm font-semibold uppercase tracking-wide text-[color:var(--gv-fg2)]">unscheduled</span>
                                    <span class="text-xs text-[color:var(--gv-fg4)]">{{ $unscheduledTasks->count() }} {{ \Illuminate\Support\Str::plural('task', $unscheduledTasks->count()) }} not in any sprint</span>
                                </header>

                                <ul class="divide-y divide-[color:var(--gv-border)]">
                                    @foreach($unscheduledTasks as $task)
                                        <li class="flex items-center gap-2 px-3 py-2 text-sm">
                                            <span @class([
                                                'rounded-sm px-1.5 py-0.5 font-mono text-[0.6rem] font-semibold uppercase tracking-[0.12em]',
                                                'status-active' => $task->status === 'done',
                                                'status-planned' => $task->status === 'in_progress' || $task->status === 'review',
                                                'status-ended' => $task->status === 'todo',
                                            ])>{{ str_replace('_', ' ', $task->status) }}</span>
                                            <a href="{{ route('tasks.show', $task->key) }}" wire:navigate class="font-mono text-xs text-[color:var(--gv-fg4)] hover:text-[color:var(--gv-amber)]">{{ $task->key }}</a>
                                            <a href="{{ route('tasks.show', $task->key) }}" wire:navigate class="min-w-0 flex-1 truncate text-[color:var(--gv-fg1)] hover:text-[color:var(--gv-amber)]">{{ $task->title }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </li>
                    @endif
                </ol>
            @endif
        </section>
    </div>
</x-layouts.app>
