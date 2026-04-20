<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class BacklogBoard extends Component
{
    #[Url(as: 'project')]
    public ?int $projectId = null;

    public function mount(): void
    {
        $project = $this->availableProjects()->first();
        if ($project && ! $this->projectId) {
            $this->projectId = $project->id;
        }
    }

    public function assignToSprint(int $taskId, ?int $sprintId): void
    {
        $task = Task::whereIn('project_id', $this->availableProjects()->pluck('id'))
            ->findOrFail($taskId);

        if ($sprintId) {
            $sprint = Sprint::where('project_id', $task->project_id)->findOrFail($sprintId);
            $task->update(['sprint_id' => $sprint->id]);
        } else {
            $task->update(['sprint_id' => null]);
        }
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

        $backlog = $this->projectId
            ? Task::with('epic', 'assignees')
                ->where('project_id', $this->projectId)
                ->whereNull('sprint_id')
                ->where('status', '!=', 'done')
                ->orderByRaw("array_position(array['crit','high','med','low']::text[], priority)")
                ->get()
            : collect();

        return view('livewire.backlog-board', [
            'projects' => $projects,
            'sprints' => $sprints,
            'backlog' => $backlog,
        ]);
    }
}
