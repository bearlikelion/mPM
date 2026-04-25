<?php

namespace App\Livewire;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\SprintPlanningMeeting;
use App\Support\SiteTenant;
use App\Support\SprintPlanningService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;

class SprintPlanner extends Component
{
    #[Url(as: 'project')]
    public ?int $projectId = null;

    #[Url(as: 'meeting')]
    public ?int $meetingId = null;

    public string $meetingName = '';

    public ?string $scheduledAt = null;

    public function mount(): void
    {
        $this->projectId = $this->resolvedProjectId($this->projectId);
    }

    public function updatedProjectId(): void
    {
        $this->projectId = $this->resolvedProjectId($this->projectId);
        $this->meetingId = null;
    }

    public function scheduleMeeting(): void
    {
        $projectIds = $this->availableProjects()->pluck('id')->all();

        $this->validate([
            'projectId' => ['required', Rule::in($projectIds)],
            'meetingName' => ['required', 'string', 'max:255'],
            'scheduledAt' => ['required', 'date'],
        ]);

        $project = $this->availableProjects()->findOrFail($this->projectId);
        abort_unless(Auth::user()->can('update', $project), 403);

        $meeting = app(SprintPlanningService::class)->schedule(
            project: $project,
            facilitator: Auth::user(),
            name: $this->meetingName,
            scheduledAt: $this->scheduledAt,
            storyPointsLimit: $this->currentOrganization()?->storyPointsPerSprint() ?? Organization::DEFAULT_STORY_POINTS_PER_SPRINT,
        );

        $this->reset('meetingName', 'scheduledAt');
        $this->meetingId = $meeting->id;
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

        $meetings = $this->projectId
            ? SprintPlanningMeeting::query()
                ->with('facilitator', 'sprint')
                ->where('project_id', $this->projectId)
                ->orderByDesc('scheduled_at')
                ->get()
            : collect();

        $selectedMeeting = $this->meetingId
            ? $meetings->firstWhere('id', $this->meetingId)
            : null;

        $selectedProject = $this->projectId ? $projects->firstWhere('id', $this->projectId) : null;

        return view('livewire.sprint-planner', [
            'projects' => $projects,
            'projectOptions' => $projects->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'key' => $project->key,
                'avatar' => $project->avatarUrl(),
            ]),
            'sprints' => $sprints,
            'meetings' => $meetings,
            'selectedMeeting' => $selectedMeeting,
            'selectedProject' => $selectedProject,
            'canScheduleMeeting' => $selectedProject ? Auth::user()->can('update', $selectedProject) : false,
        ]);
    }
}
