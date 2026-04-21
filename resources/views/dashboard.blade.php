<x-layouts.app>
    @php
        $user = \Illuminate\Support\Facades\Auth::user();
        $currentOrg = $user->defaultOrganization ?? $user->organizations->first();
        $orgIds = $user->organizations()->pluck('organizations.id');
        $projectIds = \App\Models\Project::whereIn('organization_id', $orgIds)->pluck('id');

        $myTasks = \App\Models\Task::with('project', 'sprint')
            ->whereHas('assignees', fn ($q) => $q->whereKey($user->id))
            ->whereIn('project_id', $projectIds)
            ->where('status', '!=', 'done')
            ->orderByRaw("array_position(array['crit','high','med','low']::text[], priority)")
            ->limit(10)
            ->get();

        $recentCompleted = \App\Models\Task::with('project')
            ->whereIn('project_id', $projectIds)
            ->where('status', 'done')
            ->latest('updated_at')
            ->limit(5)
            ->get();

        $recentComments = \App\Models\Comment::with('user', 'task.project')
            ->whereHas('task', fn ($q) => $q->whereIn('project_id', $projectIds))
            ->latest()
            ->limit(5)
            ->get();

        $activeEpics = \App\Models\Epic::with('project')
            ->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($q) => $q->where('status', 'done')])
            ->whereIn('project_id', $projectIds)
            ->whereNull('completed_at')
            ->orderBy('due_date')
            ->limit(6)
            ->get();
    @endphp

    <section class="app-panel app-hero overflow-hidden px-5 py-6 sm:px-7 sm:py-7">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="space-y-4">
                <div class="app-eyebrow">Execution Cockpit</div>
                <div>
                    <h1 class="app-title">
                        {{ $currentOrg?->name ?? config('app.name', 'mPM') }}
                    </h1>
                    <p class="mt-3 app-subtitle">
                        Track work across projects, keep epics moving, and keep the whole organization aligned from backlog to done.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="app-chip">{{ $user->formatLocalTime(now(), 'M d, Y g:i A T') }}</span>
                    <span class="app-chip">{{ $user->organizations->count() }} {{ \Illuminate\Support\Str::plural('org', $user->organizations->count()) }}</span>
                    <span class="app-chip">{{ $projectIds->count() }} active {{ \Illuminate\Support\Str::plural('project', $projectIds->count()) }}</span>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-3 lg:w-[32rem]">
                <a href="{{ route('kanban') }}" wire:navigate class="app-panel-muted rounded-2xl px-4 py-4 transition hover:border-amber-400/40 hover:bg-neutral-950/60">
                    <div class="app-eyebrow">Flow</div>
                    <div class="mt-2 text-lg font-semibold text-neutral-50">Kanban board</div>
                    <div class="mt-1 text-sm text-neutral-400">See work in motion.</div>
                </a>
                <a href="{{ route('backlog') }}" wire:navigate class="app-panel-muted rounded-2xl px-4 py-4 transition hover:border-amber-400/40 hover:bg-neutral-950/60">
                    <div class="app-eyebrow">Plan</div>
                    <div class="mt-2 text-lg font-semibold text-neutral-50">Backlog</div>
                    <div class="mt-1 text-sm text-neutral-400">Shape upcoming work.</div>
                </a>
                <a href="{{ route('epics') }}" wire:navigate class="app-panel-muted rounded-2xl px-4 py-4 transition hover:border-amber-400/40 hover:bg-neutral-950/60">
                    <div class="app-eyebrow">Milestones</div>
                    <div class="mt-2 text-lg font-semibold text-neutral-50">Epics</div>
                    <div class="mt-1 text-sm text-neutral-400">Follow delivery progress.</div>
                </a>
            </div>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-3">
        <div class="app-panel app-kpi">
            <div class="relative space-y-3">
                <div class="app-kpi-label">Assigned To Me</div>
                <div class="app-kpi-value">{{ $myTasks->count() }}</div>
                <p class="text-sm text-neutral-400">Open tasks waiting on your attention across all visible projects.</p>
            </div>
        </div>
        <div class="app-panel app-kpi">
            <div class="relative space-y-3">
                <div class="app-kpi-label">Recently Completed</div>
                <div class="app-kpi-value">{{ $recentCompleted->count() }}</div>
                <p class="text-sm text-neutral-400">Tasks closed lately in this workspace, useful for pulse-checking momentum.</p>
            </div>
        </div>
        <div class="app-panel app-kpi">
            <div class="relative space-y-3">
                <div class="app-kpi-label">Recent Comments</div>
                <div class="app-kpi-value">{{ $recentComments->count() }}</div>
                <p class="text-sm text-neutral-400">Fresh discussion across tasks, reviews, and handoffs.</p>
            </div>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        <div class="app-panel overflow-hidden">
            <div class="flex items-center justify-between border-b border-neutral-700/60 px-5 py-4">
                <div>
                    <div class="app-eyebrow">Your Queue</div>
                    <h2 class="mt-2 text-2xl font-semibold tracking-tight text-neutral-50">My open tasks</h2>
                </div>
                <span class="app-chip">{{ $myTasks->count() }} open</span>
            </div>
            <ul class="divide-y divide-neutral-700/60">
                @forelse($myTasks as $task)
                    <li class="flex flex-col gap-3 px-5 py-4 md:flex-row md:items-center md:justify-between">
                        <div class="min-w-0 space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="app-chip">{{ $task->key }}</span>
                                @if($task->sprint)
                                    <span class="text-xs text-neutral-500">{{ $task->sprint->name }}</span>
                                @endif
                            </div>
                            <div class="truncate text-lg font-medium text-neutral-50">{{ $task->title }}</div>
                            <div class="text-sm text-neutral-400">{{ $task->project->name }}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span @class([
                                'rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em]',
                                'priority-crit' => $task->priority === 'crit',
                                'priority-high' => $task->priority === 'high',
                                'priority-med' => $task->priority === 'med',
                                'priority-low' => $task->priority === 'low',
                            ])>{{ $task->priority }}</span>
                            <a href="{{ route('tasks.show', $task->key) }}" wire:navigate class="app-link text-sm">Open</a>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-neutral-500">Nothing assigned. Nice.</li>
                @endforelse
            </ul>
        </div>

        <div class="app-panel overflow-hidden">
            <div class="border-b border-neutral-700/60 px-5 py-4">
                <div class="app-eyebrow">Pulse</div>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-neutral-50">Recent activity</h2>
            </div>
            <ul class="divide-y divide-neutral-700/60">
                @foreach($recentComments as $comment)
                    <li class="px-5 py-4 text-sm">
                        <div class="text-neutral-200">
                            <span class="font-medium text-neutral-50">{{ $comment->user?->name ?? 'Someone' }}</span>
                            commented on
                            <span class="font-mono text-xs text-neutral-400">{{ $comment->task->key }}</span>
                        </div>
                        <div class="mt-1 line-clamp-2 text-sm text-neutral-500">{{ $comment->body }}</div>
                    </li>
                @endforeach
                @foreach($recentCompleted as $task)
                    <li class="px-5 py-4 text-sm">
                        <div class="flex items-center gap-2 text-neutral-200">
                            <span class="rounded-full status-active px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.18em]">Done</span>
                            <span class="font-mono text-xs text-neutral-400">{{ $task->key }}</span>
                            <span class="truncate">{{ $task->title }}</span>
                        </div>
                    </li>
                @endforeach
                @if($recentComments->isEmpty() && $recentCompleted->isEmpty())
                    <li class="px-5 py-10 text-center text-sm text-neutral-500">No recent activity.</li>
                @endif
            </ul>
        </div>
    </section>

    <section class="app-panel px-5 py-5 sm:px-6">
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="app-eyebrow">Milestones</div>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-neutral-50">Active epics</h2>
            </div>
            <a href="{{ route('epics') }}" wire:navigate class="app-link text-sm">View all epics</a>
        </div>
        @if($activeEpics->isEmpty())
            <div class="rounded-2xl border border-dashed border-neutral-700/80 px-4 py-10 text-center text-sm text-neutral-500">No active epics.</div>
        @else
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($activeEpics as $epic)
                    @php $pct = $epic->tasks_count > 0 ? round(($epic->completed_tasks_count / $epic->tasks_count) * 100) : 0; @endphp
                    <a href="{{ route('kanban', ['project' => $epic->project_id, 'epic' => $epic->id]) }}" wire:navigate class="app-panel-muted flex flex-col gap-4 rounded-2xl p-4 transition hover:border-amber-400/40 hover:bg-neutral-950/60">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <img src="{{ $epic->avatarUrl() }}" alt="" class="h-10 w-10 rounded-2xl border border-neutral-700/70 bg-neutral-900 object-cover" />
                                <div>
                                    <div class="font-semibold text-neutral-50">{{ $epic->name }}</div>
                                    <div class="text-sm text-neutral-400">{{ $epic->project->name }}</div>
                                </div>
                            </div>
                            @if($epic->due_date)
                                <span class="app-chip">{{ $epic->due_date->format('M j') }}</span>
                            @endif
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-xs text-neutral-500">
                                <span>{{ $epic->completed_tasks_count }}/{{ $epic->tasks_count }} tasks</span>
                                <span>{{ $pct }}%</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-bar" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <section class="app-panel px-5 py-5 sm:px-6">
        <div class="mb-5">
            <div class="app-eyebrow">Organizations</div>
            <h2 class="mt-2 text-2xl font-semibold tracking-tight text-neutral-50">Your organizations</h2>
        </div>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($user->organizations as $org)
                <a href="/app/{{ $org->slug }}" class="app-panel-muted flex items-center gap-4 rounded-2xl p-4 transition hover:border-amber-400/40 hover:bg-neutral-950/60">
                    <img src="{{ $org->logoUrl() }}" alt="" class="h-12 w-12 rounded-2xl border border-neutral-700/70 bg-neutral-900 object-cover" />
                    <div class="min-w-0 flex-1">
                        <div class="truncate font-semibold text-neutral-50">{{ $org->name }}</div>
                        <div class="text-sm text-neutral-400">{{ $org->projects()->count() }} {{ \Illuminate\Support\Str::plural('project', $org->projects()->count()) }}</div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
</x-layouts.app>
