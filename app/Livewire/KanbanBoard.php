<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class KanbanBoard extends Component
{
    #[Url(as: 'project')]
    public ?int $projectId = null;

    #[Url(as: 'sprint')]
    public ?int $sprintId = null;

    #[Url(as: 'assignee')]
    public ?int $assigneeId = null;

    public function mount(): void
    {
        $project = $this->availableProjects()->first();
        if ($project && ! $this->projectId) {
            $this->projectId = $project->id;
        }
    }

    public function updateStatus(int $taskId, string $status): void
    {
        if (! in_array($status, Task::STATUSES, true)) {
            return;
        }

        $task = Task::whereIn('project_id', $this->availableProjects()->pluck('id'))
            ->findOrFail($taskId);

        $task->update(['status' => $status]);
    }

    protected function availableProjects()
    {
        $orgIds = Auth::user()->organizations()->pluck('organizations.id');

        return Project::whereIn('organization_id', $orgIds);
    }

    public function render()
    {
        $projects = $this->availableProjects()->orderBy('name')->get();

        $sprints = $this->projectId
            ? Sprint::where('project_id', $this->projectId)->orderByDesc('starts_at')->get()
            : collect();

        $assignees = $this->projectId
            ? User::whereHas('assignedTasks', fn ($q) => $q->where('project_id', $this->projectId))
                ->orderBy('name')->get()
            : collect();

        $tasksQuery = Task::with('assignees', 'project')
            ->when($this->projectId, fn ($q) => $q->where('project_id', $this->projectId))
            ->when(! $this->projectId, fn ($q) => $q->whereIn('project_id', $projects->pluck('id')))
            ->when($this->sprintId, fn ($q) => $q->where('sprint_id', $this->sprintId))
            ->when($this->assigneeId, fn ($q) => $q->whereHas('assignees', fn ($a) => $a->whereKey($this->assigneeId)))
            ->orderByRaw("array_position(array['crit','high','med','low']::text[], priority)");

        $lanes = collect(Task::STATUSES)->mapWithKeys(fn ($s) => [
            $s => (clone $tasksQuery)->where('status', $s)->get(),
        ]);

        return view('livewire.kanban-board', [
            'projects' => $projects,
            'sprints' => $sprints,
            'assignees' => $assignees,
            'lanes' => $lanes,
        ]);
    }
}
