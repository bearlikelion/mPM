<x-layouts.app>
    @php
        $user = \Illuminate\Support\Facades\Auth::user();
        $currentOrg = $user->defaultOrganization ?? $user->organizations->first();
        $canManageOrg = $currentOrg && $user->can('update', $currentOrg);
        $orgIds = $user->organizations()->pluck('organizations.id');
        $projectIds = \App\Models\Project::whereIn('organization_id', $orgIds)->pluck('id');

        $myTasks = \App\Models\Task::with('project', 'sprint')
            ->whereHas('assignees', fn ($q) => $q->whereKey($user->id))
            ->whereIn('project_id', $projectIds)
            ->where('status', '!=', 'done')
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

    <div class="grid gap-4 xl:min-h-[calc(100vh-5.5rem)] xl:grid-cols-[1.15fr_0.85fr] xl:grid-rows-[auto_auto_1fr]">
        <section class="app-panel app-hero overflow-hidden px-5 py-5 sm:px-6 xl:row-span-2">
            <div class="flex h-full flex-col justify-between gap-5">
                <div class="space-y-3">
                    <div class="app-eyebrow">Execution Cockpit</div>
                    <div>
                        <h1 class="app-title">
                            {{ $currentOrg?->name ?? config('app.name', 'mPM') }}
                        </h1>
                        <p class="mt-2 max-w-3xl text-base leading-7 text-neutral-300">
                            Project pulse, activity, and milestones in one view.
                        </p>
                    </div>
                </div>

                <div class="grid gap-3 xl:grid-cols-[auto_1fr]">
                    <div class="flex flex-wrap gap-2">
                        <span class="app-chip">{{ $user->formatLocalTime(now(), 'M d, Y g:i A T') }}</span>
                        <span class="app-chip">{{ $user->organizations->count() }} {{ \Illuminate\Support\Str::plural('org', $user->organizations->count()) }}</span>
                        <span class="app-chip">{{ $projectIds->count() }} {{ \Illuminate\Support\Str::plural('project', $projectIds->count()) }}</span>
                    </div>

                    <div @class([
                        'grid gap-2',
                        'sm:grid-cols-4' => $canManageOrg,
                        'sm:grid-cols-3' => ! $canManageOrg,
                    ])>
                        @if($canManageOrg)
                            <a href="{{ route('manager') }}" wire:navigate class="app-panel-muted rounded-2xl px-3 py-3 transition hover:border-amber-400/40 hover:bg-neutral-950/60">
                                <div class="app-eyebrow">Manage</div>
                                <div class="mt-1 text-base font-semibold text-neutral-50">Analytics</div>
                            </a>
                        @endif
                        <a href="{{ route('kanban') }}" wire:navigate class="app-panel-muted rounded-2xl px-3 py-3 transition hover:border-amber-400/40 hover:bg-neutral-950/60">
                            <div class="app-eyebrow">Flow</div>
                            <div class="mt-1 text-base font-semibold text-neutral-50">Kanban</div>
                        </a>
                        <a href="{{ route('backlog') }}" wire:navigate class="app-panel-muted rounded-2xl px-3 py-3 transition hover:border-amber-400/40 hover:bg-neutral-950/60">
                            <div class="app-eyebrow">Plan</div>
                            <div class="mt-1 text-base font-semibold text-neutral-50">Backlog</div>
                        </a>
                        <a href="{{ route('epics') }}" wire:navigate class="app-panel-muted rounded-2xl px-3 py-3 transition hover:border-amber-400/40 hover:bg-neutral-950/60">
                            <div class="app-eyebrow">Milestones</div>
                            <div class="mt-1 text-base font-semibold text-neutral-50">Epics</div>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-3 xl:col-start-2">
            <div class="app-panel app-kpi">
                <div class="relative space-y-2">
                    <div class="app-kpi-label">Assigned</div>
                    <div class="app-kpi-value">{{ $myTasks->count() }}</div>
                    <p class="text-sm text-neutral-400">Open work.</p>
                </div>
            </div>
            <div class="app-panel app-kpi">
                <div class="relative space-y-2">
                    <div class="app-kpi-label">Completed</div>
                    <div class="app-kpi-value">{{ $recentCompleted->count() }}</div>
                    <p class="text-sm text-neutral-400">Recently closed.</p>
                </div>
            </div>
            <div class="app-panel app-kpi">
                <div class="relative space-y-2">
                    <div class="app-kpi-label">Comments</div>
                    <div class="app-kpi-value">{{ $recentComments->count() }}</div>
                    <p class="text-sm text-neutral-400">Fresh discussion.</p>
                </div>
            </div>
        </section>

        <section class="grid gap-4 xl:col-span-2 xl:grid-cols-[1.05fr_1fr_0.85fr]">
            <div class="app-panel overflow-hidden xl:min-h-0">
                <div class="flex items-center justify-between border-b border-neutral-700/60 px-4 py-3">
                    <div>
                        <div class="app-eyebrow">Your Queue</div>
                        <h2 class="mt-1 text-xl font-semibold tracking-tight text-neutral-50">My open tasks</h2>
                    </div>
                    <span class="app-chip">{{ $myTasks->count() }} open</span>
                </div>
                <ul class="divide-y divide-neutral-700/60">
                    @forelse($myTasks as $task)
                        <li class="flex flex-col gap-2 px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-2">
                                    <span class="app-chip">{{ $task->key }}</span>
                                    @if($task->sprint)
                                        <span class="truncate text-xs text-neutral-500">{{ $task->sprint->name }}</span>
                                    @endif
                                </div>
                                <span @class([
                                    'rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em]',
                                    'priority-crit' => $task->priority === 'crit',
                                    'priority-high' => $task->priority === 'high',
                                    'priority-med' => $task->priority === 'med',
                                    'priority-low' => $task->priority === 'low',
                                ])>{{ $task->priority }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0 truncate text-base font-medium text-neutral-50">{{ $task->title }}</div>
                                <a href="{{ route('tasks.show', $task->key) }}" wire:navigate class="app-link shrink-0 text-sm">Open</a>
                            </div>
                            <div class="text-sm text-neutral-400">{{ $task->project->name }}</div>
                        </li>
                    @empty
                        <li class="px-4 py-8 text-center text-sm text-neutral-500">Nothing assigned. Nice.</li>
                    @endforelse
                </ul>
            </div>

            <div class="app-panel overflow-hidden xl:min-h-0">
                <div class="border-b border-neutral-700/60 px-4 py-3">
                    <div class="app-eyebrow">Pulse</div>
                    <h2 class="mt-1 text-xl font-semibold tracking-tight text-neutral-50">Recent activity</h2>
                </div>
                <ul class="divide-y divide-neutral-700/60">
                    @foreach($recentComments as $comment)
                        <li class="px-4 py-3 text-sm">
                            <div class="text-neutral-200">
                                @if($comment->user)
                                    <a
                                        href="{{ route('kanban', ['project' => $comment->task->project_id, 'assignee' => $comment->user_id]) }}"
                                        wire:navigate
                                        class="font-medium text-neutral-50 transition hover:text-amber-300"
                                    >{{ $comment->user->name }}</a>
                                @else
                                    <span class="font-medium text-neutral-50">Someone</span>
                                @endif
                                commented on
                                <a
                                    href="{{ route('tasks.show', $comment->task->key) }}"
                                    wire:navigate
                                    class="font-mono text-xs text-neutral-400 transition hover:text-amber-300"
                                >{{ $comment->task->key }}</a>
                            </div>
                            <div class="mt-1 line-clamp-2 text-sm text-neutral-500">{{ $comment->body }}</div>
                        </li>
                    @endforeach
                    @foreach($recentCompleted as $task)
                        <li class="px-4 py-3 text-sm">
                            <div class="flex items-center gap-2 text-neutral-200">
                                <span class="rounded-full status-active px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.18em]">Done</span>
                                <a
                                    href="{{ route('tasks.show', $task->key) }}"
                                    wire:navigate
                                    class="font-mono text-xs text-neutral-400 transition hover:text-amber-300"
                                >{{ $task->key }}</a>
                                <a
                                    href="{{ route('tasks.show', $task->key) }}"
                                    wire:navigate
                                    class="truncate transition hover:text-amber-300"
                                >{{ $task->title }}</a>
                            </div>
                        </li>
                    @endforeach
                    @if($recentComments->isEmpty() && $recentCompleted->isEmpty())
                        <li class="px-4 py-8 text-center text-sm text-neutral-500">No recent activity.</li>
                    @endif
                </ul>
            </div>

            <div class="flex flex-col gap-4 xl:min-h-0">
                <section class="app-panel px-4 py-4">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <div class="app-eyebrow">Milestones</div>
                            <h2 class="mt-1 text-xl font-semibold tracking-tight text-neutral-50">Active epics</h2>
                        </div>
                        <a href="{{ route('epics') }}" wire:navigate class="app-link text-sm">View all</a>
                    </div>
                    @if($activeEpics->isEmpty())
                        <div class="rounded-2xl border border-dashed border-neutral-700/80 px-4 py-8 text-center text-sm text-neutral-500">No active epics.</div>
                    @else
                        <div class="grid gap-3">
                            @foreach($activeEpics as $epic)
                                @php $pct = $epic->tasks_count > 0 ? round(($epic->completed_tasks_count / $epic->tasks_count) * 100) : 0; @endphp
                                <a href="{{ route('kanban', ['project' => $epic->project_id, 'epic' => $epic->id]) }}" wire:navigate class="app-panel-muted flex flex-col gap-3 rounded-2xl p-3 transition hover:border-amber-400/40 hover:bg-neutral-950/60">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <img src="{{ $epic->avatarUrl() }}" alt="" class="h-10 w-10 rounded-2xl border border-neutral-700/70 bg-neutral-900 object-cover" />
                                            <div class="min-w-0">
                                                <div class="truncate font-semibold text-neutral-50">{{ $epic->name }}</div>
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

                <section class="app-panel px-4 py-4">
                    <div class="mb-4">
                        <div class="app-eyebrow">Organizations</div>
                        <h2 class="mt-1 text-xl font-semibold tracking-tight text-neutral-50">Your organizations</h2>
                    </div>
                    <div class="grid gap-3">
                        @foreach($user->organizations->take(3) as $org)
                            <a href="/app/{{ $org->slug }}" class="app-panel-muted flex items-center gap-3 rounded-2xl p-3 transition hover:border-amber-400/40 hover:bg-neutral-950/60">
                                <img src="{{ $org->logoUrl() }}" alt="" class="h-11 w-11 rounded-2xl border border-neutral-700/70 bg-neutral-900 object-cover" />
                                <div class="min-w-0 flex-1">
                                    <div class="truncate font-semibold text-neutral-50">{{ $org->name }}</div>
                                    <div class="text-sm text-neutral-400">{{ $org->projects()->count() }} {{ \Illuminate\Support\Str::plural('project', $org->projects()->count()) }}</div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            </div>
        </section>
    </div>
</x-layouts.app>
