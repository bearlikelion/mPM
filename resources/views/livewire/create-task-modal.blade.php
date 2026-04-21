<div>
    <x-mary-modal wire:model="showModal" box-class="mary-task-modal max-w-5xl border-0 bg-transparent p-0 shadow-none">
        <form wire:submit="createTask" class="space-y-5">
            <section class="create-task-hero rounded-[1.65rem] px-5 py-5 sm:px-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="space-y-2">
                        <div class="app-eyebrow">Task Intake</div>
                        <h2 class="text-3xl font-semibold tracking-tight text-neutral-50">Create new task</h2>
                        <p class="max-w-2xl text-base leading-7 text-neutral-300">
                            Capture work quickly, place it in the right stream, and assign owners without leaving the current page.
                        </p>
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
            </section>

            <div class="grid gap-5 xl:grid-cols-[1.15fr_0.85fr]">
                <div class="space-y-5">
                    <section class="app-panel-muted rounded-[1.35rem] p-4 sm:p-5">
                        <div class="mb-4">
                            <div class="app-eyebrow">Core</div>
                            <div class="mt-1 text-lg font-semibold text-neutral-50">Task framing</div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <x-mary-choices-offline
                                wire:model.live="projectId"
                                :options="$projectOptions"
                                single
                                searchable
                                clearable
                                label="Project"
                                placeholder="Search a project"
                                option-sub-label="key"
                            />

                            <x-mary-input
                                wire:model="title"
                                label="Title"
                                placeholder="Investigate login handoff"
                            />
                        </div>

                        <div class="mt-4">
                            <x-mary-textarea
                                wire:model="description"
                                label="Description"
                                placeholder="Describe the work, blockers, or expected outcome."
                                rows="5"
                            />
                        </div>
                    </section>

                    <section class="app-panel-muted rounded-[1.35rem] p-4 sm:p-5">
                        <div class="mb-4">
                            <div class="app-eyebrow">Execution</div>
                            <div class="mt-1 text-lg font-semibold text-neutral-50">Priority and schedule</div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <x-mary-select wire:model="priority" label="Priority" icon-right="o-chevron-up-down">
                                @foreach(\App\Models\Task::PRIORITIES as $priority)
                                    <option value="{{ $priority }}">{{ str($priority)->upper() }}</option>
                                @endforeach
                            </x-mary-select>

                            <x-mary-select wire:model="status" label="Status" icon-right="o-chevron-up-down">
                                @foreach(\App\Models\Task::STATUSES as $status)
                                    <option value="{{ $status }}">{{ str($status)->replace('_', ' ')->title() }}</option>
                                @endforeach
                            </x-mary-select>

                            <x-mary-select wire:model="storyPoints" label="Story points" icon-right="o-chevron-up-down">
                                <option value="">None</option>
                                @foreach(\App\Models\Task::STORY_POINTS as $points)
                                    <option value="{{ $points }}">{{ $points }}</option>
                                @endforeach
                            </x-mary-select>

                            <x-mary-input wire:model="dueDate" type="date" label="Due date" />
                        </div>
                    </section>
                </div>

                <div class="space-y-5">
                    <section class="app-panel-muted rounded-[1.35rem] p-4 sm:p-5">
                        <div class="mb-4">
                            <div class="app-eyebrow">Placement</div>
                            <div class="mt-1 text-lg font-semibold text-neutral-50">Epic and sprint</div>
                        </div>

                        <div class="grid gap-4">
                            <x-mary-choices-offline
                                wire:model.live="epicId"
                                :options="$epicOptions"
                                single
                                searchable
                                clearable
                                label="Epic"
                                placeholder="No epic"
                            />

                            <x-mary-choices-offline
                                wire:model.live="sprintId"
                                :options="$sprintOptions"
                                single
                                searchable
                                clearable
                                label="Sprint"
                                placeholder="No sprint"
                            />
                        </div>
                    </section>

                    <section class="app-panel-muted rounded-[1.35rem] p-4 sm:p-5">
                        <div class="mb-4 flex items-start justify-between gap-3">
                            <div>
                                <div class="app-eyebrow">Ownership</div>
                                <div class="mt-1 text-lg font-semibold text-neutral-50">Assign teammates</div>
                            </div>

                            <span class="app-chip">{{ count($assigneeIds) }} selected</span>
                        </div>

                        <x-mary-choices-offline
                            wire:model.live="assigneeIds"
                            :options="$assigneeOptions"
                            searchable
                            clearable
                            label="Assignees"
                            placeholder="Search by name or email"
                            option-sub-label="email"
                            option-avatar="avatar"
                        />

                        @error('assigneeIds.*')
                            <div class="mt-3 text-sm text-red-400">{{ $message }}</div>
                        @enderror
                    </section>
                </div>
            </div>

            <x-slot:actions>
                <div class="flex w-full items-center justify-end gap-3">
                    <x-mary-button label="Cancel" wire:click="closeModal" class="btn-ghost border border-neutral-700/70 text-neutral-300" />
                    <x-mary-button label="Create task" spinner="createTask" type="submit" class="btn-primary" />
                </div>
            </x-slot:actions>
        </form>
    </x-mary-modal>
</div>
