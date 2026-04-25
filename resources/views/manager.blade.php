<x-layouts.app>
    @php
        $siteTenant = app(\App\Support\SiteTenant::class);
        $projects = $siteTenant->projectsQuery(auth()->user(), $currentOrg)->get();
        $selectedProjectId = request()->has('project')
            ? $siteTenant->validProjectId(auth()->user(), request()->integer('project'), $currentOrg)
            : null;
        $selectedProject = $selectedProjectId ? $projects->firstWhere('id', $selectedProjectId) : null;
        $projectIds = $selectedProject
            ? collect([$selectedProjectId])
            : $projects->pluck('id');

        $teamMembers = $currentOrg->users()
            ->select('users.*', 'organization_user.role', 'organization_user.joined_at')
            ->withCount([
                'assignedTasks as open_tasks_count' => fn ($query) => $query
                    ->whereIn('tasks.project_id', $projectIds)
                    ->where('tasks.status', '!=', 'done'),
                'assignedTasks as completed_tasks_count' => fn ($query) => $query
                    ->whereIn('tasks.project_id', $projectIds)
                    ->where('tasks.status', 'done')
                    ->where('tasks.updated_at', '>=', now()->subDays(30)),
                'comments as recent_comments_count' => fn ($query) => $query
                    ->where('comments.created_at', '>=', now()->subDays(7))
                    ->whereHas('task', fn ($taskQuery) => $taskQuery->whereIn('tasks.project_id', $projectIds)),
            ])
            ->orderByDesc('completed_tasks_count')
            ->orderByDesc('open_tasks_count')
            ->orderBy('name')
            ->get();

        $orgTasks = \App\Models\Task::query()->whereIn('project_id', $projectIds);
        $totalOpenTasks = (clone $orgTasks)->where('status', '!=', 'done')->count();
        $completedLastThirtyDays = (clone $orgTasks)
            ->where('status', 'done')
            ->where('updated_at', '>=', now()->subDays(30))
            ->count();
        $overdueTasks = (clone $orgTasks)
            ->where('status', '!=', 'done')
            ->whereDate('due_date', '<', now()->toDateString())
            ->count();
        $commentsLastSevenDays = \App\Models\Comment::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->whereHas('task', fn ($query) => $query->whereIn('project_id', $projectIds))
            ->count();

        $projectAnalytics = \App\Models\Project::query()
            ->where('organization_id', $currentOrg->id)
            ->when($selectedProjectId, fn ($query) => $query->whereKey($selectedProjectId))
            ->withCount([
                'tasks',
                'tasks as open_tasks_count' => fn ($query) => $query->where('status', '!=', 'done'),
                'tasks as completed_tasks_count' => fn ($query) => $query
                    ->where('status', 'done')
                    ->where('updated_at', '>=', now()->subDays(30)),
            ])
            ->orderByDesc('open_tasks_count')
            ->orderBy('name')
            ->limit(5)
            ->get();
    @endphp

    <div class="flex flex-col gap-4">
        <x-page-header :title="$currentOrg->name" subtitle="KPI tracking, delivery signals, and contributor load.">
            <x-slot:actions>
                @if($projects->isNotEmpty())
                    <form method="GET" action="{{ route('manager') }}" x-data class="min-w-52">
                        <select
                            name="project"
                            x-on:change="$el.form.submit()"
                            class="select select-sm w-full"
                        >
                            <option value="">All projects</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" @selected($selectedProjectId === $project->id)>{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
                <span class="app-chip">{{ $teamMembers->count() }} members</span>
                @if($selectedProject)
                    <span class="app-chip">{{ $selectedProject->name }}</span>
                @endif
                <span class="app-chip">{{ $currentOrg->preferredTimezone() }}</span>
                <a href="{{ route('kanban', array_filter(['project' => $selectedProjectId])) }}" wire:navigate class="btn btn-sm">open board</a>
            </x-slot:actions>
        </x-page-header>

        <div class="grid gap-2 md:grid-cols-4">
            <div class="gv-card p-3">
                <div class="app-kpi-label">open work</div>
                <div class="mt-1 app-kpi-value">{{ $totalOpenTasks }}</div>
            </div>
            <div class="gv-card p-3">
                <div class="app-kpi-label">closed 30d</div>
                <div class="mt-1 app-kpi-value">{{ $completedLastThirtyDays }}</div>
            </div>
            <div class="gv-card p-3">
                <div class="app-kpi-label">comments 7d</div>
                <div class="mt-1 app-kpi-value">{{ $commentsLastSevenDays }}</div>
            </div>
            <div class="gv-card p-3">
                <div class="app-kpi-label">overdue</div>
                <div class="mt-1 app-kpi-value">{{ $overdueTasks }}</div>
            </div>
        </div>

        <div class="grid gap-3 xl:grid-cols-[1.4fr_1fr]">
            <section class="gv-card overflow-hidden">
                <div class="border-b border-[color:var(--gv-border)] px-3 py-2">
                    <span class="text-sm font-semibold uppercase tracking-wide text-[color:var(--gv-amber)]">» everyone's workload</span>
                </div>

                @if($teamMembers->isEmpty())
                    <div class="px-3 py-6 text-center text-sm text-[color:var(--gv-fg4)]">no team members</div>
                @else
                    <div class="divide-y divide-[color:var(--gv-border)]">
                        @foreach($teamMembers as $member)
                            @php
                                $total = $member->open_tasks_count + $member->completed_tasks_count;
                                $share = $total > 0 ? (int) round(($member->completed_tasks_count / $total) * 100) : 0;
                            @endphp
                            <div class="grid gap-3 px-3 py-3 lg:grid-cols-[minmax(0,1.2fr)_repeat(4,minmax(0,0.5fr))] lg:items-center">
                                <div class="flex min-w-0 items-center gap-3">
                                    <a href="{{ route('users.show', $member) }}" wire:navigate>
                                        <img src="{{ $member->avatarUrl() }}" alt="{{ $member->name }}" class="h-9 w-9 rounded-sm border border-[color:var(--gv-border)] object-cover" />
                                    </a>
                                    <div class="min-w-0">
                                        <a href="{{ route('users.show', $member) }}" wire:navigate class="block truncate text-sm font-semibold text-[color:var(--gv-fg0)] hover:text-[color:var(--gv-amber)]">{{ $member->name }}</a>
                                        <div class="text-xs text-[color:var(--gv-fg4)]">{{ $member->role === 'org_admin' ? 'admin' : 'member' }} · {{ $member->preferredTimezone() }}</div>
                                    </div>
                                </div>
                                <a href="{{ route('kanban', array_filter(['project' => $selectedProjectId, 'assignee' => $member->id])) }}" wire:navigate class="block font-mono">
                                    <div class="app-kpi-label">open</div>
                                    <div class="text-base font-semibold text-[color:var(--gv-fg0)]">{{ $member->open_tasks_count }}</div>
                                </a>
                                <a href="{{ route('kanban', array_filter(['project' => $selectedProjectId, 'assignee' => $member->id, 'status' => 'done'])) }}" wire:navigate class="block font-mono">
                                    <div class="app-kpi-label">done 30d</div>
                                    <div class="text-base font-semibold text-[color:var(--gv-fg0)]">{{ $member->completed_tasks_count }}</div>
                                </a>
                                <div class="font-mono">
                                    <div class="app-kpi-label">cmts 7d</div>
                                    <div class="text-base font-semibold text-[color:var(--gv-fg0)]">{{ $member->recent_comments_count }}</div>
                                </div>
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between text-xs text-[color:var(--gv-fg4)]">
                                        <span>{{ $share }}% done</span>
                                        <span>{{ $total }}</span>
                                    </div>
                                    <div class="progress-track"><div class="progress-bar" style="width: {{ $share }}%"></div></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="gv-card overflow-hidden">
                <div class="border-b border-[color:var(--gv-border)] px-3 py-2">
                    <span class="text-sm font-semibold uppercase tracking-wide text-[color:var(--gv-amber)]">» load by project</span>
                </div>
                @if($projectAnalytics->isEmpty())
                    <div class="px-3 py-6 text-center text-sm text-[color:var(--gv-fg4)]">no projects</div>
                @else
                    <ul class="divide-y divide-[color:var(--gv-border)]">
                        @foreach($projectAnalytics as $project)
                            <li>
                                <a href="{{ route('kanban', ['project' => $project->id]) }}" wire:navigate class="flex flex-col gap-1 px-3 py-2.5 transition hover:bg-[color:var(--gv-bg1)]">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="truncate text-sm font-semibold text-[color:var(--gv-fg0)]">{{ $project->name }}</span>
                                        <span class="app-chip">{{ $project->open_tasks_count }} open</span>
                                    </div>
                                    <div class="flex items-center justify-between text-xs text-[color:var(--gv-fg4)]">
                                        <span>{{ $project->key }}</span>
                                        <span>{{ $project->completed_tasks_count }} closed 30d · {{ $project->tasks_count }} total</span>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
</x-layouts.app>
