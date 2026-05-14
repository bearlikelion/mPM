<?php

namespace App\Livewire;

use App\Models\Comment;
use App\Models\Epic;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use App\Support\TaskActivityNotifier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class TaskDetail extends Component
{
    use WithFileUploads;

    public Task $task;

    public bool $embedded = false;

    public bool $editingDetails = false;

    public string $newComment = '';

    public array $attachments = [];

    public string $title = '';

    public string $description = '';

    public string $status;

    public string $priority;

    public ?int $storyPoints = null;

    public ?string $dueDate = null;

    public ?int $epicId = null;

    public ?int $sprintId = null;

    public array $assigneeIds = [];

    public array $blockerIds = [];

    public function mount(string $taskKey, bool $embedded = false): void
    {
        $this->embedded = $embedded;

        $orgIds = Auth::user()->organizations()->pluck('organizations.id');
        $projectIds = Project::whereIn('organization_id', $orgIds)->pluck('id');

        $this->task = Task::with('project', 'epic', 'sprint', 'assignees', 'tags', 'comments.user', 'comments.media', 'creator', 'media')
            ->withDependencyState()
            ->whereIn('project_id', $projectIds)
            ->where('key', $taskKey)
            ->firstOrFail();

        $this->syncEditableFieldsFromTask();
    }

    public function editDetails(): void
    {
        $this->syncEditableFieldsFromTask();
        $this->editingDetails = true;
    }

    public function cancelEditingDetails(): void
    {
        $this->syncEditableFieldsFromTask();
        $this->resetValidation([
            'title',
            'description',
            'dueDate',
            'epicId',
            'sprintId',
            'assigneeIds',
            'assigneeIds.*',
        ]);
        $this->editingDetails = false;
    }

    public function saveDetails(): void
    {
        $assignableUserIds = $this->assignableUsers()->pluck('id')->all();
        $epicIds = Epic::query()->where('project_id', $this->task->project_id)->pluck('id')->all();
        $sprintIds = Sprint::query()->where('project_id', $this->task->project_id)->pluck('id')->all();

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'dueDate' => ['nullable', 'date'],
            'epicId' => ['nullable', Rule::in($epicIds)],
            'sprintId' => ['nullable', Rule::in($sprintIds)],
            'assigneeIds' => ['array'],
            'assigneeIds.*' => [Rule::in($assignableUserIds)],
        ]);

        $previousAssigneeIds = $this->task->assignees()->pluck('users.id');
        $previousDescription = (string) $this->task->description;

        $this->task->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'due_date' => $validated['dueDate'],
            'epic_id' => $validated['epicId'],
            'sprint_id' => $validated['sprintId'],
        ]);

        $this->task->assignees()->sync($validated['assigneeIds']);
        $this->refreshTask();
        $this->syncEditableFieldsFromTask();

        $this->task->assignees
            ->whereNotIn('id', $previousAssigneeIds)
            ->each(fn (User $user) => app(TaskActivityNotifier::class)->taskAssigned($this->task, $user, Auth::user()));

        if ($previousDescription !== (string) $this->task->description && ! empty($this->task->description)) {
            app(TaskActivityNotifier::class)->mentioned($this->task, $this->task->description, Auth::user());
        }

        $this->dispatch('task-details-saved');
        $this->editingDetails = false;
    }

    protected function syncEditableFieldsFromTask(): void
    {
        $this->title = $this->task->title;
        $this->description = (string) $this->task->description;
        $this->status = $this->task->status;
        $this->priority = $this->task->priority;
        $this->storyPoints = $this->task->story_points;
        $this->dueDate = $this->task->due_date?->format('Y-m-d');
        $this->epicId = $this->task->epic_id;
        $this->sprintId = $this->task->sprint_id;
        $this->assigneeIds = $this->task->assignees->pluck('id')->all();
        $this->blockerIds = $this->task->blockers->pluck('id')->all();
    }

    public function updateField(string $field): void
    {
        $allowed = [
            'status' => Task::STATUSES,
            'priority' => Task::PRIORITIES,
            'storyPoints' => array_map('strval', Task::STORY_POINTS),
        ];

        if (! array_key_exists($field, $allowed)) {
            return;
        }

        $value = $this->{$field};

        if ($field === 'storyPoints') {
            $this->task->update(['story_points' => $value ?: null]);
        } else {
            $this->task->update([$field => $value]);
        }

        if ($field === 'status' && $value === 'review') {
            app(TaskActivityNotifier::class)->reviewRequested($this->task->refresh(), Auth::user());
        }

        $this->refreshTask();
        $this->syncEditableFieldsFromTask();
    }

    public function addComment(): void
    {
        $this->validate([
            'newComment' => 'nullable|string',
            'attachments.*' => 'file|max:10240',
        ]);

        if (trim($this->newComment) === '' && empty($this->attachments)) {
            $this->addError('newComment', 'Write something or attach a file.');

            return;
        }

        $comment = Comment::create([
            'task_id' => $this->task->id,
            'user_id' => Auth::id(),
            'body' => $this->newComment ?: '',
        ]);

        foreach ($this->attachments as $file) {
            $comment->addMedia($file->getRealPath())
                ->usingName($file->getClientOriginalName())
                ->usingFileName($file->getClientOriginalName())
                ->toMediaCollection('attachments');
        }

        $this->newComment = '';
        $this->attachments = [];
        $this->task->load('comments.user', 'comments.media');

        if ($comment->body !== '') {
            app(TaskActivityNotifier::class)->mentioned($this->task, $comment->body, Auth::user());
        }
    }

    public function updatedBlockerIds(): void
    {
        $availableTaskIds = $this->availableTasks()->pluck('id')->all();

        $validated = $this->validate([
            'blockerIds' => ['array'],
            'blockerIds.*' => [Rule::in($availableTaskIds)],
        ]);

        $previousBlockers = $this->task->blockers()->get(['tasks.id', 'tasks.key', 'tasks.title']);

        $this->task->blockers()->sync($validated['blockerIds']);
        $this->refreshTask();
        $this->blockerIds = $this->task->blockers->pluck('id')->all();

        $currentBlockers = $this->task->blockers;

        app(TaskActivityNotifier::class)->blockersChanged(
            task: $this->task,
            added: $currentBlockers->whereNotIn('id', $previousBlockers->pluck('id')),
            removed: $previousBlockers->whereNotIn('id', $currentBlockers->pluck('id')),
            actor: Auth::user(),
        );
    }

    protected function availableTasks()
    {
        return Task::query()
            ->where('project_id', $this->task->project_id)
            ->whereKeyNot($this->task->id)
            ->orderBy('key');
    }

    protected function assignableUsers()
    {
        return User::query()
            ->whereHas('organizations', fn ($query) => $query->whereKey($this->task->project->organization_id))
            ->orderBy('name');
    }

    protected function refreshTask(): void
    {
        $this->task->refresh();
        $this->task->load('project', 'epic', 'sprint', 'assignees', 'tags', 'comments.user', 'comments.media', 'creator', 'media');
        $this->task->loadCount(['blockers', 'blockedTasks']);
        $this->task->load([
            'blockers:id,key,title,project_id',
            'blockedTasks:id,key,title,project_id',
        ]);
    }

    public function render()
    {
        return view('livewire.task-detail', [
            'epicOptions' => Epic::query()
                ->where('project_id', $this->task->project_id)
                ->orderBy('name')
                ->get()
                ->map(fn (Epic $epic) => [
                    'id' => $epic->id,
                    'name' => $epic->name,
                    'avatar' => $epic->avatarUrl(),
                ])
                ->values()
                ->all(),
            'sprintOptions' => Sprint::query()
                ->where('project_id', $this->task->project_id)
                ->orderByDesc('starts_at')
                ->get()
                ->map(fn (Sprint $sprint) => [
                    'id' => $sprint->id,
                    'name' => $sprint->name,
                    'window' => trim(($sprint->starts_at?->format('M j') ?? 'unscheduled').' - '.($sprint->ends_at?->format('M j') ?? 'open')),
                    'avatar' => $sprint->avatarUrl(),
                ])
                ->values()
                ->all(),
            'assigneeOptions' => $this->assignableUsers()
                ->get()
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatarUrl(),
                ])
                ->values()
                ->all(),
            'blockerOptions' => $this->availableTasks()
                ->get(['id', 'key', 'title'])
                ->map(fn (Task $task) => [
                    'id' => $task->id,
                    'name' => $task->key.' · '.$task->title,
                ])
                ->values()
                ->all(),
        ]);
    }
}
