<?php

namespace App\Livewire;

use App\Models\Epic;
use App\Models\Organization;
use App\Support\SiteTenant;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class EpicsIndex extends Component
{
    #[Url(as: 'project')]
    public ?int $projectId = null;

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        $this->projectId = $this->validProjectId($this->projectId);
    }

    public function updatedProjectId(): void
    {
        $this->projectId = $this->validProjectId($this->projectId);
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

        $epics = Epic::with('project')
            ->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($q) => $q->where('status', 'done')])
            ->whereIn('project_id', $this->projectId ? [$this->projectId] : $projects->pluck('id'))
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($this->search)).'%';
                $q->where(fn ($w) => $w->where('name', 'ilike', $term)->orWhere('description', 'ilike', $term));
            })
            ->orderByDesc('id')
            ->get();

        return view('livewire.epics-index', [
            'projects' => $projects,
            'epics' => $epics,
        ]);
    }
}
