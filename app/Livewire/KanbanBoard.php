<?php

namespace App\Livewire;

use App\Models\Epic;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use App\Support\SiteTenant;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class KanbanBoard extends Component
{
    private const PROJECT_PALETTE = [
        ['key' => 'amber', 'stripe' => '#d79921', 'chip_bg' => 'rgba(215, 153, 33, 0.18)', 'chip_fg' => '#fabd2f'],
        ['key' => 'aqua', 'stripe' => '#689d6a', 'chip_bg' => 'rgba(142, 192, 124, 0.18)', 'chip_fg' => '#8ec07c'],
        ['key' => 'blue', 'stripe' => '#458588', 'chip_bg' => 'rgba(131, 165, 152, 0.18)', 'chip_fg' => '#83a598'],
        ['key' => 'purple', 'stripe' => '#b16286', 'chip_bg' => 'rgba(211, 134, 155, 0.18)', 'chip_fg' => '#d3869b'],
        ['key' => 'orange', 'stripe' => '#d65d0e', 'chip_bg' => 'rgba(254, 128, 25, 0.18)', 'chip_fg' => '#fe8019'],
        ['key' => 'red', 'stripe' => '#cc241d', 'chip_bg' => 'rgba(251, 73, 52, 0.18)', 'chip_fg' => '#fb4934'],
    ];

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

    #[Url(as: 'task')]
    public ?string $taskKey = null;

    #[Url(as: 'status')]
    public ?string $statusFilter = null;

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        $this->projectId = $this->validProjectId($this->projectId);

        if ($this->statusFilter && ! in_array($this->statusFilter, Task::STATUSES, true)) {
            $this->statusFilter = null;
        }
    }

    public function updatedProjectId(): void
    {
        $this->projectId = $this->validProjectId($this->projectId);
        $this->sprintId = null;
        $this->epicId = null;
        $this->assigneeId = null;
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
        return app(SiteTenant::class)->projectsQuery(Auth::user(), $this->currentOrganization());
    }

    protected function currentOrganization(): ?Organization
    {
        return app(SiteTenant::class)->currentOrganization(Auth::user());
    }

    protected function validProjectId(?int $projectId): ?int
    {
        return app(SiteTenant::class)->validProjectId(Auth::user(), $projectId, $this->currentOrganization());
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

        $tags = Tag::query()
            ->when($this->currentOrganization(), fn ($query, $organization) => $query->whereBelongsTo($organization))
            ->orderBy('name')
            ->get();

        $tasksQuery = Task::with('assignees', 'project', 'tags')
            ->withDependencyState()
            ->when($this->projectId, fn ($q) => $q->where('project_id', $this->projectId))
            ->when(! $this->projectId, fn ($q) => $q->whereIn('project_id', $projects->pluck('id')))
            ->when($this->sprintId, fn ($q) => $q->where('sprint_id', $this->sprintId))
            ->when($this->epicId, fn ($q) => $q->where('epic_id', $this->epicId))
            ->when($this->assigneeId, fn ($q) => $q->whereHas('assignees', fn ($a) => $a->whereKey($this->assigneeId)))
            ->when($this->tagId, fn ($q) => $q->whereHas('tags', fn ($t) => $t->whereKey($this->tagId)))
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($this->search)).'%';
                $q->where(fn ($w) => $w->where('title', 'ilike', $term)
                    ->orWhere('key', 'ilike', $term)
                    ->orWhere('description', 'ilike', $term));
            })
            ->orderByDependencyPriority()
            ->orderByRaw("array_position(array['crit','high','med','low']::text[], priority)");

        $lanes = collect(Task::STATUSES)->mapWithKeys(fn ($s) => [
            $s => $this->statusFilter && $this->statusFilter !== $s
                ? collect()
                : (clone $tasksQuery)->where('status', $s)->get(),
        ]);

        $visibleProjectIds = $this->projectId
            ? collect([$this->projectId])
            : $lanes->flatten()->pluck('project_id')->unique()->values();

        $projectColors = $visibleProjectIds->count() > 1
            ? $visibleProjectIds->mapWithKeys(fn ($id, $index) => [
                $id => self::PROJECT_PALETTE[$index % count(self::PROJECT_PALETTE)],
            ])
            : collect();

        return view('livewire.kanban-board', [
            'projects' => $projects,
            'sprints' => $sprints,
            'epics' => $epics,
            'assignees' => $assignees,
            'tags' => $tags,
            'projectOptions' => $projects->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
            ]),
            'sprintOptions' => $sprints->map(fn (Sprint $sprint) => [
                'id' => $sprint->id,
                'name' => $sprint->name,
            ]),
            'epicOptions' => $epics->map(fn (Epic $epic) => [
                'id' => $epic->id,
                'name' => $epic->name,
            ]),
            'assigneeOptions' => $assignees->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatarUrl(),
            ]),
            'tagOptions' => $tags->map(fn (Tag $tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
            ]),
            'lanes' => $lanes,
            'projectColors' => $projectColors,
        ]);
    }
}
