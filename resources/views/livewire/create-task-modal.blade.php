<div>
    <flux:modal name="create-task-modal" flyout class="w-full max-w-2xl">
        <form wire:submit="createTask" class="space-y-6">
            <div>
                <flux:heading size="lg">Create new task</flux:heading>
                <flux:subheading class="mt-2">
                    Capture work from anywhere, assign owners immediately, and drop it into the right project context.
                </flux:subheading>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <flux:select wire:model.live="projectId" label="Project">
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="title" label="Title" placeholder="Investigate login handoff" />
            </div>

            <flux:textarea wire:model="description" rows="5" label="Description" placeholder="Describe the work, blockers, or expected outcome." />

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

            <div class="grid gap-4 md:grid-cols-2">
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

            <div class="space-y-2">
                <label class="text-sm font-medium text-neutral-200">Assignees</label>
                <select wire:model="assigneeIds" multiple size="6" class="app-select w-full">
                    @foreach($assignees as $assignee)
                        <option value="{{ $assignee->id }}">{{ $assignee->name }} · {{ $assignee->email }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-neutral-500">Hold Command or Ctrl to select multiple assignees.</p>
                @error('assigneeIds.*')
                    <div class="text-sm text-red-400">{{ $message }}</div>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-3">
                <flux:modal.close>
                    <flux:button variant="filled">Cancel</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">Create task</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
