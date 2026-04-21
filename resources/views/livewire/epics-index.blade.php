<div class="flex h-full w-full flex-col gap-4 p-1">
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="text-xl font-semibold">Epics</h1>

        <select wire:model.live="projectId" class="rounded-md border-neutral-300 bg-white text-sm dark:border-neutral-700 dark:bg-neutral-900">
            <option value="">All projects</option>
            @foreach($projects as $project)
                <option value="{{ $project->id }}">{{ $project->name }}</option>
            @endforeach
        </select>
    </div>

    @if($epics->isEmpty())
        <div class="rounded-xl border border-neutral-200 px-4 py-8 text-center text-sm text-neutral-500 dark:border-neutral-700">
            No epics yet.
        </div>
    @else
        <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
            @foreach($epics as $epic)
                @php
                    $pct = $epic->tasks_count > 0 ? round(($epic->completed_tasks_count / $epic->tasks_count) * 100) : 0;
                @endphp
                <a
                    href="{{ route('kanban', ['project' => $epic->project_id, 'epic' => $epic->id]) }}"
                    wire:navigate
                    class="flex flex-col gap-2 rounded-xl border border-neutral-200 p-4 transition hover:border-indigo-500 dark:border-neutral-700"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <img src="{{ $epic->avatarUrl() }}" alt="" class="h-8 w-8 rounded-lg" />
                            <div class="font-medium">{{ $epic->name }}</div>
                        </div>
                        @if($epic->completed_at)
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] uppercase tracking-wide text-emerald-700">Done</span>
                        @endif
                    </div>
                    <div class="text-xs text-neutral-500">{{ $epic->project->name }}</div>
                    @if($epic->description)
                        <div class="line-clamp-2 text-sm text-neutral-600 dark:text-neutral-300">{{ $epic->description }}</div>
                    @endif
                    <div class="mt-auto pt-2">
                        <div class="mb-1 flex justify-between text-xs text-neutral-500">
                            <span>{{ $epic->completed_tasks_count }}/{{ $epic->tasks_count }} tasks</span>
                            <span>{{ $pct }}%</span>
                        </div>
                        <div class="h-1.5 overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                            <div class="h-full bg-indigo-500" style="width: {{ $pct }}%"></div>
                        </div>
                        @if($epic->due_date)
                            <div class="mt-2 text-xs text-neutral-500">Due {{ $epic->due_date->format('M j, Y') }}</div>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
