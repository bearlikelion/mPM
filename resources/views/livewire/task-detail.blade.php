<div class="flex h-full w-full flex-col gap-4 p-1">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <div class="font-mono text-xs text-[color:var(--gv-fg4)]">
                <a href="{{ route('kanban', ['project' => $task->project_id]) }}" wire:navigate class="transition hover:text-[color:var(--gv-amber)]">{{ $task->project->name }}</a>
                ·
                <a href="{{ route('kanban', ['project' => $task->project_id, 'task' => $task->key]) }}" wire:navigate class="transition hover:text-[color:var(--gv-amber)]">{{ $task->key }}</a>
            </div>
            <div class="mt-1 flex items-start gap-2">
                @if($task->blockedTasks->isNotEmpty())
                    @php($blockingNames = $task->blockedTasks->take(3)->pluck('title')->implode(', '))
                    <span class="tooltip" data-tip="Blocking: {{ $blockingNames }}{{ $task->blockedTasks->count() > 3 ? ' +'.($task->blockedTasks->count() - 3).' more' : '' }}">
                        <x-mary-icon name="o-no-symbol" class="h-5 w-5 text-[color:var(--gv-red)]" />
                    </span>
                @elseif($task->blockers->isNotEmpty())
                    @php($blockedByNames = $task->blockers->take(3)->pluck('title')->implode(', '))
                    <span class="tooltip" data-tip="Blocked by: {{ $blockedByNames }}{{ $task->blockers->count() > 3 ? ' +'.($task->blockers->count() - 3).' more' : '' }}">
                        <x-mary-icon name="o-lock-closed" class="h-5 w-5 text-[color:var(--gv-orange)]" />
                    </span>
                @endif
                <h1 class="text-2xl font-semibold text-[color:var(--gv-fg0)]">{{ $task->title }}</h1>
            </div>
            @if($task->creator)
                <div class="mt-1 text-xs text-[color:var(--gv-fg4)]">
                    Created by <a href="{{ route('users.show', $task->creator) }}" wire:navigate class="transition hover:text-[color:var(--gv-amber)]">{{ $task->creator->name }}</a> · {{ auth()->user()->formatLocalTime($task->created_at) }} · {{ $task->created_at->diffForHumans() }}
                </div>
            @endif
        </div>
        @if(! $embedded)
            <a href="{{ route('kanban', ['project' => $task->project_id, 'highlight' => $task->id]) }}" wire:navigate class="btn btn-sm">
                Open in kanban
            </a>
        @endif
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="flex flex-col gap-3 lg:col-span-2">
            <div class="gv-card p-4">
                <div class="mb-2 text-sm font-medium text-[color:var(--gv-fg2)]">Description</div>
                <x-tiptap-content :html="$task->description" empty="No description." />
            </div>

            @if($task->comments->isNotEmpty())
                <div class="gv-card overflow-hidden">
                    <div class="border-b border-[color:var(--gv-border)] px-4 py-3 text-sm font-medium">
                        Comments ({{ $task->comments->count() }})
                    </div>
                    <ul class="divide-y divide-[color:var(--gv-border)]">
                        @foreach($task->comments as $comment)
                            <li class="flex gap-3 px-4 py-3 text-sm">
                                @if($comment->user)
                                    <a href="{{ route('users.show', $comment->user) }}" wire:navigate class="shrink-0">
                                        <img src="{{ $comment->user->avatarUrl() }}" alt="{{ $comment->user->name }}" class="h-8 w-8 rounded-full" />
                                    </a>
                                @else
                                    <span class="h-8 w-8 rounded-full bg-[color:var(--gv-bg1)]"></span>
                                @endif
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        @if($comment->user)
                                            <a href="{{ route('users.show', $comment->user) }}" wire:navigate class="font-medium transition hover:text-[color:var(--gv-amber)]">{{ $comment->user->name }}</a>
                                        @else
                                            <span class="font-medium">Someone</span>
                                        @endif
                                        <span class="text-xs text-[color:var(--gv-fg4)]">{{ auth()->user()->formatLocalTime($comment->created_at) }} · {{ $comment->created_at->diffForHumans() }}</span>
                                    </div>
                                    @if($comment->body)
                                        <x-tiptap-content :html="$comment->body" class="mt-1 prose-tiptap" />
                                    @endif
                                    @if($comment->getMedia('attachments')->isNotEmpty())
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach($comment->getMedia('attachments') as $media)
                                                <a href="{{ $media->getUrl() }}" target="_blank" class="flex items-center gap-1 rounded-md border border-[color:var(--gv-border)] bg-[color:var(--gv-bg1)] px-2 py-1 text-xs hover:bg-[color:var(--gv-bg2)]">
                                                    <x-mary-icon name="o-paper-clip" class="h-3 w-3" />
                                                    {{ $media->name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form wire:submit="addComment" class="gv-card p-4">
                <div class="mb-2 text-sm font-medium text-[color:var(--gv-fg2)]">Add comment</div>
                <x-tiptap-editor wire:model="newComment" rows="3" placeholder="Write a comment..." />

                <div class="mt-2 flex items-center justify-between gap-2">
                    <label class="flex cursor-pointer items-center gap-2 text-xs text-[color:var(--gv-fg4)]">
                        <x-mary-icon name="o-paper-clip" class="h-4 w-4" />
                        <span>Attach files</span>
                        <input type="file" wire:model="attachments" multiple class="hidden" />
                    </label>
                    <x-mary-button type="submit" class="btn btn-primary btn-sm" label="Comment" />
                </div>

                @if(! empty($attachments))
                    <ul class="mt-2 flex flex-wrap gap-2">
                        @foreach($attachments as $file)
                            <li class="rounded bg-[color:var(--gv-bg1)] px-2 py-1 text-xs">{{ $file->getClientOriginalName() }}</li>
                        @endforeach
                    </ul>
                @endif
                @error('attachments.*')<div class="mt-1 text-xs text-[color:var(--gv-red)]">{{ $message }}</div>@enderror
            </form>
        </div>

        <aside class="flex flex-col gap-3">
            <div class="gv-card p-4">
                <x-mary-select
                    wire:model.live="status"
                    wire:change="updateField('status')"
                    label="Status"
                    :options="collect(\App\Models\Task::STATUSES)->map(fn ($s) => ['id' => $s, 'name' => str_replace('_', ' ', $s)])->all()"
                />

                <div class="mt-3">
                    <x-mary-select
                        wire:model.live="priority"
                        wire:change="updateField('priority')"
                        label="Priority"
                        :options="collect(\App\Models\Task::PRIORITIES)->map(fn ($p) => ['id' => $p, 'name' => $p])->all()"
                    />
                </div>

                <div class="mt-3">
                    <x-mary-select
                        wire:model.live="storyPoints"
                        wire:change="updateField('storyPoints')"
                        label="Story points"
                        placeholder="-"
                        placeholder-value=""
                        :options="collect(\App\Models\Task::STORY_POINTS)->map(fn ($p) => ['id' => $p, 'name' => (string) $p])->all()"
                    />
                </div>
            </div>

            <div class="gv-card p-4">
                <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-[color:var(--gv-fg4)]">Assignees</div>
                @if($task->assignees->isEmpty())
                    <div class="text-sm text-[color:var(--gv-fg4)]">Unassigned</div>
                @else
                    <ul class="flex flex-col gap-2">
                        @foreach($task->assignees as $user)
                            <li class="flex items-center justify-between gap-3">
                                <a
                                    href="{{ route('users.show', $user) }}"
                                    wire:navigate
                                    class="flex min-w-0 items-center gap-2 rounded px-1 py-0.5 text-sm hover:bg-[color:var(--gv-bg1)]"
                                >
                                    <img src="{{ $user->avatarUrl() }}" alt="" class="h-6 w-6 rounded-full" />
                                    <span class="truncate">{{ $user->name }}</span>
                                </a>
                                <a href="{{ route('kanban', ['project' => $task->project_id, 'assignee' => $user->id]) }}" wire:navigate class="shrink-0 text-xs text-[color:var(--gv-fg4)] transition hover:text-[color:var(--gv-amber)]">
                                    Filter board
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @if($task->epic || $task->sprint)
                <div class="gv-card p-4 text-sm">
                    @if($task->epic)
                        <div class="flex items-center gap-2">
                            <span class="text-[color:var(--gv-fg4)]">Epic:</span>
                            <a href="{{ route('epics.show', $task->epic) }}" wire:navigate class="flex items-center gap-1 text-[color:var(--gv-blue)] hover:underline">
                                <img src="{{ $task->epic->avatarUrl() }}" alt="" class="h-4 w-4 rounded" />
                                {{ $task->epic->name }}
                            </a>
                        </div>
                    @endif
                    @if($task->sprint)
                        <div class="mt-1 flex items-center gap-2">
                            <span class="text-[color:var(--gv-fg4)]">Sprint:</span>
                            <a href="{{ route('kanban', ['project' => $task->project_id, 'sprint' => $task->sprint_id]) }}" wire:navigate class="text-[color:var(--gv-blue)] hover:underline">
                                {{ $task->sprint->name }}
                            </a>
                        </div>
                    @endif
                </div>
            @endif

            @if($task->tags->isNotEmpty())
                <div class="gv-card p-4">
                    <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-[color:var(--gv-fg4)]">Tags</div>
                    <div class="flex flex-wrap gap-1">
                        @foreach($task->tags as $tag)
                            <a
                                href="{{ route('kanban', ['project' => $task->project_id, 'tag' => $tag->id]) }}"
                                wire:navigate
                                class="rounded-full px-2 py-0.5 text-xs hover:opacity-80"
                                style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}"
                            >
                                {{ $tag->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="gv-card p-4">
                <x-mary-choices-offline
                    wire:model.live="blockerIds"
                    :options="$blockerOptions"
                    label="Blocked by"
                    searchable
                    clearable
                    hint="Select tasks in this project that block this work."
                />
                @error('blockerIds.*')<div class="mt-1 text-xs text-[color:var(--gv-red)]">{{ $message }}</div>@enderror

                @if($task->blockers->isNotEmpty())
                    <ul class="mt-3 flex flex-col gap-1.5 text-sm">
                        @foreach($task->blockers as $blocker)
                            <li class="flex items-center gap-2">
                                <x-mary-icon name="o-lock-closed" class="h-4 w-4 shrink-0 text-[color:var(--gv-orange)]" />
                                <a href="{{ route('tasks.show', $blocker->key) }}" wire:navigate class="min-w-0 truncate transition hover:text-[color:var(--gv-amber)]">
                                    <span class="font-mono text-xs text-[color:var(--gv-fg4)]">{{ $blocker->key }}</span>
                                    <span>{{ $blocker->title }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @if($task->blockedTasks->isNotEmpty())
                <div class="gv-card p-4">
                    <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-[color:var(--gv-fg4)]">Blocking</div>
                    <ul class="flex flex-col gap-1.5 text-sm">
                        @foreach($task->blockedTasks as $blockedTask)
                            <li class="flex items-center gap-2">
                                <x-mary-icon name="o-no-symbol" class="h-4 w-4 shrink-0 text-[color:var(--gv-red)]" />
                                <a href="{{ route('tasks.show', $blockedTask->key) }}" wire:navigate class="min-w-0 truncate transition hover:text-[color:var(--gv-amber)]">
                                    <span class="font-mono text-xs text-[color:var(--gv-fg4)]">{{ $blockedTask->key }}</span>
                                    <span>{{ $blockedTask->title }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </aside>
    </div>
</div>
