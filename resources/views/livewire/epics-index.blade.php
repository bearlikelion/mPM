<div class="flex h-full w-full flex-col gap-4">
    <x-page-header title="Epics" subtitle="Delivery arcs with progress and due dates.">
        <x-slot:actions>
            <span class="app-chip">{{ $epics->count() }} epics</span>
            <span class="app-chip">{{ $projects->count() }} projects</span>
        </x-slot:actions>
    </x-page-header>

    <div class="max-w-sm">
        <x-mary-choices-offline
            wire:model.live="projectId"
            :options="$projects"
            single
            searchable
            clearable
            placeholder="All projects"
        />
    </div>

    @if($epics->isEmpty())
        <div class="gv-card px-4 py-10 text-center text-sm text-[color:var(--gv-fg4)]">no epics yet</div>
    @else
        <div class="grid gap-3 md:grid-cols-2 2xl:grid-cols-3">
            @foreach($epics as $epic)
                @php $pct = $epic->tasks_count > 0 ? round(($epic->completed_tasks_count / $epic->tasks_count) * 100) : 0; @endphp
                <a
                    href="{{ route('kanban', ['project' => $epic->project_id, 'epic' => $epic->id]) }}"
                    wire:navigate
                    class="gv-card gv-hover flex flex-col gap-3 p-4"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex min-w-0 items-center gap-3">
                            <img src="{{ $epic->avatarUrl() }}" alt="" class="h-10 w-10 rounded-sm border border-[color:var(--gv-border)] bg-[color:var(--gv-bg1)] object-cover" />
                            <div class="min-w-0">
                                <div class="truncate text-sm font-semibold text-[color:var(--gv-fg0)]">{{ $epic->name }}</div>
                                <div class="text-xs text-[color:var(--gv-fg4)]">{{ $epic->project->name }}</div>
                            </div>
                        </div>
                        @if($epic->completed_at)
                            <span class="rounded-sm px-1.5 py-0.5 font-mono text-[0.6rem] font-semibold uppercase tracking-[0.14em] status-active">done</span>
                        @endif
                    </div>

                    @if($epic->description)
                        <div class="line-clamp-2 text-xs text-[color:var(--gv-fg4)]">{{ $epic->description }}</div>
                    @endif

                    <div class="mt-auto space-y-1.5">
                        <div class="flex items-center justify-between text-xs text-[color:var(--gv-fg4)]">
                            <span>{{ $epic->completed_tasks_count }}/{{ $epic->tasks_count }} tasks</span>
                            <span>{{ $pct }}%</span>
                        </div>
                        <div class="progress-track"><div class="progress-bar" style="width: {{ $pct }}%"></div></div>
                        @if($epic->due_date)
                            <div class="text-xs text-[color:var(--gv-fg4)]">due {{ $epic->due_date->format('M j, Y') }}</div>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
