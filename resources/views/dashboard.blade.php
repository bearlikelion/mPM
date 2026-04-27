<x-layouts.app>
    @php
        $user = \Illuminate\Support\Facades\Auth::user();
        $siteTenant = app(\App\Support\SiteTenant::class);
        $canManageOrg = $currentOrg && $user->can('update', $currentOrg);
        $projects = $siteTenant->projectsQuery($user, $currentOrg)->get();
        $selectedProjectId = request()->has('project')
            ? $siteTenant->validProjectId($user, request()->integer('project'), $currentOrg)
            : null;
        $selectedProject = $selectedProjectId ? $projects->firstWhere('id', $selectedProjectId) : null;
        $projectIds = $selectedProject
            ? collect([$selectedProjectId])
            : $projects->pluck('id');

        $myTasks = \App\Models\Task::with('project', 'sprint')
            ->withDependencyState()
            ->whereHas('assignees', fn ($q) => $q->whereKey($user->id))
            ->whereIn('project_id', $projectIds)
            ->where('status', '!=', 'done')
            ->orderByDependencyPriority()
            ->orderByRaw("array_position(array['crit','high','med','low']::text[], priority)")
            ->limit(6)
            ->get();

        $recentCompleted = \App\Models\Task::with('project')
            ->whereIn('project_id', $projectIds)
            ->where('status', 'done')
            ->latest('updated_at')
            ->limit(4)
            ->get();

        $recentComments = \App\Models\Comment::with('user', 'task.project')
            ->whereHas('task', fn ($q) => $q->whereIn('project_id', $projectIds))
            ->latest()
            ->limit(4)
            ->get();

        $activeEpics = \App\Models\Epic::with('project')
            ->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($q) => $q->where('status', 'done')])
            ->whereIn('project_id', $projectIds)
            ->whereNull('completed_at')
            ->orderBy('due_date')
            ->limit(4)
            ->get();
    @endphp

    <div class="flex flex-col gap-4">
        <x-page-header
            :title="$currentOrg?->name ?? config('app.name', 'mPM')"
            subtitle="Project pulse, activity, and milestones."
        >
            <x-slot:actions>
                @if($projects->isNotEmpty())
                    <form method="GET" action="{{ route('dashboard') }}" x-data class="min-w-64">
                        @if($selectedProject)
                            <div class="mb-1 flex items-center gap-2">
                                <img src="{{ $selectedProject->avatarUrl() }}" alt="" class="h-5 w-5 rounded-sm border border-[color:var(--gv-border)] bg-[color:var(--gv-bg1)] object-cover" />
                                <span class="truncate text-xs font-medium text-[color:var(--gv-fg2)]">{{ $selectedProject->key }} · {{ $selectedProject->name }}</span>
                            </div>
                        @endif
                        <select
                            name="project"
                            x-on:change="$el.form.submit()"
                            class="select select-sm w-full"
                        >
                            <option value="">◆ All projects</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" @selected($selectedProjectId === $project->id)>■ {{ $project->key }} · {{ $project->name }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
                <span class="app-chip">{{ $projectIds->count() }} {{ \Illuminate\Support\Str::plural('project', $projectIds->count()) }}</span>
                @if($selectedProject)
                    <span class="app-chip">{{ $selectedProject->name }}</span>
                @endif
                <span class="app-chip">{{ $user->formatLocalTime(now(), 'g:i A T') }}</span>
            </x-slot:actions>
        </x-page-header>

        <div class="grid grid-cols-2 gap-2 md:grid-cols-3">
            <div class="gv-card p-3">
                <div class="app-kpi-label">assigned</div>
                <div class="mt-1 app-kpi-value">{{ $myTasks->count() }}</div>
                <p class="mt-0.5 text-xs text-[color:var(--gv-fg4)]">open work</p>
            </div>
            <div class="gv-card p-3">
                <div class="app-kpi-label">completed</div>
                <div class="mt-1 app-kpi-value">{{ $recentCompleted->count() }}</div>
                <p class="mt-0.5 text-xs text-[color:var(--gv-fg4)]">recently closed</p>
            </div>
            <div class="gv-card p-3">
                <div class="app-kpi-label">comments</div>
                <div class="mt-1 app-kpi-value">{{ $recentComments->count() }}</div>
                <p class="mt-0.5 text-xs text-[color:var(--gv-fg4)]">fresh discussion</p>
            </div>
        </div>

        <div class="grid items-stretch gap-3 xl:grid-cols-[1.1fr_1fr_0.9fr]">
            <section class="gv-card flex h-full flex-col overflow-hidden">
                <div class="flex items-center justify-between border-b border-[color:var(--gv-border)] px-3 py-2">
                    <span class="text-sm font-semibold uppercase tracking-wide text-[color:var(--gv-amber)]">» my open tasks</span>
                    <span class="app-chip">{{ $myTasks->count() }}</span>
                </div>
                <ul class="flex-1 divide-y divide-[color:var(--gv-border)]">
                    @forelse($myTasks as $task)
                        <li class="flex flex-col gap-1 px-3 py-2.5">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex min-w-0 items-center gap-2">
                                    <a href="{{ route('tasks.show', $task->key) }}" wire:navigate class="font-mono text-[0.68rem] text-[color:var(--gv-fg4)] hover:text-[color:var(--gv-amber)]">{{ $task->key }}</a>
                                    @if($task->sprint)
                                        <span class="font-mono text-[0.68rem] text-[color:var(--gv-fg4)]">·</span>
                                        <a href="{{ route('kanban', ['project' => $task->project_id, 'sprint' => $task->sprint_id]) }}" wire:navigate class="truncate font-mono text-[0.68rem] text-[color:var(--gv-fg4)] hover:text-[color:var(--gv-amber)]">{{ $task->sprint->name }}</a>
                                    @endif
                                </div>
                                <span @class([
                                    'rounded-sm px-1.5 py-0.5 text-xs font-semibold uppercase tracking-[0.12em]',
                                    'priority-crit' => $task->priority === 'crit',
                                    'priority-high' => $task->priority === 'high',
                                    'priority-med' => $task->priority === 'med',
                                    'priority-low' => $task->priority === 'low',
                                ])>{{ $task->priority }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-1.5">
                                    @if($task->blockedTasks->isNotEmpty())
                                        @php($blockingNames = $task->blockedTasks->take(3)->pluck('title')->implode(', '))
                                        <span class="tooltip" data-tip="Blocking: {{ $blockingNames }}{{ $task->blockedTasks->count() > 3 ? ' +'.($task->blockedTasks->count() - 3).' more' : '' }}">
                                            <x-mary-icon name="o-no-symbol" class="h-4 w-4 text-[color:var(--gv-red)]" />
                                        </span>
                                    @elseif($task->blockers->isNotEmpty())
                                        @php($blockedByNames = $task->blockers->take(3)->pluck('title')->implode(', '))
                                        <span class="tooltip" data-tip="Blocked by: {{ $blockedByNames }}{{ $task->blockers->count() > 3 ? ' +'.($task->blockers->count() - 3).' more' : '' }}">
                                            <x-mary-icon name="o-lock-closed" class="h-4 w-4 text-[color:var(--gv-orange)]" />
                                        </span>
                                    @endif
                                    <a href="{{ route('tasks.show', $task->key) }}" wire:navigate class="min-w-0 truncate text-sm text-[color:var(--gv-fg1)] hover:text-[color:var(--gv-amber)]">{{ $task->title }}</a>
                                </div>
                                <a href="{{ route('kanban', ['project' => $task->project_id]) }}" wire:navigate class="shrink-0 font-mono text-[0.68rem] text-[color:var(--gv-fg4)] hover:text-[color:var(--gv-amber)]">{{ $task->project->name }}</a>
                            </div>
                        </li>
                    @empty
                        <li class="px-3 py-6 text-center text-sm text-[color:var(--gv-fg4)]">nothing assigned</li>
                    @endforelse
                </ul>
            </section>

            <section class="gv-card flex h-full flex-col overflow-hidden">
                <div class="border-b border-[color:var(--gv-border)] px-3 py-2">
                    <span class="text-sm font-semibold uppercase tracking-wide text-[color:var(--gv-amber)]">» recent activity</span>
                </div>
                <ul class="flex-1 divide-y divide-[color:var(--gv-border)]">
                    @foreach($recentComments as $comment)
                        <li class="px-3 py-2.5 text-sm">
                            <div class="flex flex-wrap items-center gap-x-1.5 text-[color:var(--gv-fg2)]">
                                @if($comment->user)
                                    <a href="{{ route('users.show', $comment->user) }}" wire:navigate class="font-medium text-[color:var(--gv-fg0)] hover:text-[color:var(--gv-amber)]">{{ $comment->user->name }}</a>
                                @else
                                    <span class="font-medium text-[color:var(--gv-fg0)]">Someone</span>
                                @endif
                                <span class="text-[color:var(--gv-fg4)]">→</span>
                                <a href="{{ route('tasks.show', $comment->task->key) }}" wire:navigate class="font-mono text-xs text-[color:var(--gv-fg4)] hover:text-[color:var(--gv-amber)]">{{ $comment->task->key }}</a>
                            </div>
                            <a href="{{ route('tasks.show', $comment->task->key) }}" wire:navigate class="mt-0.5 block line-clamp-2 text-xs text-[color:var(--gv-fg4)] hover:text-[color:var(--gv-amber)]">{{ \Illuminate\Support\Str::limit(trim(strip_tags($comment->body)), 160) }}</a>
                        </li>
                    @endforeach
                    @foreach($recentCompleted as $task)
                        <li class="flex items-center gap-2 px-3 py-2.5 text-sm">
                            <span class="rounded-sm px-1.5 py-0.5 font-mono text-[0.6rem] font-semibold uppercase tracking-[0.12em] status-active">done</span>
                            <a href="{{ route('tasks.show', $task->key) }}" wire:navigate class="font-mono text-xs text-[color:var(--gv-fg4)] hover:text-[color:var(--gv-amber)]">{{ $task->key }}</a>
                            <a href="{{ route('tasks.show', $task->key) }}" wire:navigate class="truncate text-[color:var(--gv-fg2)] hover:text-[color:var(--gv-amber)]">{{ $task->title }}</a>
                        </li>
                    @endforeach
                    @if($recentComments->isEmpty() && $recentCompleted->isEmpty())
                        <li class="px-3 py-6 text-center text-sm text-[color:var(--gv-fg4)]">no activity</li>
                    @endif
                </ul>
            </section>

            <section class="gv-card flex h-full flex-col overflow-hidden">
                <div class="flex items-center justify-between border-b border-[color:var(--gv-border)] px-3 py-2">
                    <span class="text-sm font-semibold uppercase tracking-wide text-[color:var(--gv-amber)]">» active epics</span>
                    <a href="{{ route('epics') }}" wire:navigate class="app-link text-sm">all →</a>
                </div>
                @if($activeEpics->isEmpty())
                    <div class="px-3 py-6 text-center text-sm text-[color:var(--gv-fg4)]">no active epics</div>
                @else
                    <ul class="flex-1 divide-y divide-[color:var(--gv-border)]">
                        @foreach($activeEpics as $epic)
                            @php($pct = $epic->tasks_count > 0 ? round(($epic->completed_tasks_count / $epic->tasks_count) * 100) : 0)
                            <li>
                                <a href="{{ route('epics.show', $epic) }}" wire:navigate class="flex flex-col gap-1.5 px-3 py-2.5 transition hover:bg-[color:var(--gv-bg1)]">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="truncate text-sm font-medium text-[color:var(--gv-fg0)]">{{ $epic->name }}</span>
                                        @if($epic->due_date)
                                            <span class="shrink-0 text-xs text-[color:var(--gv-fg4)]">{{ $epic->due_date->format('M j') }}</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center justify-between text-xs text-[color:var(--gv-fg4)]">
                                        <span>{{ $epic->project->name }}</span>
                                        <span>{{ $epic->completed_tasks_count }}/{{ $epic->tasks_count }} · {{ $pct }}%</span>
                                    </div>
                                    <div class="progress-track"><div class="progress-bar" style="width: {{ $pct }}%"></div></div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
</x-layouts.app>
