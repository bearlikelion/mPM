<x-layouts.app>
    @php
        $projectIds = $currentOrg->projects()->pluck('id');

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
        $activeContributors = $teamMembers
            ->filter(fn ($member) => $member->open_tasks_count > 0 || $member->completed_tasks_count > 0 || $member->recent_comments_count > 0)
            ->count();

        $projectAnalytics = \App\Models\Project::query()
            ->where('organization_id', $currentOrg->id)
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

    <div class="grid gap-4 xl:min-h-[calc(100vh-5.5rem)] xl:grid-cols-[1.15fr_0.85fr] xl:grid-rows-[auto_1fr]">
        <section class="app-panel app-hero overflow-hidden px-5 py-5 sm:px-6">
            <div class="flex h-full flex-col justify-between gap-5">
                <div class="space-y-3">
                    <div class="app-eyebrow">Org Manager</div>
                    <div>
                        <h1 class="app-title">{{ $currentOrg->name }} manager desk</h1>
                        <p class="mt-2 max-w-3xl text-base leading-7 text-neutral-300">
                            KPI tracking, delivery signals, and contributor load across the organization.
                        </p>
                    </div>
                </div>

                <div class="grid gap-2 sm:grid-cols-4">
                    <div class="app-panel-muted rounded-2xl px-3 py-3">
                        <div class="app-eyebrow">Team</div>
                        <div class="mt-1 text-base font-semibold text-neutral-50">{{ $teamMembers->count() }} members</div>
                    </div>
                    <div class="app-panel-muted rounded-2xl px-3 py-3">
                        <div class="app-eyebrow">Contributors</div>
                        <div class="mt-1 text-base font-semibold text-neutral-50">{{ $activeContributors }} active this week</div>
                    </div>
                    <div class="app-panel-muted rounded-2xl px-3 py-3">
                        <div class="app-eyebrow">Timezone</div>
                        <div class="mt-1 text-base font-semibold text-neutral-50">{{ $currentOrg->preferredTimezone() }}</div>
                    </div>
                    <a href="{{ route('kanban') }}" wire:navigate class="app-panel-muted rounded-2xl px-3 py-3 transition hover:border-amber-400/40 hover:bg-neutral-950/60">
                        <div class="app-eyebrow">Inspect</div>
                        <div class="mt-1 text-base font-semibold text-neutral-50">Open board</div>
                    </a>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-2">
            <div class="app-panel app-kpi">
                <div class="relative space-y-2">
                    <div class="app-kpi-label">Open Work</div>
                    <div class="app-kpi-value">{{ $totalOpenTasks }}</div>
                    <p class="text-sm text-neutral-400">Assigned and unassigned tasks still in flight.</p>
                </div>
            </div>
            <div class="app-panel app-kpi">
                <div class="relative space-y-2">
                    <div class="app-kpi-label">Closed 30d</div>
                    <div class="app-kpi-value">{{ $completedLastThirtyDays }}</div>
                    <p class="text-sm text-neutral-400">Tasks completed in the last 30 days.</p>
                </div>
            </div>
            <div class="app-panel app-kpi">
                <div class="relative space-y-2">
                    <div class="app-kpi-label">Comments 7d</div>
                    <div class="app-kpi-value">{{ $commentsLastSevenDays }}</div>
                    <p class="text-sm text-neutral-400">Discussion volume across the org this week.</p>
                </div>
            </div>
            <div class="app-panel app-kpi">
                <div class="relative space-y-2">
                    <div class="app-kpi-label">Overdue</div>
                    <div class="app-kpi-value">{{ $overdueTasks }}</div>
                    <p class="text-sm text-neutral-400">Open tasks past their due date.</p>
                </div>
            </div>
        </section>

        <section class="grid gap-4 xl:col-span-2 xl:grid-cols-[1.2fr_0.8fr]">
            <div class="app-panel overflow-hidden xl:min-h-0">
                <div class="border-b border-neutral-700/60 px-4 py-3">
                    <div class="app-eyebrow">Team Analytics</div>
                    <h2 class="mt-1 text-xl font-semibold tracking-tight text-neutral-50">Everyone's workload</h2>
                </div>

                @if($teamMembers->isEmpty())
                    <div class="px-4 py-8 text-center text-sm text-neutral-500">No team members found for this organization.</div>
                @else
                    <div class="divide-y divide-neutral-700/60">
                        @foreach($teamMembers as $member)
                            @php
                                $throughputTotal = $member->open_tasks_count + $member->completed_tasks_count;
                                $completionShare = $throughputTotal > 0
                                    ? (int) round(($member->completed_tasks_count / $throughputTotal) * 100)
                                    : 0;
                            @endphp
                            <div class="grid gap-4 px-4 py-4 lg:grid-cols-[minmax(0,1.2fr)_repeat(4,minmax(0,0.5fr))] lg:items-center">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $member->avatarUrl() }}" alt="" class="h-11 w-11 rounded-2xl border border-neutral-700/70 bg-neutral-900 object-cover" />
                                        <div class="min-w-0">
                                            <a
                                                href="{{ route('kanban', ['assignee' => $member->id]) }}"
                                                wire:navigate
                                                class="block truncate font-semibold text-neutral-50 transition hover:text-amber-300"
                                            >{{ $member->name }}</a>
                                            <div class="text-sm text-neutral-400">
                                                {{ $member->role === 'org_admin' ? 'Org admin' : 'Member' }} · {{ $member->preferredTimezone() }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <div class="app-eyebrow">Open</div>
                                    <div class="mt-1 text-lg font-semibold text-neutral-50">{{ $member->open_tasks_count }}</div>
                                </div>

                                <div>
                                    <div class="app-eyebrow">Done 30d</div>
                                    <div class="mt-1 text-lg font-semibold text-neutral-50">{{ $member->completed_tasks_count }}</div>
                                </div>

                                <div>
                                    <div class="app-eyebrow">Comments 7d</div>
                                    <div class="mt-1 text-lg font-semibold text-neutral-50">{{ $member->recent_comments_count }}</div>
                                </div>

                                <div class="space-y-2">
                                    <div class="app-eyebrow">Delivery Mix</div>
                                    <div class="flex items-center justify-between text-xs text-neutral-500">
                                        <span>{{ $completionShare }}% done</span>
                                        <span>{{ $throughputTotal }} tracked</span>
                                    </div>
                                    <div class="progress-track">
                                        <div class="progress-bar" style="width: {{ $completionShare }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="flex flex-col gap-4 xl:min-h-0">
                <section class="app-panel px-4 py-4">
                    <div class="mb-4">
                        <div class="app-eyebrow">Projects</div>
                        <h2 class="mt-1 text-xl font-semibold tracking-tight text-neutral-50">Load by project</h2>
                    </div>

                    @if($projectAnalytics->isEmpty())
                        <div class="rounded-2xl border border-dashed border-neutral-700/80 px-4 py-8 text-center text-sm text-neutral-500">No projects yet.</div>
                    @else
                        <div class="grid gap-3">
                            @foreach($projectAnalytics as $project)
                                <a href="{{ route('kanban', ['project' => $project->id]) }}" wire:navigate class="app-panel-muted rounded-2xl p-3 transition hover:border-amber-400/40 hover:bg-neutral-950/60">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="truncate font-semibold text-neutral-50">{{ $project->name }}</div>
                                            <div class="text-sm text-neutral-400">{{ $project->key }}</div>
                                        </div>
                                        <span class="app-chip">{{ $project->open_tasks_count }} open</span>
                                    </div>
                                    <div class="mt-3 flex items-center justify-between text-sm text-neutral-400">
                                        <span>{{ $project->completed_tasks_count }} closed in 30d</span>
                                        <span>{{ $project->tasks_count }} total</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </section>

                <section class="app-panel px-4 py-4">
                    <div class="mb-4">
                        <div class="app-eyebrow">Signals</div>
                        <h2 class="mt-1 text-xl font-semibold tracking-tight text-neutral-50">Manager notes</h2>
                    </div>
                    <div class="grid gap-3 text-sm text-neutral-300">
                        <div class="app-panel-muted rounded-2xl px-3 py-3">
                            <div class="font-semibold text-neutral-50">Open load</div>
                            <div class="mt-1 text-neutral-400">
                                {{ $totalOpenTasks }} active tasks across {{ $projectIds->count() }} {{ \Illuminate\Support\Str::plural('project', $projectIds->count()) }}.
                            </div>
                        </div>
                        <div class="app-panel-muted rounded-2xl px-3 py-3">
                            <div class="font-semibold text-neutral-50">Discussion intensity</div>
                            <div class="mt-1 text-neutral-400">
                                {{ $commentsLastSevenDays }} comments landed this week. Use this to spot blocked work or review churn.
                            </div>
                        </div>
                        <div class="app-panel-muted rounded-2xl px-3 py-3">
                            <div class="font-semibold text-neutral-50">Delivery pace</div>
                            <div class="mt-1 text-neutral-400">
                                {{ $completedLastThirtyDays }} tasks closed in 30 days, with {{ $overdueTasks }} currently overdue.
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </section>
    </div>
</x-layouts.app>
