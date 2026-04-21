<?php

namespace App\Livewire;

use App\Models\Epic;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Tag;
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

    #[Url(as: 'epic')]
    public ?int $epicId = null;

    #[Url(as: 'tag')]
    public ?int $tagId = null;

    #[Url(as: 'highlight')]
    public ?int $highlightId = null;

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

        $epics = $this->projectId
            ? Epic::where('project_id', $this->projectId)->orderBy('name')->get()
            : collect();

        $assignees = $this->projectId
            ? User::whereHas('assignedTasks', fn ($q) => $q->where('project_id', $this->projectId))
                ->orderBy('name')->get()
            : collect();

        $orgIds = Auth::user()->organizations()->pluck('organizations.id');
        $tags = Tag::whereIn('organization_id', $orgIds)->orderBy('name')->get();

        $tasksQuery = Task::with('assignees', 'project', 'tags')
            ->when($this->projectId, fn ($q) => $q->where('project_id', $this->projectId))
            ->when(! $this->projectId, fn ($q) => $q->whereIn('project_id', $projects->pluck('id')))
            ->when($this->sprintId, fn ($q) => $q->where('sprint_id', $this->sprintId))
            ->when($this->epicId, fn ($q) => $q->where('epic_id', $this->epicId))
            ->when($this->assigneeId, fn ($q) => $q->whereHas('assignees', fn ($a) => $a->whereKey($this->assigneeId)))
            ->when($this->tagId, fn ($q) => $q->whereHas('tags', fn ($t) => $t->whereKey($this->tagId)))
            ->orderByRaw("array_position(array['crit','high','med','low']::text[], priority)");

        $lanes = collect(Task::STATUSES)->mapWithKeys(fn ($s) => [
            $s => (clone $tasksQuery)->where('status', $s)->get(),
        ]);

        return view('livewire.kanban-board', [
            'projects' => $projects,
            'sprints' => $sprints,
            'epics' => $epics,
            'assignees' => $assignees,
            'tags' => $tags,
            'lanes' => $lanes,
        ]);
    }
}
