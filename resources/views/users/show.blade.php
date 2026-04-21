<x-layouts.app>
    @php
        $canManageCurrentOrg = $currentOrg && auth()->user()->can('update', $currentOrg);
        $orgMemberships = $profileUser->organizations()
            ->whereIn('organizations.id', $sharedOrganizationIds)
            ->get();
    @endphp

    <div class="grid gap-4 xl:min-h-[calc(100vh-5.5rem)] xl:grid-cols-[1.15fr_0.85fr] xl:grid-rows-[auto_1fr]">
        <section class="app-panel app-hero overflow-hidden px-5 py-5 sm:px-6">
            <div class="flex h-full flex-col justify-between gap-5">
                <div class="space-y-4">
                    <div class="app-eyebrow">Team Profile</div>
                    <div class="flex items-start gap-4">
                        <img src="{{ $profileUser->avatarUrl() }}" alt="{{ $profileUser->name }}" class="h-20 w-20 rounded-3xl border border-neutral-700/70 bg-neutral-900 object-cover shadow-lg shadow-black/25" />
                        <div class="min-w-0">
                            <h1 class="app-title">{{ $profileUser->name }}</h1>
                            <p class="mt-2 text-base text-neutral-300">{{ $profileUser->email }}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="app-chip">{{ $profileUser->preferredTimezone() }}</span>
                                @if($currentOrg)
                                    <span class="app-chip">{{ $currentOrg->name }}</span>
                                @endif
                                <a href="{{ route('kanban', ['assignee' => $profileUser->id]) }}" wire:navigate class="app-chip transition hover:border-amber-400/40 hover:text-neutral-50">View workload</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-2 sm:grid-cols-3">
                    <a href="{{ route('kanban', ['assignee' => $profileUser->id]) }}" wire:navigate class="app-panel-muted rounded-2xl px-3 py-3 transition hover:border-amber-400/40 hover:bg-neutral-950/60">
                        <div class="app-eyebrow">Board</div>
                        <div class="mt-1 text-base font-semibold text-neutral-50">Assigned tasks</div>
                    </a>
                    @if($canManageCurrentOrg)
                        <a href="{{ route('manager') }}" wire:navigate class="app-panel-muted rounded-2xl px-3 py-3 transition hover:border-amber-400/40 hover:bg-neutral-950/60">
                            <div class="app-eyebrow">Manager</div>
                            <div class="mt-1 text-base font-semibold text-neutral-50">Back to analytics</div>
                        </a>
                    @endif
                    <a href="{{ route('dashboard') }}" wire:navigate class="app-panel-muted rounded-2xl px-3 py-3 transition hover:border-amber-400/40 hover:bg-neutral-950/60">
                        <div class="app-eyebrow">Home</div>
                        <div class="mt-1 text-base font-semibold text-neutral-50">Dashboard</div>
                    </a>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-3 xl:grid-cols-3">
            <a href="{{ route('kanban', ['assignee' => $profileUser->id]) }}" wire:navigate class="app-panel app-kpi transition hover:border-amber-400/40">
                <div class="relative space-y-2">
                    <div class="app-kpi-label">Open Work</div>
                    <div class="app-kpi-value">{{ $openTasksCount }}</div>
                    <p class="text-sm text-neutral-400">Visible assigned tasks in motion.</p>
                </div>
            </a>
            <a href="{{ route('kanban', ['assignee' => $profileUser->id, 'status' => 'done']) }}" wire:navigate class="app-panel app-kpi transition hover:border-amber-400/40">
                <div class="relative space-y-2">
                    <div class="app-kpi-label">Closed 30d</div>
                    <div class="app-kpi-value">{{ $completedTasksCount }}</div>
                    <p class="text-sm text-neutral-400">Completed tasks over the last month.</p>
                </div>
            </a>
            <a href="{{ route('kanban', ['assignee' => $profileUser->id]) }}" wire:navigate class="app-panel app-kpi transition hover:border-amber-400/40">
                <div class="relative space-y-2">
                    <div class="app-kpi-label">Comments 7d</div>
                    <div class="app-kpi-value">{{ $recentCommentsCount }}</div>
                    <p class="text-sm text-neutral-400">Recent discussion attached to org work.</p>
                </div>
            </a>
        </section>

        <section class="grid gap-4 xl:col-span-2 xl:grid-cols-[1.1fr_0.9fr]">
            <div class="app-panel overflow-hidden xl:min-h-0">
                <div class="border-b border-neutral-700/60 px-4 py-3">
                    <div class="app-eyebrow">Workload</div>
                    <h2 class="mt-1 text-xl font-semibold tracking-tight text-neutral-50">Recent assigned tasks</h2>
                </div>

                @if($recentTasks->isEmpty())
                    <div class="px-4 py-8 text-center text-sm text-neutral-500">No assigned tasks found.</div>
                @else
                    <div class="divide-y divide-neutral-700/60">
                        @foreach($recentTasks as $task)
                            <div class="flex items-center justify-between gap-3 px-4 py-3">
                                <div class="min-w-0">
                                    <a href="{{ route('tasks.show', $task->key) }}" wire:navigate class="block truncate font-semibold text-neutral-50 transition hover:text-amber-300">{{ $task->title }}</a>
                                    <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-neutral-400">
                                        <a href="{{ route('kanban', ['project' => $task->project_id]) }}" wire:navigate class="transition hover:text-amber-300">{{ $task->project->name }}</a>
                                        <span>·</span>
                                        <a href="{{ route('kanban', ['project' => $task->project_id, 'task' => $task->key]) }}" wire:navigate class="font-mono transition hover:text-amber-300">{{ $task->key }}</a>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('kanban', ['project' => $task->project_id, 'task' => $task->key]) }}" wire:navigate class="app-link text-sm">Open board</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="flex flex-col gap-4 xl:min-h-0">
                <section class="app-panel px-4 py-4">
                    <div class="mb-4">
                        <div class="app-eyebrow">Membership</div>
                        <h2 class="mt-1 text-xl font-semibold tracking-tight text-neutral-50">Organizations</h2>
                    </div>
                    <div class="grid gap-3">
                        @foreach($orgMemberships as $org)
                            <div class="app-panel-muted rounded-2xl p-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $org->logoUrl() }}" alt="" class="h-10 w-10 rounded-2xl border border-neutral-700/70 bg-neutral-900 object-cover" />
                                    <div class="min-w-0">
                                        <div class="truncate font-semibold text-neutral-50">{{ $org->name }}</div>
                                        <div class="text-sm text-neutral-400">{{ $org->pivot->role === 'org_admin' ? 'Org admin' : 'Member' }} · {{ $org->preferredTimezone() }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>
        </section>
    </div>
</x-layouts.app>
