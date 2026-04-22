<?php

namespace App\Livewire;

use App\Models\Organization;
use App\Models\Sprint;
use App\Models\Task;
use App\Support\SiteTenant;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class BacklogBoard extends Component
{
    #[Url(as: 'project')]
    public ?int $projectId = null;

    public function mount(): void
    {
        $this->projectId = $this->resolvedProjectId($this->projectId);
    }

    public function updatedProjectId(): void
    {
        $this->projectId = $this->resolvedProjectId($this->projectId);
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

    public function render()
    {
        $projects = $this->availableProjects()->orderBy('name')->get();

        $sprints = $this->projectId
            ? Sprint::where('project_id', $this->projectId)->orderByDesc('starts_at')->get()
            : collect();

        $activeSprint = $this->projectId
            ? Sprint::query()
                ->where('project_id', $this->projectId)
                ->whereNotNull('started_at')
                ->whereNull('ended_at')
                ->first()
            : null;

        $unassignedTasks = collect();
        $backlog = collect();

        if ($this->projectId) {
            $baseQuery = Task::with('epic', 'assignees')
                ->where('project_id', $this->projectId)
                ->whereNull('sprint_id')
                ->where('status', '!=', 'done')
                ->orderByRaw("array_position(array['crit','high','med','low']::text[], priority)");

            if ($activeSprint) {
                $unassignedTasks = (clone $baseQuery)
                    ->where('created_at', '>', $activeSprint->started_at)
                    ->get();

                $backlog = (clone $baseQuery)
                    ->where('created_at', '<=', $activeSprint->started_at)
                    ->get();
            } else {
                $backlog = $baseQuery->get();
            }
        }

        return view('livewire.backlog-board', [
            'projects' => $projects,
            'sprints' => $sprints,
            'activeSprint' => $activeSprint,
            'unassignedTasks' => $unassignedTasks,
            'backlog' => $backlog,
        ]);
    }
}
