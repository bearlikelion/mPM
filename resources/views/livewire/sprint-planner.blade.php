<div class="flex h-full w-full flex-col gap-4">
    <x-page-header title="Sprint Planning" subtitle="Schedule planning meetings, estimate backlog cards together, and create focused sprints.">
        <x-slot:actions>
            <span class="app-chip">{{ $meetings->count() }} meeting{{ $meetings->count() === 1 ? '' : 's' }}</span>
            <span class="app-chip">{{ $sprints->count() }} sprint{{ $sprints->count() === 1 ? '' : 's' }}</span>
        </x-slot:actions>
    </x-page-header>

    <div class="max-w-sm">
        <x-mary-choices-offline
            wire:model.live="projectId"
            :options="$projects"
            single
            searchable
            clearable
            placeholder="Select project"
        />
    </div>

    @if($selectedMeeting)
        <livewire:sprint-planning-room :meeting-id="$selectedMeeting->id" :key="'planning-room-'.$selectedMeeting->id" />
    @else
        @if($projectId && $canScheduleMeeting)
            <form wire:submit="scheduleMeeting" class="gv-card grid gap-3 p-3 md:grid-cols-[2fr_1fr_auto] md:items-end">
                <x-mary-input wire:model="meetingName" label="Meeting name" placeholder="Sprint 8 planning" />
                <x-mary-input wire:model="scheduledAt" type="datetime-local" label="Scheduled for" />
                <x-mary-button type="submit" label="Schedule" spinner="scheduleMeeting" class="btn-primary btn-sm" />
            </form>
        @endif

        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_24rem]">
            <section class="gv-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-[color:var(--gv-border)] px-3 py-2">
                    <span class="text-sm font-semibold uppercase tracking-wide text-[color:var(--gv-amber)]">» planning meetings</span>
                    <span class="app-chip">{{ $meetings->count() }} total</span>
                </div>

                @if($meetings->isEmpty())
                    <div class="px-3 py-8 text-center text-sm text-[color:var(--gv-fg4)]">no planning meetings scheduled</div>
                @else
                    <ul class="divide-y divide-[color:var(--gv-border)]">
                        @foreach($meetings as $meeting)
                            <li wire:key="meeting-{{ $meeting->id }}" class="flex flex-col gap-3 px-3 py-3 md:flex-row md:items-center md:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-mono text-[0.9rem] font-semibold text-[color:var(--gv-fg0)]">{{ $meeting->name }}</span>
                                        <span @class([
                                            'rounded-sm px-1.5 py-0.5 font-mono text-[0.6rem] font-semibold uppercase tracking-[0.14em]',
                                            'status-active' => $meeting->status === \App\Models\SprintPlanningMeeting::STATUS_ACTIVE,
                                            'status-ended' => $meeting->status === \App\Models\SprintPlanningMeeting::STATUS_COMPLETED,
                                            'status-planned' => $meeting->status === \App\Models\SprintPlanningMeeting::STATUS_SCHEDULED,
                                        ])>{{ $meeting->status }}</span>
                                    </div>
                                    <div class="mt-1 text-xs text-[color:var(--gv-fg4)]">
                                        {{ $meeting->scheduled_at->format('M j, Y g:i A') }} · {{ $meeting->story_points_limit }} pt cap · facilitator {{ $meeting->facilitator->name }}
                                    </div>
                                </div>

                                <x-mary-button wire:click="$set('meetingId', {{ $meeting->id }})" label="{{ $meeting->status === \App\Models\SprintPlanningMeeting::STATUS_COMPLETED ? 'View' : 'Join' }}" class="btn-sm btn-primary" />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="gv-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-[color:var(--gv-border)] px-3 py-2">
                    <span class="text-sm font-semibold uppercase tracking-wide text-[color:var(--gv-amber)]">» sprint schedule</span>
                    <span class="app-chip">{{ $sprints->count() }} total</span>
                </div>

                @if($sprints->isEmpty())
                    <div class="px-3 py-8 text-center text-sm text-[color:var(--gv-fg4)]">no sprints yet</div>
                @else
                    <ul class="divide-y divide-[color:var(--gv-border)]">
                        @foreach($sprints as $sprint)
                            <li wire:key="sprint-{{ $sprint->id }}" class="px-3 py-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <a
                                        href="{{ route('kanban', ['project' => $sprint->project_id, 'sprint' => $sprint->id]) }}"
                                        wire:navigate
                                        class="font-mono text-[0.9rem] font-semibold text-[color:var(--gv-fg0)] transition hover:text-[color:var(--gv-amber)] hover:underline"
                                    >
                                        {{ $sprint->name }}
                                    </a>
                                    <span @class([
                                        'rounded-sm px-1.5 py-0.5 font-mono text-[0.6rem] font-semibold uppercase tracking-[0.14em]',
                                        'status-active' => $sprint->isActive(),
                                        'status-ended' => $sprint->ended_at,
                                        'status-planned' => ! $sprint->isActive() && ! $sprint->ended_at,
                                    ])>
                                        {{ $sprint->isActive() ? 'active' : ($sprint->ended_at ? 'ended' : 'planned') }}
                                    </span>
                                </div>
                                <div class="mt-1 font-mono text-[0.68rem] text-[color:var(--gv-fg4)]">{{ $sprint->starts_at?->format('M j') }} → {{ $sprint->ends_at?->format('M j, Y') }} · {{ $sprint->tasks_count }} {{ \Illuminate\Support\Str::plural('task', $sprint->tasks_count) }}</div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    @endif
</div>
