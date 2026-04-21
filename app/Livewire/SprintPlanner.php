<?php

namespace App\Livewire;

use App\Models\Organization;
use App\Models\Sprint;
use App\Support\SiteTenant;
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
        $this->projectId = $this->resolvedProjectId($this->projectId);
    }

    public function updatedProjectId(): void
    {
        $this->projectId = $this->resolvedProjectId($this->projectId);
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
