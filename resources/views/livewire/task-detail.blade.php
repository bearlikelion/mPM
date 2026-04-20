<div class="flex h-full w-full flex-col gap-4 p-1">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="font-mono text-xs text-neutral-500">{{ $task->project->name }} · {{ $task->key }}</div>
            <h1 class="text-2xl font-semibold">{{ $task->title }}</h1>
            @if($task->creator)
                <div class="mt-1 text-xs text-neutral-500">Created by {{ $task->creator->name }} · {{ $task->created_at->diffForHumans() }}</div>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 flex flex-col gap-4">
            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <div class="mb-2 text-sm font-medium">Description</div>
                <div class="prose prose-sm max-w-none whitespace-pre-wrap text-sm dark:prose-invert">
                    {{ $task->description ?: 'No description.' }}
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700">
                <div class="border-b border-neutral-200 px-4 py-3 text-sm font-medium dark:border-neutral-700">
                    Comments ({{ $task->comments->count() }})
                </div>

                <ul class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse($task->comments as $comment)
                        <li class="flex gap-3 px-4 py-3 text-sm">
                            <img src="{{ $comment->user?->avatarUrl() }}" alt="" class="h-8 w-8 rounded-full" />
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{ $comment->user?->name ?? 'Someone' }}</span>
                                    <span class="text-xs text-neutral-500">{{ $comment->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="mt-1 whitespace-pre-wrap">{{ $comment->body }}</div>
                            </div>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-center text-sm text-neutral-500">No comments yet.</li>
                    @endforelse
                </ul>

                <form wire:submit="addComment" class="border-t border-neutral-200 p-4 dark:border-neutral-700">
                    <textarea
                        wire:model="newComment"
                        rows="3"
                        placeholder="Write a comment…"
                        class="w-full rounded-md border-neutral-300 bg-white text-sm dark:border-neutral-700 dark:bg-neutral-900"
                    ></textarea>
                    @error('newComment')<div class="mt-1 text-xs text-red-600">{{ $message }}</div>@enderror
                    <div class="mt-2 flex justify-end">
                        <flux:button type="submit" variant="primary" size="sm">Comment</flux:button>
                    </div>
                </form>
            </div>
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
                    <option value="">—</option>
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
                            <li class="flex items-center gap-2 text-sm">
                                <img src="{{ $user->avatarUrl() }}" alt="" class="h-6 w-6 rounded-full" />
                                <span>{{ $user->name }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @if($task->epic || $task->sprint)
                <div class="rounded-xl border border-neutral-200 p-4 text-sm dark:border-neutral-700">
                    @if($task->epic)
                        <div><span class="text-neutral-500">Epic:</span> {{ $task->epic->name }}</div>
                    @endif
                    @if($task->sprint)
                        <div><span class="text-neutral-500">Sprint:</span> {{ $task->sprint->name }}</div>
                    @endif
                </div>
            @endif

            @if($task->tags->isNotEmpty())
                <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                    <div class="mb-2 text-xs uppercase tracking-wide text-neutral-500">Tags</div>
                    <div class="flex flex-wrap gap-1">
                        @foreach($task->tags as $tag)
                            <span class="rounded-full px-2 py-0.5 text-xs" style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </aside>
    </div>
</div>
