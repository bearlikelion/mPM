<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class SprintPlanner extends Component
{
    #[Url(as: 'project')]
    public ?int $projectId = null;

    public string $name = '';

    public ?string $startsAt = null;

    public ?string $endsAt = null;

    public function mount(): void
    {
        $project = $this->availableProjects()->first();
        if ($project && ! $this->projectId) {
            $this->projectId = $project->id;
        }
    }

    public function createSprint(): void
    {
        $this->validate([
            'projectId' => 'required|integer',
            'name' => 'required|string|max:255',
            'startsAt' => 'required|date',
            'endsAt' => 'required|date|after:startsAt',
        ]);

        $this->availableProjects()->findOrFail($this->projectId);

        Sprint::create([
            'project_id' => $this->projectId,
            'name' => $this->name,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
        ]);

        $this->reset('name', 'startsAt', 'endsAt');
    }

    public function startSprint(int $sprintId): void
    {
        $sprint = $this->scopedSprint($sprintId);
        $sprint->update(['started_at' => now()]);
    }

    public function endSprint(int $sprintId): void
    {
        $sprint = $this->scopedSprint($sprintId);
        $sprint->update(['ended_at' => now()]);
    }

    protected function scopedSprint(int $id): Sprint
    {
        return Sprint::whereIn('project_id', $this->availableProjects()->pluck('id'))
            ->findOrFail($id);
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
            ? Sprint::withCount('tasks')
                ->where('project_id', $this->projectId)
                ->orderByDesc('starts_at')
                ->get()
            : collect();

        return view('livewire.sprint-planner', [
            'projects' => $projects,
            'sprints' => $sprints,
        ]);
    }
}
