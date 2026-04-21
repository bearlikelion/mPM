<x-layouts.app>
    @php
        $user = auth()->user();
        $orgIds = $user->organizations()->pluck('organizations.id');

        $projects = \App\Models\Project::query()
            ->whereIn('organization_id', $orgIds)
            ->withCount([
                'tasks',
                'tasks as open_tasks_count' => fn ($query) => $query->where('status', '!=', 'done'),
                'tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'done'),
                'epics as active_epics_count' => fn ($query) => $query->whereNull('completed_at'),
                'sprints as active_sprints_count' => fn ($query) => $query
                    ->whereNotNull('started_at')
                    ->whereNull('ended_at'),
            ])
            ->with('organization')
            ->orderBy('name')
            ->get();

        $openTasks = $projects->sum('open_tasks_count');
        $completedTasks = $projects->sum('completed_tasks_count');
        $activeEpics = $projects->sum('active_epics_count');
    @endphp

    <div class="grid gap-4 xl:min-h-[calc(100vh-5.5rem)] xl:grid-cols-[1.15fr_0.85fr] xl:grid-rows-[auto_1fr]">
        <section class="app-panel app-hero overflow-hidden px-5 py-5 sm:px-6">
            <div class="flex h-full flex-col justify-between gap-5">
                <div class="space-y-3">
                    <div class="app-eyebrow">Project Dashboards</div>
                    <div>
                        <h1 class="app-title">Projects overview</h1>
                        <p class="mt-2 max-w-3xl text-base leading-7 text-neutral-300">
                            Scan workload, momentum, and milestone pressure across every project you can access.
                        </p>
                    </div>
                </div>

                <div class="grid gap-2 sm:grid-cols-4">
                    <a href="{{ route('dashboard') }}" wire:navigate class="app-panel-muted rounded-2xl px-3 py-3 transition hover:border-amber-400/40 hover:bg-neutral-950/60">
                        <div class="app-eyebrow">Home</div>
                        <div class="mt-1 text-base font-semibold text-neutral-50">Dashboard</div>
                    </a>
                    <a href="{{ route('kanban') }}" wire:navigate class="app-panel-muted rounded-2xl px-3 py-3 transition hover:border-amber-400/40 hover:bg-neutral-950/60">
                        <div class="app-eyebrow">Board</div>
                        <div class="mt-1 text-base font-semibold text-neutral-50">All tasks</div>
                    </a>
                    <a href="{{ route('backlog') }}" wire:navigate class="app-panel-muted rounded-2xl px-3 py-3 transition hover:border-amber-400/40 hover:bg-neutral-950/60">
                        <div class="app-eyebrow">Plan</div>
                        <div class="mt-1 text-base font-semibold text-neutral-50">Backlog</div>
                    </a>
                    <flux:modal.trigger name="create-task-modal">
                        <button type="button" class="app-panel-muted flex h-full w-full flex-col rounded-2xl px-3 py-3 text-left transition hover:border-amber-400/40 hover:bg-neutral-950/60">
                            <div class="app-eyebrow">Create</div>
                            <div class="mt-1 text-base font-semibold text-neutral-50">New task +</div>
                        </button>
                    </flux:modal.trigger>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-3 xl:grid-cols-3">
            <div class="app-panel app-kpi">
                <div class="relative space-y-2">
                    <div class="app-kpi-label">Projects</div>
                    <div class="app-kpi-value">{{ $projects->count() }}</div>
                    <p class="text-sm text-neutral-400">Visible across your organizations.</p>
                </div>
            </div>
            <div class="app-panel app-kpi">
                <div class="relative space-y-2">
                    <div class="app-kpi-label">Open Tasks</div>
                    <div class="app-kpi-value">{{ $openTasks }}</div>
                    <p class="text-sm text-neutral-400">Work currently in motion.</p>
                </div>
            </div>
            <div class="app-panel app-kpi">
                <div class="relative space-y-2">
                    <div class="app-kpi-label">Completed</div>
                    <div class="app-kpi-value">{{ $completedTasks }}</div>
                    <p class="text-sm text-neutral-400">Closed tasks across all projects.</p>
                </div>
            </div>
        </section>

        <section class="grid gap-4 xl:col-span-2 xl:grid-cols-[1.2fr_0.8fr]">
            <div class="app-panel overflow-hidden xl:min-h-0">
                <div class="border-b border-neutral-700/60 px-4 py-3">
                    <div class="app-eyebrow">Portfolio</div>
                    <h2 class="mt-1 text-xl font-semibold tracking-tight text-neutral-50">Project dashboards</h2>
                </div>

                @if($projects->isEmpty())
                    <div class="px-4 py-8 text-center text-sm text-neutral-500">No projects available yet.</div>
                @else
                    <div class="divide-y divide-neutral-700/60">
                        @foreach($projects as $project)
                            @php
                                $completionRate = $project->tasks_count > 0
                                    ? (int) round(($project->completed_tasks_count / $project->tasks_count) * 100)
                                    : 0;
                            @endphp
                            <div class="grid gap-4 px-4 py-4 lg:grid-cols-[minmax(0,1.15fr)_repeat(4,minmax(0,0.45fr))] lg:items-center">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $project->avatarUrl() }}" alt="" class="h-11 w-11 rounded-2xl border border-neutral-700/70 bg-neutral-900 object-cover" />
                                        <div class="min-w-0">
                                            <div class="truncate font-semibold text-neutral-50">{{ $project->name }}</div>
                                            <div class="text-sm text-neutral-400">{{ $project->organization->name }} · {{ $project->key }}</div>
                                        </div>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-3 text-sm text-neutral-400">
                                        <a href="{{ route('kanban', ['project' => $project->id]) }}" wire:navigate class="app-link">Kanban</a>
                                        <a href="{{ route('backlog', ['project' => $project->id]) }}" wire:navigate class="app-link">Backlog</a>
                                        <a href="{{ route('sprints', ['project' => $project->id]) }}" wire:navigate class="app-link">Sprints</a>
                                        <a href="{{ route('epics', ['project' => $project->id]) }}" wire:navigate class="app-link">Epics</a>
                                    </div>
                                </div>

                                <div>
                                    <div class="app-eyebrow">Open</div>
                                    <div class="mt-1 text-lg font-semibold text-neutral-50">{{ $project->open_tasks_count }}</div>
                                </div>

                                <div>
                                    <div class="app-eyebrow">Done</div>
                                    <div class="mt-1 text-lg font-semibold text-neutral-50">{{ $project->completed_tasks_count }}</div>
                                </div>

                                <div>
                                    <div class="app-eyebrow">Epics</div>
                                    <div class="mt-1 text-lg font-semibold text-neutral-50">{{ $project->active_epics_count }}</div>
                                </div>

                                <div class="space-y-2">
                                    <div class="app-eyebrow">Progress</div>
                                    <div class="flex items-center justify-between text-xs text-neutral-500">
                                        <span>{{ $completionRate }}% closed</span>
                                        <span>{{ $project->tasks_count }} tasks</span>
                                    </div>
                                    <div class="progress-track">
                                        <div class="progress-bar" style="width: {{ $completionRate }}%"></div>
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
                        <div class="app-eyebrow">Signals</div>
                        <h2 class="mt-1 text-xl font-semibold tracking-tight text-neutral-50">Portfolio notes</h2>
                    </div>
                    <div class="grid gap-3 text-sm text-neutral-300">
                        <div class="app-panel-muted rounded-2xl px-3 py-3">
                            <div class="font-semibold text-neutral-50">Active epics</div>
                            <div class="mt-1 text-neutral-400">{{ $activeEpics }} epics are still in motion across the portfolio.</div>
                        </div>
                        <div class="app-panel-muted rounded-2xl px-3 py-3">
                            <div class="font-semibold text-neutral-50">Cross-project load</div>
                            <div class="mt-1 text-neutral-400">{{ $openTasks }} open tasks are visible across {{ $projects->count() }} projects.</div>
                        </div>
                        <div class="app-panel-muted rounded-2xl px-3 py-3">
                            <div class="font-semibold text-neutral-50">Fast entry</div>
                            <div class="mt-1 text-neutral-400">Use the global `Create new task +` action from any page to capture work without leaving context.</div>
                        </div>
                    </div>
                </section>
            </div>
        </section>
    </div>
</x-layouts.app>
