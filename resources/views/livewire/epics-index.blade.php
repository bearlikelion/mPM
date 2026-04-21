<div class="flex h-full w-full flex-col gap-5">
    <section class="app-panel app-hero px-5 py-6 sm:px-7">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
            <div class="space-y-4">
                <div class="app-eyebrow">Milestone Map</div>
                <div>
                    <h1 class="text-4xl font-semibold tracking-tight text-neutral-50 sm:text-5xl">Epics</h1>
                    <p class="mt-3 max-w-3xl text-base leading-7 text-neutral-300">
                        Group related tasks into visible delivery arcs with progress, due dates, and direct links back into execution.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <span class="app-chip">{{ $epics->count() }} epics</span>
                <span class="app-chip">{{ $projects->count() }} projects</span>
            </div>
        </div>
    </section>

    <section class="app-panel app-filter-panel px-4 py-4 sm:px-5">
        <div class="mb-4">
            <div class="app-eyebrow">Scope</div>
            <div class="mt-2 text-lg font-semibold text-neutral-50">Filter by project</div>
        </div>

        <div class="max-w-md">
            <x-mary-choices-offline
                wire:model.live="projectId"
                :options="$projects"
                single
                searchable
                clearable
                placeholder="All projects"
            />
        </div>
    </section>

    @if($epics->isEmpty())
        <div class="app-panel px-4 py-14 text-center text-sm text-neutral-500">No epics yet.</div>
    @else
        <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
            @foreach($epics as $epic)
                @php
                    $pct = $epic->tasks_count > 0 ? round(($epic->completed_tasks_count / $epic->tasks_count) * 100) : 0;
                @endphp
                <a
                    href="{{ route('kanban', ['project' => $epic->project_id, 'epic' => $epic->id]) }}"
                    wire:navigate
                    class="app-panel flex flex-col gap-4 p-5 transition hover:-translate-y-0.5 hover:border-amber-400/40"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <img src="{{ $epic->avatarUrl() }}" alt="" class="h-12 w-12 rounded-2xl border border-neutral-700/70 bg-neutral-900 object-cover" />
                            <div>
                                <div class="font-semibold text-neutral-50">{{ $epic->name }}</div>
                                <div class="text-sm text-neutral-400">{{ $epic->project->name }}</div>
                            </div>
                        </div>
                        @if($epic->completed_at)
                            <span class="rounded-full status-active px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em]">Done</span>
                        @endif
                    </div>

                    @if($epic->description)
                        <div class="line-clamp-3 text-sm leading-7 text-neutral-400">{{ $epic->description }}</div>
                    @endif

                    <div class="mt-auto space-y-3 pt-2">
                        <div class="flex items-center justify-between text-xs text-neutral-500">
                            <span>{{ $epic->completed_tasks_count }}/{{ $epic->tasks_count }} tasks</span>
                            <span>{{ $pct }}%</span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-bar" style="width: {{ $pct }}%"></div>
                        </div>
                        @if($epic->due_date)
                            <div class="text-xs text-neutral-500">Due {{ $epic->due_date->format('M j, Y') }}</div>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
