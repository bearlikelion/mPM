<div class="flex h-full w-full flex-col gap-4 p-1">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="font-mono text-xs text-neutral-500">
                <a href="{{ route('kanban', ['project' => $task->project_id]) }}" wire:navigate class="transition hover:text-amber-300">{{ $task->project->name }}</a>
                ·
                <a href="{{ route('kanban', ['project' => $task->project_id, 'task' => $task->key]) }}" wire:navigate class="transition hover:text-amber-300">{{ $task->key }}</a>
            </div>
            <h1 class="text-2xl font-semibold">{{ $task->title }}</h1>
            @if($task->creator)
                <div class="mt-1 text-xs text-neutral-500">
                    Created by <a href="{{ route('users.show', $task->creator) }}" wire:navigate class="transition hover:text-amber-300">{{ $task->creator->name }}</a> · {{ auth()->user()->formatLocalTime($task->created_at) }} · {{ $task->created_at->diffForHumans() }}
                </div>
            @endif
        </div>
        @if(! $embedded)
            <a href="{{ route('kanban', ['project' => $task->project_id, 'highlight' => $task->id, 'task' => $task->key]) }}" wire:navigate class="rounded-md border border-neutral-300 px-3 py-1.5 text-sm hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-800">
                Open in kanban
            </a>
        @endif
    </div>

    @if($task->blockedTasks->isNotEmpty() || $task->blockers->isNotEmpty())
        <div class="grid gap-2">
            @if($task->blockedTasks->isNotEmpty())
                @php
                    $firstBlockedTask = $task->blockedTasks->first();
                    $remainingBlockedTasks = $task->blockedTasks->count() - 1;
                @endphp
                <div class="flex items-center gap-2 rounded-xl border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-100">
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-red-500/20 font-mono text-xs font-bold text-red-200">!</span>
                    <span>
                        This task is blocking {{ $firstBlockedTask->title }}
                        @if($remainingBlockedTasks > 0)
                            <span class="text-red-200/80">+{{ $remainingBlockedTasks }} more</span>
                        @endif
                    </span>
                </div>
            @endif

            @if($task->blockers->isNotEmpty())
                @php
                    $firstBlocker = $task->blockers->first();
                    $remainingBlockers = $task->blockers->count() - 1;
                @endphp
                <div class="flex items-center gap-2 rounded-xl border border-amber-400/30 bg-amber-400/10 px-3 py-2 text-sm text-amber-100">
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-md bg-amber-400/20 font-mono text-xs font-bold text-amber-200">!</span>
                    <span>
                        Blocked by
                        <a href="{{ route('tasks.show', $firstBlocker->key) }}" wire:navigate class="font-medium underline underline-offset-2 transition hover:text-amber-50">
                            {{ $firstBlocker->title }}
                        </a>
                        @if($remainingBlockers > 0)
                            <span class="text-amber-200/80">+{{ $remainingBlockers }} more</span>
                        @endif
                    </span>
                </div>
            @endif
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 flex flex-col gap-4">
            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <div class="mb-2 text-sm font-medium">Description</div>
                <div class="prose prose-sm max-w-none whitespace-pre-wrap text-sm dark:prose-invert">
                    {{ $task->description ?: 'No description.' }}
                </div>
            </div>

            @if($task->comments->isNotEmpty())
                <div class="rounded-xl border border-neutral-200 dark:border-neutral-700">
                    <div class="border-b border-neutral-200 px-4 py-3 text-sm font-medium dark:border-neutral-700">
                        Comments ({{ $task->comments->count() }})
                    </div>
                    <ul class="divide-y divide-neutral-200 dark:divide-neutral-700">
                        @foreach($task->comments as $comment)
                            <li class="flex gap-3 px-4 py-3 text-sm">
                                @if($comment->user)
                                    <a href="{{ route('users.show', $comment->user) }}" wire:navigate class="shrink-0">
                                        <img src="{{ $comment->user->avatarUrl() }}" alt="{{ $comment->user->name }}" class="h-8 w-8 rounded-full" />
                                    </a>
                                @else
                                    <span class="h-8 w-8 rounded-full bg-neutral-800"></span>
                                @endif
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        @if($comment->user)
                                            <a href="{{ route('users.show', $comment->user) }}" wire:navigate class="font-medium transition hover:text-amber-300">{{ $comment->user->name }}</a>
                                        @else
                                            <span class="font-medium">Someone</span>
                                        @endif
                                        <span class="text-xs text-neutral-500">{{ auth()->user()->formatLocalTime($comment->created_at) }} · {{ $comment->created_at->diffForHumans() }}</span>
                                    </div>
                                    @if($comment->body)
                                        <div class="mt-1 whitespace-pre-wrap">{{ $comment->body }}</div>
                                    @endif
                                    @if($comment->getMedia('attachments')->isNotEmpty())
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach($comment->getMedia('attachments') as $media)
                                                <a href="{{ $media->getUrl() }}" target="_blank" class="flex items-center gap-1 rounded-md border border-neutral-200 bg-neutral-50 px-2 py-1 text-xs hover:bg-neutral-100 dark:border-neutral-700 dark:bg-neutral-800 dark:hover:bg-neutral-700">
                                                    <flux:icon name="paper-clip" class="size-3" />
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

            <form wire:submit="addComment" class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <div class="mb-2 text-sm font-medium">Add comment</div>
                <textarea
                    wire:model="newComment"
                    rows="3"
                    placeholder="Write a comment…"
                    class="w-full rounded-md border-neutral-300 bg-white text-sm dark:border-neutral-700 dark:bg-neutral-900"
                ></textarea>
                @error('newComment')<div class="mt-1 text-xs text-red-600">{{ $message }}</div>@enderror

                <div class="mt-2 flex items-center justify-between gap-2">
                    <label class="flex cursor-pointer items-center gap-2 text-xs text-neutral-600 dark:text-neutral-400">
                        <flux:icon name="paper-clip" class="size-4" />
                        <span>Attach files</span>
                        <input type="file" wire:model="attachments" multiple class="hidden" />
                    </label>
                    <flux:button type="submit" variant="primary" size="sm">Comment</flux:button>
                </div>

                @if(! empty($attachments))
                    <ul class="mt-2 flex flex-wrap gap-2">
                        @foreach($attachments as $file)
                            <li class="rounded bg-neutral-100 px-2 py-1 text-xs dark:bg-neutral-800">{{ $file->getClientOriginalName() }}</li>
                        @endforeach
                    </ul>
                @endif
                @error('attachments.*')<div class="mt-1 text-xs text-red-600">{{ $message }}</div>@enderror
            </form>
        </div>

        <aside class="flex flex-col gap-3">
            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <div class="mb-2 text-xs uppercase tracking-wide text-neutral-500">Status</div>
                <select wire:model.live="status" wire:change="updateField('status')" class="w-full rounded-md border-neutral-300 bg-white text-sm dark:border-neutral-700 dark:bg-neutral-900">
                    @foreach(\App\Models\Task::STATUSES as $s)
                        <option value="{{ $s }}">{{ $s }}</option>
                    @endforeach
                </select>

                <div class="mt-3 mb-2 text-xs uppercase tracking-wide text-neutral-500">Priority</div>
                <select wire:model.live="priority" wire:change="updateField('priority')" class="w-full rounded-md border-neutral-300 bg-white text-sm dark:border-neutral-700 dark:bg-neutral-900">
                    @foreach(\App\Models\Task::PRIORITIES as $p)
                        <option value="{{ $p }}">{{ $p }}</option>
                    @endforeach
                </select>

                <div class="mt-3 mb-2 text-xs uppercase tracking-wide text-neutral-500">Story points</div>
                <select wire:model.live="storyPoints" wire:change="updateField('storyPoints')" class="w-full rounded-md border-neutral-300 bg-white text-sm dark:border-neutral-700 dark:bg-neutral-900">
                    <option value="">-</option>
                    @foreach(\App\Models\Task::STORY_POINTS as $pt)
                        <option value="{{ $pt }}">{{ $pt }}</option>
                    @endforeach
                </select>
            </div>

            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <div class="mb-2 text-xs uppercase tracking-wide text-neutral-500">Assignees</div>
                @if($task->assignees->isEmpty())
                    <div class="text-sm text-neutral-500">Unassigned</div>
                @else
                    <ul class="flex flex-col gap-2">
                        @foreach($task->assignees as $user)
                            <li class="flex items-center justify-between gap-3">
                                <a
                                    href="{{ route('users.show', $user) }}"
                                    wire:navigate
                                    class="flex items-center gap-2 rounded-md px-1 py-0.5 text-sm hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                >
                                    <img src="{{ $user->avatarUrl() }}" alt="" class="h-6 w-6 rounded-full" />
                                    <span>{{ $user->name }}</span>
                                </a>
                                <a href="{{ route('kanban', ['project' => $task->project_id, 'assignee' => $user->id]) }}" wire:navigate class="text-xs text-neutral-500 transition hover:text-amber-300">
                                    Filter board
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @if($task->epic || $task->sprint)
                <div class="rounded-xl border border-neutral-200 p-4 text-sm dark:border-neutral-700">
                    @if($task->epic)
                        <div class="flex items-center gap-2">
                            <span class="text-neutral-500">Epic:</span>
                            <a href="{{ route('kanban', ['project' => $task->project_id, 'epic' => $task->epic_id]) }}" wire:navigate class="flex items-center gap-1 text-indigo-600 hover:underline dark:text-indigo-400">
                                <img src="{{ $task->epic->avatarUrl() }}" alt="" class="h-4 w-4 rounded" />
                                {{ $task->epic->name }}
                            </a>
                        </div>
                    @endif
                    @if($task->sprint)
                        <div class="mt-1 flex items-center gap-2">
                            <span class="text-neutral-500">Sprint:</span>
                            <a href="{{ route('kanban', ['project' => $task->project_id, 'sprint' => $task->sprint_id]) }}" wire:navigate class="text-indigo-600 hover:underline dark:text-indigo-400">
                                {{ $task->sprint->name }}
                            </a>
                        </div>
                    @endif
                </div>
            @endif

            @if($task->tags->isNotEmpty())
                <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                    <div class="mb-2 text-xs uppercase tracking-wide text-neutral-500">Tags</div>
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

            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <div class="mb-2 text-xs uppercase tracking-wide text-neutral-500">Blocked by</div>
                <select
                    wire:model.live="blockerIds"
                    multiple
                    class="min-h-32 w-full rounded-md border-neutral-300 bg-white text-sm dark:border-neutral-700 dark:bg-neutral-900"
                >
                    @foreach($blockerOptions as $option)
                        <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
                <div class="mt-2 text-xs text-neutral-500">
                    Select one or more tasks in this project that are blocking this work.
                </div>
                @error('blockerIds.*')<div class="mt-1 text-xs text-red-600">{{ $message }}</div>@enderror
            </div>

            @if($task->blockedTasks->isNotEmpty())
                <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                    <div class="mb-2 text-xs uppercase tracking-wide text-neutral-500">Blocking</div>
                    <ul class="flex flex-col gap-2 text-sm">
                        @foreach($task->blockedTasks as $blockedTask)
                            <li>
                                <a href="{{ route('tasks.show', $blockedTask->key) }}" wire:navigate class="transition hover:text-amber-300">
                                    {{ $blockedTask->key }} · {{ $blockedTask->title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </aside>
    </div>
</div>
