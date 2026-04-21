<?php

namespace App\Livewire;

use App\Models\Epic;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CreateTaskModal extends Component
{
    public ?int $projectId = null;

    public ?int $epicId = null;

    public ?int $sprintId = null;

    public array $assigneeIds = [];

    public string $title = '';

    public string $description = '';

    public string $priority = 'med';

    public string $status = 'todo';

    public ?int $storyPoints = null;

    public ?string $dueDate = null;

    public function toggleAssignee(int $userId): void
    {
        if (in_array($userId, $this->assigneeIds, true)) {
            $this->assigneeIds = array_values(array_filter(
                $this->assigneeIds,
                fn (int $selectedUserId) => $selectedUserId !== $userId
            ));

            return;
        }

        $this->assigneeIds[] = $userId;
        $this->assigneeIds = array_values(array_unique($this->assigneeIds));
    }

    public function mount(): void
    {
        $this->projectId = $this->availableProjects()->value('id');
    }

    public function updatedProjectId(): void
    {
        $this->epicId = null;
        $this->sprintId = null;
        $this->assigneeIds = [];
    }

    public function createTask(): void
    {
        $project = $this->availableProjects()->findOrFail($this->projectId);

        $assignableUserIds = $this->assignableUsers($project)->pluck('id')->all();
        $epicIds = Epic::query()->where('project_id', $project->id)->pluck('id')->all();
        $sprintIds = Sprint::query()->where('project_id', $project->id)->pluck('id')->all();

        $validated = $this->validate([
            'projectId' => ['required', Rule::in($this->availableProjects()->pluck('id')->all())],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', Rule::in(Task::PRIORITIES)],
            'status' => ['required', Rule::in(Task::STATUSES)],
            'storyPoints' => ['nullable', Rule::in(Task::STORY_POINTS)],
            'epicId' => ['nullable', Rule::in($epicIds)],
            'sprintId' => ['nullable', Rule::in($sprintIds)],
            'assigneeIds' => ['array'],
            'assigneeIds.*' => [Rule::in($assignableUserIds)],
            'dueDate' => ['nullable', 'date'],
        ]);

        $task = DB::transaction(function () use ($project, $validated) {
            $lockedProject = Project::query()->whereKey($project->id)->lockForUpdate()->firstOrFail();
            $lockedProject->increment('task_counter');
            $lockedProject->refresh();

            $task = Task::query()->create([
                'project_id' => $lockedProject->id,
                'epic_id' => $validated['epicId'],
                'sprint_id' => $validated['sprintId'],
                'created_by' => Auth::id(),
                'key' => $lockedProject->key.'-'.$lockedProject->task_counter,
                'title' => $validated['title'],
                'description' => $validated['description'],
                'status' => $validated['status'],
                'priority' => $validated['priority'],
                'story_points' => $validated['storyPoints'],
                'due_date' => $validated['dueDate'],
            ]);

            $task->assignees()->sync($validated['assigneeIds']);

            return $task;
        });

        $this->resetForm();
        $this->modal('create-task-modal')->close();

        $this->redirectRoute('tasks.show', ['key' => $task->key], navigate: true);
    }

    public function render()
    {
        $projects = $this->availableProjects()->orderBy('name')->get();
        $selectedProject = $this->projectId ? $projects->firstWhere('id', $this->projectId) : null;

        return view('livewire.create-task-modal', [
            'projects' => $projects,
            'epics' => $selectedProject
                ? Epic::query()->where('project_id', $selectedProject->id)->orderBy('name')->get()
                : collect(),
            'sprints' => $selectedProject
                ? Sprint::query()->where('project_id', $selectedProject->id)->orderByDesc('starts_at')->get()
                : collect(),
            'assignees' => $selectedProject
                ? $this->assignableUsers($selectedProject)->get()
                : collect(),
            'selectedProject' => $selectedProject,
        ]);
    }

    protected function availableProjects()
    {
        return Project::query()->whereIn(
            'organization_id',
            Auth::user()->organizations()->pluck('organizations.id')
        );
    }

    protected function assignableUsers(Project $project)
    {
        return User::query()
            ->whereHas('organizations', fn ($query) => $query->whereKey($project->organization_id))
            ->orderBy('name');
    }

    protected function resetForm(): void
    {
        $this->reset([
            'epicId',
            'sprintId',
            'assigneeIds',
            'title',
            'description',
            'storyPoints',
            'dueDate',
        ]);

        $this->priority = 'med';
        $this->status = 'todo';
        $this->projectId = $this->availableProjects()->value('id');
    }
}
