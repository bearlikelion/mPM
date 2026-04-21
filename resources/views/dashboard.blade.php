<x-layouts.app>
    @php
        $user = \Illuminate\Support\Facades\Auth::user();
        $orgIds = $user->organizations()->pluck('organizations.id');
        $projectIds = \App\Models\Project::whereIn('organization_id', $orgIds)->pluck('id');

        $myTasks = \App\Models\Task::with('project', 'sprint')
            ->whereHas('assignees', fn($q) => $q->whereKey($user->id))
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
            ->whereHas('task', fn($q) => $q->whereIn('project_id', $projectIds))
            ->latest()
            ->limit(5)
            ->get();

        $activeEpics = \App\Models\Epic::with('project')
            ->withCount(['tasks', 'tasks as completed_tasks_count' => fn($q) => $q->where('status', 'done')])
            ->whereIn('project_id', $projectIds)
            ->whereNull('completed_at')
            ->orderBy('due_date')
            ->limit(6)
            ->get();
    @endphp

    <div class="flex h-full w-full flex-1 flex-col gap-6 p-1">
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <div class="text-sm text-neutral-500">Assigned to me (open)</div>
                <div class="mt-2 text-3xl font-semibold">{{ $myTasks->count() }}</div>
            </div>
            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <div class="text-sm text-neutral-500">Recently completed (org)</div>
                <div class="mt-2 text-3xl font-semibold">{{ $recentCompleted->count() }}</div>
            </div>
            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <div class="text-sm text-neutral-500">Recent comments</div>
                <div class="mt-2 text-3xl font-semibold">{{ $recentComments->count() }}</div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700">
                <div class="border-b border-neutral-200 px-4 py-3 font-medium dark:border-neutral-700">My open tasks</div>
                <ul class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse($myTasks as $task)
                        <li class="flex items-center justify-between px-4 py-2 text-sm">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs text-neutral-500">{{ $task->key }}</span>
                                <span>{{ $task->title }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-neutral-500">{{ $task->project->name }}</span>
                                <span class="rounded-full px-2 py-0.5 text-xs uppercase tracking-wide {{ match($task->priority) {
                                    'crit' => 'bg-red-100 text-red-700',
                                    'high' => 'bg-orange-100 text-orange-700',
                                    'med' => 'bg-blue-100 text-blue-700',
                                    default => 'bg-neutral-100 text-neutral-600',
                                } }}">{{ $task->priority }}</span>
                            </div>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-center text-sm text-neutral-500">Nothing assigned. Nice.</li>
                    @endforelse
                </ul>
            </div>

            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700">
                <div class="border-b border-neutral-200 px-4 py-3 font-medium dark:border-neutral-700">Recent activity</div>
                <ul class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @foreach($recentComments as $comment)
                        <li class="px-4 py-2 text-sm">
                            <div class="text-neutral-600 dark:text-neutral-300">
                                <span class="font-medium">{{ $comment->user?->name ?? 'Someone' }}</span>
                                commented on
                                <span class="font-mono text-xs text-neutral-500">{{ $comment->task->key }}</span>
                            </div>
                            <div class="truncate text-xs text-neutral-500">{{ $comment->body }}</div>
                        </li>
                    @endforeach
                    @foreach($recentCompleted as $task)
                        <li class="px-4 py-2 text-sm">
                            <div class="text-neutral-600 dark:text-neutral-300">
                                Completed
                                <span class="font-mono text-xs">{{ $task->key }}</span>
                                — {{ $task->title }}
                            </div>
                        </li>
                    @endforeach
                    @if($recentComments->isEmpty() && $recentCompleted->isEmpty())
                        <li class="px-4 py-6 text-center text-sm text-neutral-500">No recent activity.</li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-lg font-medium">Active epics</h2>
                <a href="{{ route('epics') }}" wire:navigate class="text-sm text-indigo-600 hover:underline dark:text-indigo-400">View all</a>
            </div>
            @if($activeEpics->isEmpty())
                <div class="py-6 text-center text-sm text-neutral-500">No active epics.</div>
            @else
                <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($activeEpics as $epic)
                        @php $pct = $epic->tasks_count > 0 ? round(($epic->completed_tasks_count / $epic->tasks_count) * 100) : 0; @endphp
                        <a href="{{ route('kanban', ['project' => $epic->project_id, 'epic' => $epic->id]) }}" wire:navigate class="flex flex-col gap-2 rounded-lg border border-neutral-200 p-3 transition hover:border-indigo-500 dark:border-neutral-700">
                            <div class="flex items-start justify-between gap-2">
                                <div class="font-medium">{{ $epic->name }}</div>
                                @if($epic->due_date)
                                    <span class="text-[10px] text-neutral-500">{{ $epic->due_date->format('M j') }}</span>
                                @endif
                            </div>
                            <div class="text-xs text-neutral-500">{{ $epic->project->name }}</div>
                            <div>
                                <div class="mb-1 flex justify-between text-[10px] text-neutral-500">
                                    <span>{{ $epic->completed_tasks_count }}/{{ $epic->tasks_count }}</span>
                                    <span>{{ $pct }}%</span>
                                </div>
                                <div class="h-1 overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                                    <div class="h-full bg-indigo-500" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-lg font-medium">Your organizations</h2>
            </div>
            <div class="grid gap-3 md:grid-cols-3">
                @foreach($user->organizations as $org)
                    <a href="/app/{{ $org->slug }}" class="flex items-center gap-3 rounded-lg border border-neutral-200 p-4 transition hover:border-indigo-500 dark:border-neutral-700">
                        <img src="{{ $org->logoUrl() }}" alt="" class="h-10 w-10 rounded-lg" />
                        <div class="flex-1">
                            <div class="font-medium">{{ $org->name }}</div>
                            <div class="text-sm text-neutral-500">{{ $org->projects()->count() }} projects</div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts.app>
