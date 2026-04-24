<section
    wire:poll.10s="refreshMeeting"
    x-data="{
        activeUsers: @js($attendanceUsers),
        socketState: window.Echo ? 'pending' : 'unavailable',
        upsertUser(user) {
            this.activeUsers = [
                ...this.activeUsers.filter((active) => active.id !== user.id),
                user,
            ];
        },
        joinPresence() {
            if (! window.Echo) {
                this.socketState = 'unavailable';

                return;
            }

            window.Echo.join('sprint-planning.{{ $meeting->id }}')
                .here((users) => {
                    this.activeUsers = users.length ? users : this.activeUsers;
                    this.socketState = 'live';
                })
                .joining((user) => {
                    this.upsertUser(user);
                    this.socketState = 'live';
                })
                .leaving((user) => this.activeUsers = this.activeUsers.filter((active) => active.id !== user.id))
                .error((error) => {
                    this.socketState = 'error';
                    console.error(error);
                });
        },
    }"
    x-init="joinPresence()"
    class="grid gap-4"
>
    <div class="gv-card overflow-hidden">
        <div class="flex flex-col gap-4 border-b border-[color:var(--gv-border)] px-4 py-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-[color:var(--gv-amber)]">Sprint planning room</div>
                <h2 class="mt-1 text-xl font-semibold text-[color:var(--gv-fg0)]">{{ $meeting->name }}</h2>
                <div class="mt-1 text-sm text-[color:var(--gv-fg4)]">
                    {{ $meeting->project->name }} · facilitated by {{ $meeting->facilitator->name }} · {{ $meeting->scheduled_at->format('M j, Y g:i A') }}
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="flex -space-x-2" title="Active users">
                    <template x-for="user in activeUsers" :key="user.id">
                        <img :src="user.avatar" :alt="user.name" class="h-9 w-9 rounded-full border-2 border-[color:var(--gv-bg0)] bg-[color:var(--gv-bg1)]" />
                    </template>
                </div>

                <span class="app-chip" x-text="`${activeUsers.length} in attendance`">{{ $participants->count() }} in attendance</span>
                <span
                    class="app-chip"
                    x-bind:class="{
                        'border-emerald-500/40 text-emerald-300': socketState === 'live',
                        'border-amber-500/40 text-amber-300': socketState === 'pending',
                        'border-red-500/40 text-red-300': socketState === 'error',
                    }"
                    x-text="socketState === 'live' ? 'realtime live' : (socketState === 'error' ? 'realtime error' : (socketState === 'unavailable' ? 'realtime unavailable' : 'realtime pending'))"
                >realtime pending</span>
                <span class="app-chip">{{ $plannedPoints }} / {{ $meeting->story_points_limit }} pts</span>
            </div>
        </div>

        <div class="px-4 py-4">
            <progress class="progress h-3 w-full" max="{{ $meeting->story_points_limit }}" value="{{ $plannedPoints }}"></progress>
        </div>
    </div>

    @if($meeting->status === \App\Models\SprintPlanningMeeting::STATUS_SCHEDULED)
        <div class="gv-card p-6 text-center">
            <div class="mx-auto max-w-2xl">
                <h3 class="text-lg font-semibold text-[color:var(--gv-fg0)]">Waiting for everyone to connect</h3>
                <p class="mt-2 text-sm text-[color:var(--gv-fg4)]">Users who open this page join the planning roster. The facilitator starts the meeting when the room is ready.</p>

                @if($isFacilitator)
                    <x-mary-button wire:click="begin" label="Begin sprint planning" spinner="begin" class="btn-primary mt-5" />
                @endif
            </div>
        </div>
    @elseif($meeting->status === \App\Models\SprintPlanningMeeting::STATUS_COMPLETED)
        <div class="gv-card p-6 text-center">
            <h3 class="text-lg font-semibold text-[color:var(--gv-fg0)]">Planning completed</h3>
            @if($meeting->sprint_id)
                <p class="mt-2 text-sm text-[color:var(--gv-fg4)]">Created sprint #{{ $meeting->sprint_id }} with {{ $plannedPoints }} story points.</p>
            @endif
        </div>
    @else
        @if($currentItem)
            <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_22rem]">
                <article class="gv-card overflow-hidden">
                    <div class="border-b border-[color:var(--gv-border)] px-5 py-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-xs text-[color:var(--gv-fg4)]">{{ $currentItem->task->key }}</span>
                            <span class="priority-{{ $currentItem->task->priority }} rounded-sm px-1.5 py-0.5 font-mono text-[0.65rem] font-semibold uppercase tracking-[0.12em]">{{ $currentItem->task->priority }}</span>
                        </div>
                        <h3 class="mt-3 text-3xl font-semibold leading-tight text-[color:var(--gv-fg0)]">{{ $currentItem->task->title }}</h3>
                    </div>

                    <div class="px-5 py-5">
                        <div class="mb-2 text-xs font-semibold uppercase tracking-[0.16em] text-[color:var(--gv-amber)]">Card description</div>
                        <div class="min-h-56 whitespace-pre-wrap rounded-2xl border border-[color:var(--gv-border)] bg-[color:var(--gv-bg1)] p-5 text-base leading-7 text-[color:var(--gv-fg1)]">
                            {{ $currentItem->task->description ?: 'No description yet.' }}
                        </div>
                    </div>
                </article>

                <aside class="grid gap-4">
                    <div class="gv-card p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-[0.16em] text-[color:var(--gv-amber)]">Story points</div>
                                <p class="mt-1 text-xs text-[color:var(--gv-fg4)]">Estimate complexity, uncertainty, and effort relative to other cards.</p>
                            </div>

                            <flux:modal.trigger name="story-points-help">
                                <flux:button size="xs" variant="subtle">What are story points?</flux:button>
                            </flux:modal.trigger>
                        </div>

                        @if($currentItem->status === \App\Models\SprintPlanningItem::STATUS_VOTING)
                            <div class="mt-4 grid grid-cols-4 gap-2">
                                @foreach($storyPointOptions as $points)
                                    <button
                                        type="button"
                                        wire:click="vote({{ $points }})"
                                        @class([
                                            'rounded-xl border px-3 py-3 text-center font-mono text-lg font-semibold transition',
                                            'border-[color:var(--gv-amber)] bg-amber-500/15 text-[color:var(--gv-amber)]' => $storyPoints === $points,
                                            'border-[color:var(--gv-border)] bg-[color:var(--gv-bg1)] text-[color:var(--gv-fg1)] hover:border-[color:var(--gv-amber)]' => $storyPoints !== $points,
                                        ])
                                    >
                                        {{ $points }}
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <div class="mt-4 rounded-xl border border-[color:var(--gv-border)] bg-[color:var(--gv-bg1)] p-4 text-center">
                                <div class="text-xs uppercase tracking-[0.16em] text-[color:var(--gv-fg4)]">Selected estimate</div>
                                <div class="mt-1 font-mono text-4xl font-semibold text-[color:var(--gv-amber)]">{{ $currentItem->selected_story_points ?? '—' }}</div>
                            </div>
                        @endif

                        @if($voteSummary->isNotEmpty())
                            <div class="mt-4 space-y-2">
                                @foreach($voteSummary as $summary)
                                    <div class="flex items-center justify-between rounded-lg border border-[color:var(--gv-border)] px-3 py-2 text-sm">
                                        <span>{{ $summary['points'] }} pts</span>
                                        <span class="font-mono text-[color:var(--gv-fg4)]">{{ $summary['votes'] }} vote{{ $summary['votes'] === 1 ? '' : 's' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if($isFacilitator && $currentItem->status === \App\Models\SprintPlanningItem::STATUS_VOTING && $tieOptions->count() > 1)
                            <div class="mt-4 rounded-xl border border-amber-500/30 bg-amber-500/10 p-3">
                                <div class="text-sm font-semibold text-amber-200">Tie detected</div>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach($tieOptions as $points)
                                        <x-mary-button wire:click="resolveTie({{ $points }})" label="{{ $points }} pts" class="btn-sm btn-warning" />
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="gv-card p-4">
                        <div class="text-xs font-semibold uppercase tracking-[0.16em] text-[color:var(--gv-amber)]">Decision</div>

                        <div class="mt-4 grid gap-2">
                            @if($currentItem->status === \App\Models\SprintPlanningItem::STATUS_ESTIMATED)
                                <x-mary-button wire:click="claim" label="Assign to me" spinner="claim" class="btn-primary" />
                            @endif

                            @if($isFacilitator)
                                <x-mary-button wire:click="delay" label="Delay" spinner="delay" class="btn-outline btn-sm" />
                                <x-mary-button wire:click="backlog" label="Place in backlog" spinner="backlog" class="btn-outline btn-sm" />
                            @endif
                        </div>
                    </div>
                </aside>
            </div>
        @else
            <div class="gv-card p-6 text-center">
                <h3 class="text-lg font-semibold text-[color:var(--gv-fg0)]">No more cards in this planning queue</h3>
                <p class="mt-2 text-sm text-[color:var(--gv-fg4)]">Complete planning to create the sprint with the claimed tasks.</p>

                @if($isFacilitator)
                    <x-mary-button wire:click="complete" label="Create sprint and end meeting" spinner="complete" class="btn-primary mt-5" />
                @endif
            </div>
        @endif
    @endif

    <flux:modal name="story-points-help" class="md:w-[32rem]">
        <div class="space-y-4">
            <flux:heading size="lg">What are story points?</flux:heading>
            <p class="text-sm leading-6 text-[color:var(--gv-fg2)]">Story points are a relative estimate of work. They combine complexity, uncertainty, and effort instead of promising exact hours.</p>
            <p class="text-sm leading-6 text-[color:var(--gv-fg2)]">Use the Fibonacci-style scale so bigger, riskier work naturally spreads out. A 5 should feel meaningfully larger than a 3, and a 13 should trigger discussion about splitting the task.</p>
        </div>
    </flux:modal>
</section>
