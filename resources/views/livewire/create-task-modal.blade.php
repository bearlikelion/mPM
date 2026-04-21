<div>
    <flux:modal name="create-task-modal" flyout variant="floating" class="w-full max-w-3xl">
        <form wire:submit="createTask" class="space-y-6">
            <div class="create-task-hero rounded-[1.65rem] px-5 py-5 sm:px-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="app-eyebrow">Task Intake</div>
                        <flux:heading size="lg" class="mt-2 text-neutral-50">Create new task</flux:heading>
                        <flux:subheading class="mt-2 max-w-2xl text-neutral-300">
                            Capture work from anywhere, assign owners immediately, and drop it into the right project context.
                        </flux:subheading>
                    </div>

                    @if($selectedProject)
                        <div class="create-task-project-badge">
                            <img src="{{ $selectedProject->avatarUrl() }}" alt="" class="h-11 w-11 rounded-2xl border border-neutral-700/70 bg-neutral-900 object-cover" />
                            <div class="min-w-0">
                                <div class="app-eyebrow">Target Project</div>
                                <div class="mt-1 truncate text-base font-semibold text-neutral-50">{{ $selectedProject->name }}</div>
                                <div class="text-sm text-neutral-400">{{ $selectedProject->key }}</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="grid gap-5 xl:grid-cols-[1.1fr_0.9fr]">
                <div class="space-y-5">
                    <div class="app-panel-muted rounded-[1.4rem] p-4 sm:p-5">
                        <div class="mb-4">
                            <div class="app-eyebrow">Core</div>
                            <div class="mt-1 text-lg font-semibold text-neutral-50">Task framing</div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <flux:select wire:model.live="projectId" label="Project">
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </flux:select>

                            <flux:input wire:model="title" label="Title" placeholder="Investigate login handoff" />
                        </div>

                        <div class="mt-4">
                            <flux:textarea wire:model="description" rows="6" label="Description" placeholder="Describe the work, blockers, or expected outcome." />
                        </div>
                    </div>

                    <div class="app-panel-muted rounded-[1.4rem] p-4 sm:p-5">
                        <div class="mb-4">
                            <div class="app-eyebrow">Execution</div>
                            <div class="mt-1 text-lg font-semibold text-neutral-50">Priority and schedule</div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <flux:select wire:model="priority" label="Priority">
                                @foreach(\App\Models\Task::PRIORITIES as $priority)
                                    <option value="{{ $priority }}">{{ str($priority)->upper() }}</option>
                                @endforeach
                            </flux:select>

                            <flux:select wire:model="status" label="Status">
                                @foreach(\App\Models\Task::STATUSES as $status)
                                    <option value="{{ $status }}">{{ str($status)->replace('_', ' ')->title() }}</option>
                                @endforeach
                            </flux:select>

                            <flux:select wire:model="storyPoints" label="Story points">
                                <option value="">None</option>
                                @foreach(\App\Models\Task::STORY_POINTS as $points)
                                    <option value="{{ $points }}">{{ $points }}</option>
                                @endforeach
                            </flux:select>

                            <flux:input wire:model="dueDate" type="date" label="Due date" />
                        </div>
                    </div>
                </div>

                <div class="space-y-5">
                    <div class="app-panel-muted rounded-[1.4rem] p-4 sm:p-5">
                        <div class="mb-4">
                            <div class="app-eyebrow">Placement</div>
                            <div class="mt-1 text-lg font-semibold text-neutral-50">Epic and sprint</div>
                        </div>

                        <div class="grid gap-4">
                            <flux:select wire:model="epicId" label="Epic">
                                <option value="">No epic</option>
                                @foreach($epics as $epic)
                                    <option value="{{ $epic->id }}">{{ $epic->name }}</option>
                                @endforeach
                            </flux:select>

                            <flux:select wire:model="sprintId" label="Sprint">
                                <option value="">No sprint</option>
                                @foreach($sprints as $sprint)
                                    <option value="{{ $sprint->id }}">{{ $sprint->name }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                    </div>

                    <div class="app-panel-muted rounded-[1.4rem] p-4 sm:p-5">
                        <div class="mb-4 flex items-start justify-between gap-3">
                            <div>
                                <div class="app-eyebrow">Ownership</div>
                                <div class="mt-1 text-lg font-semibold text-neutral-50">Assign teammates</div>
                            </div>

                            <span class="app-chip">{{ count($assigneeIds) }} selected</span>
                        </div>

                        @if($assignees->isEmpty())
                            <div class="rounded-2xl border border-dashed border-neutral-700/70 bg-neutral-950/35 px-4 py-8 text-center text-sm text-neutral-500">
                                No teammates available for this project yet.
                            </div>
                        @else
                            <div class="grid gap-3">
                                @foreach($assignees as $assignee)
                                    <button
                                        type="button"
                                        wire:click="toggleAssignee({{ $assignee->id }})"
                                        @class([
                                            'create-task-assignee flex items-center gap-3 rounded-2xl px-3 py-3 text-left transition',
                                            'is-selected' => in_array($assignee->id, $assigneeIds, true),
                                        ])
                                    >
                                        <img src="{{ $assignee->avatarUrl() }}" alt="{{ $assignee->name }}" class="h-11 w-11 rounded-2xl border border-neutral-700/70 bg-neutral-900 object-cover" />
                                        <div class="min-w-0 flex-1">
                                            <div class="truncate font-semibold text-neutral-50">{{ $assignee->name }}</div>
                                            <div class="truncate text-sm text-neutral-400">{{ $assignee->email }}</div>
                                        </div>
                                        <div class="shrink-0 rounded-full border border-neutral-700/70 px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-neutral-400">
                                            {{ in_array($assignee->id, $assigneeIds, true) ? 'Added' : 'Pick' }}
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        @error('assigneeIds.*')
                            <div class="mt-3 text-sm text-red-400">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 border-t border-neutral-700/60 pt-4">
                <flux:modal.close>
                    <flux:button variant="filled">Cancel</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">Create task</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
