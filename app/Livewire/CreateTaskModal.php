<?php

namespace App\Livewire;

use App\Models\Epic;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use App\Support\SiteTenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class CreateTaskModal extends Component
{
    public bool $showModal = false;

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

    #[On('open-create-task-modal')]
    public function openModal(): void
    {
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

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
        $this->projectId = $this->resolvedProjectId($this->projectId);
    }

    public function updatedProjectId(): void
    {
        $this->projectId = $this->resolvedProjectId($this->projectId);
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
        $this->showModal = false;

        $this->redirectRoute('tasks.show', ['key' => $task->key], navigate: true);
    }

    public function render()
    {
        $projects = $this->availableProjects()->orderBy('name')->get();
        $selectedProject = $this->projectId ? $projects->firstWhere('id', $this->projectId) : null;

        return view('livewire.create-task-modal', [
            'projects' => $projects,
            'projectOptions' => $projects->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'key' => $project->key,
            ]),
            'epics' => $selectedProject
                ? Epic::query()->where('project_id', $selectedProject->id)->orderBy('name')->get()
                : collect(),
            'sprints' => $selectedProject
                ? Sprint::query()->where('project_id', $selectedProject->id)->orderByDesc('starts_at')->get()
                : collect(),
            'epicOptions' => $selectedProject
                ? Epic::query()
                    ->where('project_id', $selectedProject->id)
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Epic $epic) => [
                        'id' => $epic->id,
                        'name' => $epic->name,
                    ])
                : collect(),
            'sprintOptions' => $selectedProject
                ? Sprint::query()
                    ->where('project_id', $selectedProject->id)
                    ->orderByDesc('starts_at')
                    ->get()
                    ->map(fn (Sprint $sprint) => [
                        'id' => $sprint->id,
                        'name' => $sprint->name,
                    ])
                : collect(),
            'assigneeOptions' => $selectedProject
                ? $this->assignableUsers($selectedProject)
                    ->get()
                    ->map(fn (User $user) => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar' => $user->avatarUrl(),
                    ])
                : collect(),
            'selectedProject' => $selectedProject,
        ]);
    }

    protected function availableProjects()
    {
        return app(SiteTenant::class)->projectsQuery(Auth::user(), $this->currentOrganization());
    }

    protected function currentOrganization(): ?Organization
    {
        return app(SiteTenant::class)->currentOrganization(Auth::user());
    }

    protected function resolvedProjectId(?int $projectId): ?int
    {
        $validProjectId = app(SiteTenant::class)->validProjectId(Auth::user(), $projectId, $this->currentOrganization());

        return $validProjectId ?? $this->availableProjects()->value('id');
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
        $this->projectId = $this->resolvedProjectId(null);
    }
}
