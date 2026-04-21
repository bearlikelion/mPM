<x-layouts.app>
    @php
        $projects = \App\Models\Project::query()
            ->when($currentOrg, fn ($query) => $query->whereBelongsTo($currentOrg))
            ->when(! $currentOrg, fn ($query) => $query->whereRaw('1 = 0'))
            ->withCount([
                'tasks',
                'tasks as open_tasks_count' => fn ($query) => $query->where('status', '!=', 'done'),
                'tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'done'),
                'epics as active_epics_count' => fn ($query) => $query->whereNull('completed_at'),
            ])
            ->with('organization')
            ->orderBy('name')
            ->get();

        $openTasks = $projects->sum('open_tasks_count');
        $completedTasks = $projects->sum('completed_tasks_count');
    @endphp

    <div class="flex flex-col gap-4">
        <x-page-header
            title="Projects"
            :subtitle="$currentOrg ? 'Scan workload and momentum across '.$currentOrg->name.'.' : 'Scan workload and momentum across everything you can access.'"
        >
            <x-slot:actions>
                @if($currentOrg)
                    <span class="app-chip">{{ $currentOrg->name }}</span>
                @endif
                <span class="app-chip">{{ $projects->count() }} total</span>
                <span class="app-chip">{{ $openTasks }} open</span>
                <flux:modal.trigger name="create-task-modal">
                    <button type="button" class="btn btn-sm btn-primary">+ new task</button>
                </flux:modal.trigger>
            </x-slot:actions>
        </x-page-header>

        @if($projects->isEmpty())
            <div class="gv-card px-4 py-10 text-center text-sm text-[color:var(--gv-fg4)]">no projects yet</div>
        @else
            <div class="grid gap-3 md:grid-cols-2 2xl:grid-cols-3">
                @foreach($projects as $project)
                    @php
                        $rate = $project->tasks_count > 0
                            ? (int) round(($project->completed_tasks_count / $project->tasks_count) * 100)
                            : 0;
                    @endphp
                    <div class="gv-card gv-hover p-4">
                        <div class="flex items-start gap-3">
                            <img src="{{ $project->avatarUrl() }}" alt="" class="h-10 w-10 rounded-sm border border-[color:var(--gv-border)] bg-[color:var(--gv-bg1)] object-cover" />
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="truncate text-base font-semibold text-[color:var(--gv-fg0)]">{{ $project->name }}</span>
                                    <span class="text-xs text-[color:var(--gv-fg4)]">{{ $project->key }}</span>
                                </div>
                                <div class="text-xs text-[color:var(--gv-fg4)]">{{ $project->organization->name }}</div>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-3 gap-3">
                            <div>
                                <div class="app-kpi-label">open</div>
                                <div class="mt-0.5 font-mono text-lg font-semibold text-[color:var(--gv-fg0)]">{{ $project->open_tasks_count }}</div>
                            </div>
                            <div>
                                <div class="app-kpi-label">done</div>
                                <div class="mt-0.5 font-mono text-lg font-semibold text-[color:var(--gv-fg0)]">{{ $project->completed_tasks_count }}</div>
                            </div>
                            <div>
                                <div class="app-kpi-label">epics</div>
                                <div class="mt-0.5 font-mono text-lg font-semibold text-[color:var(--gv-fg0)]">{{ $project->active_epics_count }}</div>
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <div class="flex items-center justify-between text-xs text-[color:var(--gv-fg4)]">
                                <span>{{ $rate }}% closed</span>
                                <span>{{ $project->tasks_count }} tasks</span>
                            </div>
                            <div class="progress-track"><div class="progress-bar" style="width: {{ $rate }}%"></div></div>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-3 border-t border-[color:var(--gv-border)] pt-2 font-mono text-xs">
                            <a href="{{ route('kanban', ['project' => $project->id]) }}" wire:navigate class="app-link">kanban</a>
                            <a href="{{ route('backlog', ['project' => $project->id]) }}" wire:navigate class="app-link">backlog</a>
                            <a href="{{ route('sprints', ['project' => $project->id]) }}" wire:navigate class="app-link">sprints</a>
                            <a href="{{ route('epics', ['project' => $project->id]) }}" wire:navigate class="app-link">epics</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.app>
