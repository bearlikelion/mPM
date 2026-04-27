<div
    x-data
    x-on:focus-task-title.window="
        setTimeout(() => {
            document.activeElement?.blur?.();
            $el.querySelectorAll('[x-data]').forEach(node => {
                const data = Alpine.$data(node);
                if (data && 'focused' in data) { data.focused = false; }
            });
            const el = $el.querySelector('[data-title-input] input');
            if (el) { el.focus(); el.select?.(); }
        }, 100);
    "
>
    <x-mary-modal wire:model="showModal" box-class="mary-task-modal max-w-5xl border-0 bg-transparent shadow-none">
        <form wire:submit="createTask" class="space-y-4">
            <section class="gv-card p-4 sm:p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0 space-y-1.5">
                        <div class="text-xs font-semibold uppercase tracking-[0.14em] text-[color:var(--gv-fg4)]">task intake</div>
                        <h2 class="text-xl font-semibold tracking-tight text-[color:var(--gv-fg0)]">
                            <span class="text-[color:var(--gv-amber)]">»</span> create new task
                        </h2>
                        <p class="text-sm text-[color:var(--gv-fg4)]">capture work quickly, place it in the right stream, and assign owners without leaving the current page.</p>
                    </div>

                    <div class="lg:w-72 lg:shrink-0">
                        <x-mary-choices-offline
                            wire:model.live="projectId"
                            :options="$projectOptions"
                            single
                            searchable
                            clearable
                            label="project"
                            placeholder="search a project"
                            option-sub-label="key"
                            option-avatar="avatar"
                            tabindex="-1"
                        />
                    </div>
                </div>
            </section>

            <div class="grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
                <div class="space-y-4">
                    <section class="gv-card p-4 sm:p-5">
                        <div class="mb-4">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-[color:var(--gv-amber)]">» task framing</h3>
                        </div>

                        <div data-title-input>
                            <x-mary-input
                                wire:model="title"
                                label="title"
                                placeholder="investigate login handoff"
                            />
                        </div>

                        <div class="mt-4">
                            <x-tiptap-editor
                                wire:model="description"
                                label="description"
                                placeholder="describe the work, blockers, or expected outcome."
                                rows="5"
                            />
                        </div>
                    </section>

                    <section class="gv-card p-4 sm:p-5">
                        <div class="mb-4">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-[color:var(--gv-amber)]">» priority and schedule</h3>
                        </div>

                        <div class="grid gap-4 md:grid-cols-3">
                            <x-mary-select wire:model="priority" label="priority" icon-right="o-chevron-up-down">
                                @foreach(\App\Models\Task::PRIORITIES as $priority)
                                    <option value="{{ $priority }}">{{ str($priority)->upper() }}</option>
                                @endforeach
                            </x-mary-select>

                            <x-mary-select wire:model="status" label="status" icon-right="o-chevron-up-down">
                                @foreach(\App\Models\Task::STATUSES as $status)
                                    <option value="{{ $status }}">{{ str($status)->replace('_', ' ')->title() }}</option>
                                @endforeach
                            </x-mary-select>

                            <x-mary-datetime wire:model="dueDate" label="due date" icon="o-calendar" />
                        </div>
                    </section>
                </div>

                <div class="space-y-4">
                    <section class="gv-card p-4 sm:p-5">
                        <div class="mb-4">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-[color:var(--gv-amber)]">» epic and sprint</h3>
                        </div>

                        <div class="grid gap-4">
                            <x-mary-choices-offline
                                wire:model.live="epicId"
                                :options="$epicOptions"
                                single
                                searchable
                                clearable
                                label="epic"
                                placeholder="no epic"
                                option-sub-label="project"
                                option-avatar="avatar"
                            />

                            @if($activeSprint)
                                <div class="gv-card-accent px-3 py-2.5">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-[color:var(--gv-amber)]">sprint active: {{ $activeSprint->name }}</div>
                                    <div class="mt-0.5 text-xs text-[color:var(--gv-fg4)]">new tasks go to the unassigned queue for sprint planning.</div>
                                </div>
                            @else
                                <x-mary-choices-offline
                                    wire:model.live="sprintId"
                                    :options="$sprintOptions"
                                    single
                                    searchable
                                    clearable
                                    label="sprint"
                                    placeholder="no sprint"
                                    option-sub-label="window"
                                    option-avatar="avatar"
                                />
                            @endif
                        </div>
                    </section>

                    <section class="gv-card p-4 sm:p-5">
                        <div class="mb-4 flex items-start justify-between gap-3">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-[color:var(--gv-amber)]">» assign teammates</h3>
                            <span class="app-chip">{{ count($assigneeIds) }} selected</span>
                        </div>

                        <x-mary-choices-offline
                            wire:model.live="assigneeIds"
                            :options="$assigneeOptions"
                            searchable
                            clearable
                            label="assignees"
                            placeholder="search by name or email"
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
                <div class="flex w-full items-center justify-end gap-2">
                    <x-mary-button label="cancel" wire:click="closeModal" class="btn-ghost border border-[color:var(--gv-border)] text-[color:var(--gv-fg2)]" />
                    <x-mary-button label="create & add another" wire:click="createTaskAndAddAnother" spinner="createTaskAndAddAnother" class="btn-ghost border border-[color:var(--gv-border)] text-[color:var(--gv-fg2)]" />
                    <x-mary-button label="create task" spinner="createTask" type="submit" class="btn-primary" />
                </div>
            </x-slot:actions>
        </form>
    </x-mary-modal>
</div>
